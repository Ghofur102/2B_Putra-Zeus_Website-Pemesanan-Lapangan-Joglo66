<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\BookingDetail;
use App\Models\Payment;
use App\Models\Field;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class controller_danil extends Controller
{
    public function __construct()
    {
        // Pastikan API ini hanya bisa diakses oleh admin yang sah.
        $this->middleware('auth:sanctum');
        $this->middleware(function ($request, $next) {
            $user = $request->user();

            if (! $user || ! in_array($user->role, ['owner', 'manager', 'treasurer'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized admin access.',
                ], 403);
            }

            return $next($request);
        });
    }

    /**
     * Buat booking baru untuk admin.
     * Endpoint: POST /api/admin/create-booking
     */
    public function createBooking(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'field_id' => ['required', 'integer', 'exists:fields,id'],
            'team_name' => ['required', 'string', 'max:50'],
            'booking_date' => ['required', 'date'],
            'details' => ['required', 'array', 'min:1'],
            'details.*.start_play_time' => ['required', 'date_format:H:i'],
            'details.*.end_play_time' => ['required', 'date_format:H:i'],
            'details.*.play_date' => ['required', 'date'],
            'details.*.price' => ['required', 'integer', 'min:0'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $payload = $validator->validated();
        $field = Field::find($payload['field_id']);
        $user = User::find($payload['user_id']);

        if (! $field || ! $user) {
            return response()->json([
                'success' => false,
                'message' => 'User or field not found.',
            ], 400);
        }

        $details = $payload['details'];
        $totalPrice = 0;
        $conflictDetail = null;

        foreach ($details as $index => $detail) {
            if ($detail['start_play_time'] >= $detail['end_play_time']) {
                return response()->json([
                    'success' => false,
                    'message' => "Detail #{$index} has invalid time range.",
                ], 400);
            }

            if ($this->hasFieldConflict($payload['field_id'], $detail)) {
                $conflictDetail = $detail;
                break;
            }

            if (! $this->validateFieldPrice($payload['field_id'], $detail)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Price validation failed for booking detail. Please use the active field price for the requested schedule.',
                ], 400);
            }

            $totalPrice += $detail['price'];
        }

        if ($conflictDetail) {
            return response()->json([
                'success' => false,
                'message' => 'Field is already booked for the requested time range.',
                'conflict' => $conflictDetail,
            ], 400);
        }

        try {
            $booking = DB::transaction(function () use ($payload, $details, $user, $field, &$totalPrice) {
                $booking = Booking::create([
                    'fk_user_id' => $payload['user_id'],
                    'fk_field_id' => $payload['field_id'],
                    'team_name' => $payload['team_name'],
                    'booking_date' => $payload['booking_date'],
                ]);

                foreach ($details as $detail) {
                    BookingDetail::create([
                        'fk_booking_id' => $booking->id,
                        'start_play_time' => $detail['start_play_time'],
                        'end_play_time' => $detail['end_play_time'],
                        'play_date' => $detail['play_date'],
                        'price' => $detail['price'],
                        'status' => 'waiting',
                    ]);
                }

                return $booking;
            });
        } catch (\Exception $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create booking. Please try again.',
                'error' => $exception->getMessage(),
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Booking created successfully.',
            'data' => [
                'booking_id' => $booking->id,
                'total_price' => $totalPrice,
            ],
        ], 201);
    }

    /**
     * Proses pembayaran booking untuk admin.
     * Endpoint: POST /api/admin/payment-booking
     */
    public function paymentBooking(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'booking_id' => ['required', 'integer', 'exists:bookings,id'],
            'booking_detail_id' => ['nullable', 'integer', 'exists:booking_details,id'],
            'payment_type' => ['required', Rule::in(['down payment', 'final payment', 'reschedule fee', 'refund'])],
            'method' => ['required', Rule::in(['cash', 'transfer'])],
            'amount' => ['required', 'integer', 'min:1'],
            'reference_id' => ['nullable', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $payload = $validator->validated();
        $booking = Booking::with('details')->find($payload['booking_id']);

        if (! $booking) {
            return response()->json([
                'success' => false,
                'message' => 'Booking not found.',
            ], 400);
        }

        if ($booking->details->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Booking has no associated details.',
            ], 400);
        }

        if ($booking->details->every(fn ($detail) => $detail->status === 'cancelled')) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot process payment for a fully cancelled booking.',
            ], 400);
        }

        if (! empty($payload['booking_detail_id'])) {
            $detail = $booking->details->firstWhere('id', $payload['booking_detail_id']);
            if (! $detail) {
                return response()->json([
                    'success' => false,
                    'message' => 'Booking detail does not belong to the selected booking.',
                ], 400);
            }
        }

        $referenceId = $payload['reference_id'] ?? Str::upper(Str::random(16));
        $paymentUrl = null;
        $paymentStatus = 'pending';

        if ($payload['method'] === 'cash') {
            $paymentStatus = 'success';
            $paymentUrl = null;
        } else {
            $paymentUrl = $this->generatePaymentUrl($booking->id, $referenceId);
        }

        try {
            $payment = DB::transaction(function () use ($booking, $payload, $referenceId, $paymentUrl, $paymentStatus) {
                $payment = Payment::create([
                    'fk_booking_id' => $booking->id,
                    'fk_booking_detail_id' => $payload['booking_detail_id'] ?? null,
                    'reference_id' => $referenceId,
                    'payment_url' => $paymentUrl,
                    'payment_type' => $payload['payment_type'],
                    'method' => $payload['method'],
                    'amount' => $payload['amount'],
                    'status' => $paymentStatus,
                    'paid_at' => $paymentStatus === 'success' ? now() : null,
                ]);

                if ($payload['payment_type'] === 'down payment') {
                    $booking->details()->where('status', 'waiting')->update(['status' => 'active']);
                }

                return $payment;
            });
        } catch (\Exception $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to process payment.',
                'error' => $exception->getMessage(),
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Payment processed successfully.',
            'data' => [
                'payment_id' => $payment->id,
                'status' => $payment->status,
                'payment_url' => $paymentUrl,
            ],
        ], 200);
    }

    private function hasFieldConflict(int $fieldId, array $detail): bool
    {
        return BookingDetail::query()
            ->whereHas('booking', function ($query) use ($fieldId) {
                $query->where('fk_field_id', $fieldId);
            })
            ->where('play_date', $detail['play_date'])
            ->where(function ($query) use ($detail) {
                $query->whereBetween('start_play_time', [$detail['start_play_time'], $detail['end_play_time']])
                    ->orWhereBetween('end_play_time', [$detail['start_play_time'], $detail['end_play_time']])
                    ->orWhere(function ($subQuery) use ($detail) {
                        $subQuery->where('start_play_time', '<=', $detail['start_play_time'])
                            ->where('end_play_time', '>=', $detail['end_play_time']);
                    });
            })
            ->exists();
    }

    private function validateFieldPrice(int $fieldId, array $detail): bool
    {
        $dayName = strtolower(date('l', strtotime($detail['play_date'])));

        $price = DB::table('field_prices')
            ->where('fk_field_id', $fieldId)
            ->where('day_type', $dayName)
            ->where('start_time', '<=', $detail['start_play_time'])
            ->where('end_time', '>=', $detail['end_play_time'])
            ->value('price');

        if (! is_null($price) && (int) $price !== (int) $detail['price']) {
            return false;
        }

        return true;
    }

    private function generatePaymentUrl(int $bookingId, string $referenceId): string
    {
        return sprintf('https://payment-gateway.example.com/pay/%s/%s', $bookingId, $referenceId);
    }
}
