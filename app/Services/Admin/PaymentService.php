<?php

namespace App\Services\Admin;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\BookingDetail;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Enums\BookingDetailStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;

class PaymentService
{
    public function process(array $payload): Payment
    {
        $booking = Booking::query()->with('details')->findOrFail($payload['booking_id']);

        $this->verifyBookingIntegrity($booking);
        $this->verifyFinancials($booking, $payload);

        $referenceId = $payload['reference_id'] ?? 'CSH-' . Str::upper(Str::random(12));

        $paymentStatus = PaymentStatus::SUCCESS->value;

        return DB::transaction(function () use ($booking, $payload, $referenceId, $paymentStatus) {
            $createdPayment = Payment::create([
                'fk_booking_id'        => $booking->id,
                'fk_booking_detail_id' => $payload['booking_detail_id'] ?? null,
                'reference_id'         => $referenceId,
                'payment_url'          => null,
                'payment_type'         => $payload['payment_type'],
                'method'               => $payload['method'],
                'amount'               => $payload['amount'],
                'status'               => $paymentStatus,
                'paid_at'              => now(),
            ]);

            if (in_array($payload['payment_type'], [PaymentType::DOWN_PAYMENT->value, PaymentType::FINAL_PAYMENT->value], true)) {
                $booking->details()->where('status', BookingDetailStatus::WAITING->value)->update([
                    'status' => BookingDetailStatus::ACTIVE->value
                ]);
            }

            return $createdPayment;
        });
    }

    private function verifyBookingIntegrity(Booking $booking): void
    {
        if ($booking->details->isEmpty()) {
            throw new HttpException(400, 'Booking has no associated details.');
        }

        $allCancelled = $booking->details->every(function ($detail) {
            /** @var BookingDetail $detail */
            return $detail->status === BookingDetailStatus::CANCELLED->value;
        });

        if ($allCancelled) {
            throw new HttpException(400, 'Cannot process payment for a fully cancelled booking.');
        }
    }

    private function verifyFinancials(Booking $booking, array $payload): void
    {
        $totalPrice = $booking->details->sum(fn($d) => $d->price);

        $totalPaid = Payment::query()->where('fk_booking_id', $booking->id)
            ->where('status', PaymentStatus::SUCCESS->value)
            ->whereIn('payment_type', [PaymentType::DOWN_PAYMENT->value, PaymentType::FINAL_PAYMENT->value, PaymentType::RESCHEDULE_FEE->value])
            ->sum(fn($p) => $p->amount);

        $totalRefunded = Payment::query()->where('fk_booking_id', $booking->id)
            ->where('status', PaymentStatus::SUCCESS->value)
            ->where('payment_type', PaymentType::REFUND->value)
            ->sum(fn($p) => $p->amount);

        $netPaid = $totalPaid - $totalRefunded;

        if (in_array($payload['payment_type'], [PaymentType::DOWN_PAYMENT->value, PaymentType::FINAL_PAYMENT->value, PaymentType::RESCHEDULE_FEE->value], true)) {
            $this->validateIncomingPayment($payload, $netPaid, $totalPrice, $booking->id);
        } elseif ($payload['payment_type'] === PaymentType::REFUND->value) {
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

        if ($payload['payment_type'] === PaymentType::DOWN_PAYMENT->value) {
            $hasDownPayment = Payment::query()->where('fk_booking_id', $bookingId)
                ->where('payment_type', PaymentType::DOWN_PAYMENT->value)
                ->where('status', PaymentStatus::SUCCESS->value)
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
