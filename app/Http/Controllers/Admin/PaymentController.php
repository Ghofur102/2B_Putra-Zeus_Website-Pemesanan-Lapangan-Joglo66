<?php

namespace App\Http\Controllers\Admin;

use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class PaymentController extends Controller
{
    private const STR_SUCCESS = 'success';
    private const STR_WORKER = 'worker';
    private const DOWN_PAYMENT = 'down payment';
    private const FINAL_PAYMENT = 'final payment';
    private const RESCHEDULE_FEE = 'reschedule fee';
    private const STR_REFUND = 'refund';
    private const STR_CASH = 'cash';

    public function processPayment(Request $request): JsonResponse
    {
        $status = 200;
        try {
            $validator = Validator::make($request->all(), [
                'booking_id' => ['required', 'integer', 'exists:bookings,id'],
                'booking_detail_id' => ['nullable', 'integer', 'exists:booking_details,id'],
                'payment_type' => ['required', Rule::in([self::DOWN_PAYMENT, self::FINAL_PAYMENT, self::RESCHEDULE_FEE, self::STR_REFUND])],
                'method' => ['required', Rule::in([self::STR_CASH, 'transfer'])],
                'amount' => ['required', 'integer', 'min:1'],
                'reference_id' => ['nullable', 'string', 'max:255'],
            ]);

            if ($validator->fails()) {
                $status = 422;
                $data = [
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors(),
                ];
                return response()->json($data, $status);
            }

            $payload = $validator->validated();
            $booking = Booking::with('details')->find($payload['booking_id']);

            if (!$booking) {
                throw new HttpException(400, 'Booking not found.');
            }

            $this->verifyWorkerAccess($request->user(), $booking);
            $this->verifyBookingIntegrity($booking);
            $this->verifyFinancials($booking, $payload);

            $referenceId = $payload['reference_id'] ?? Str::upper(Str::random(16));
            $paymentStatus = $payload['method'] === self::STR_CASH ? self::STR_SUCCESS : 'pending';
            $paymentUrl = $payload['method'] === self::STR_CASH
                ? null
                : sprintf('https://payment-gateway.example.com/pay/%s/%s', $booking->id, $referenceId);

            $payment = DB::transaction(function () use ($booking, $payload, $referenceId, $paymentUrl, $paymentStatus) {
                $createdPayment = Payment::create([
                    'fk_booking_id' => $booking->id,
                    'fk_booking_detail_id' => $payload['booking_detail_id'] ?? null,
                    'reference_id' => $referenceId,
                    'payment_url' => $paymentUrl,
                    'payment_type' => $payload['payment_type'],
                    'method' => $payload['method'],
                    'amount' => $payload['amount'],
                    'status' => $paymentStatus,
                    'paid_at' => $paymentStatus === self::STR_SUCCESS ? now() : null,
                ]);

                if (in_array($payload['payment_type'], [self::DOWN_PAYMENT, self::FINAL_PAYMENT], true)) {
                    $booking->details()->where('status', 'waiting')->update(['status' => 'active']);
                }

                return $createdPayment;
            });

            $data = [
                'success' => true,
                'message' => 'Payment processed successfully.',
                'data' => [
                    'payment_id' => $payment->id,
                    'status' => $payment->status,
                    'payment_url' => $paymentUrl,
                ],
            ];
        } catch (HttpException $e) {
            $status = $e->getStatusCode();
            $data = [
                'success' => false,
                'message' => $e->getMessage()
            ];
        } catch (Throwable $e) {
            $status = 500;
            $data = [
                'success' => false,
                'message' => 'Failed to process payment.',
                'error' => $e->getMessage(),
            ];
        }

        return response()->json($data, $status);
    }

    private function verifyWorkerAccess($user, $booking): void
    {
        if ($user && $user->role === self::STR_WORKER) {
            $isAuthorized = DB::table('field_admins')
                ->where('fk_user_id', $user->id)
                ->where('fk_field_id', $booking->fk_field_id)
                ->exists();

            if (!$isAuthorized) {
                throw new HttpException(403, 'Forbidden. Anda tidak memiliki akses untuk memproses pembayaran di lapangan ini.');
            }
        }
    }

    private function verifyBookingIntegrity($booking): void
    {
        if ($booking->details->isEmpty()) {
            throw new HttpException(400, 'Booking has no associated details.');
        }

        if ($booking->details->every(fn ($detail) => $detail->status === 'cancelled')) {
            throw new HttpException(400, 'Cannot process payment for a fully cancelled booking.');
        }
    }

    private function verifyFinancials($booking, array $payload): void
    {
        $totalPrice = $booking->details->sum('price');

        $totalPaid = Payment::where('fk_booking_id', $booking->id)
            ->where('status', self::STR_SUCCESS)
            ->whereIn('payment_type', [self::DOWN_PAYMENT, self::FINAL_PAYMENT, self::RESCHEDULE_FEE])
            ->sum('amount');

        $totalRefunded = Payment::where('fk_booking_id', $booking->id)
            ->where('status', self::STR_SUCCESS)
            ->where('payment_type', self::STR_REFUND)
            ->sum('amount');

        $netPaid = $totalPaid - $totalRefunded;

        if (in_array($payload['payment_type'], [self::DOWN_PAYMENT, self::FINAL_PAYMENT, self::RESCHEDULE_FEE], true)) {
            $this->validateIncomingPayment($payload, $netPaid, $totalPrice, $booking->id);
        } elseif ($payload['payment_type'] === self::STR_REFUND) {
            $this->validateRefundPayment($payload, $netPaid);
        }
    }

    private function validateIncomingPayment(array $payload, float $netPaid, float $totalPrice, int $bookingId): void
    {
        if ($netPaid >= $totalPrice) {
            throw new HttpException(400, 'Pesanan ini sudah lunas sepenuhnya. Tidak dapat memproses pembayaran lagi.');
        }

        if (($netPaid + $payload['amount']) > $totalPrice) {
            $remaining = $totalPrice - $netPaid;
            throw new HttpException(400, 'Nominal pembayaran melebihi sisa tagihan. Sisa tagihan saat ini adalah: Rp ' . number_format($remaining, 0, ',', '.'));
        }

        if ($payload['payment_type'] === self::DOWN_PAYMENT) {
            $hasDownPayment = Payment::where('fk_booking_id', $bookingId)
                ->where('payment_type', self::DOWN_PAYMENT)
                ->where('status', self::STR_SUCCESS)
                ->exists();

            if ($hasDownPayment) {
                throw new HttpException(400, 'Down Payment (DP) sudah dibayarkan sebelumnya. Silakan pilih Final Payment (Pelunasan).');
            }
        }
    }

    private function validateRefundPayment(array $payload, float $netPaid): void
    {
        if ($payload['amount'] > $netPaid) {
            throw new HttpException(400, 'Nominal refund melebihi total uang yang telah dibayarkan (Maksimal Refund: Rp ' . number_format($netPaid, 0, ',', '.') . ').');
        }
    }
}
