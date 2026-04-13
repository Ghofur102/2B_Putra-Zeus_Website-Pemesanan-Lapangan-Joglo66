<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PaymentController extends Controller
{
    // POST: /api/admin/payment-booking
    public function processPayment(Request $request)
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
            $paymentUrl = sprintf('https://payment-gateway.example.com/pay/%s/%s', $booking->id, $referenceId);
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
}
