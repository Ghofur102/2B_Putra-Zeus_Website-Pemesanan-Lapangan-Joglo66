<?php

namespace App\Services\Tenant\Booking;

use App\Enums\BookingDetailStatus;
use App\Enums\CancelRefundStatus;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Models\BookingCancelled;
use App\Models\BookingDetail;
use App\Models\Payment;
use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TenantCancelBookingService
{
    private const DB_CONNECTION = 'mysql_joglo66_app';

    public function getCancellationData(BookingDetail $detail): array
    {
        $playDate = Carbon::parse($detail->play_date)->startOfDay();
        $daysUntilPlay = Carbon::now()->startOfDay()->diffInDays($playDate, false);
        $netPaid = $this->calculatePaymentTotals($detail);

        $isRefundable = $daysUntilPlay >= 3;
        $refundAmount = $isRefundable ? $netPaid : 0;

        return [
            'playDate' => $playDate,
            'daysUntilPlay' => $daysUntilPlay,
            'netPaid' => $netPaid,
            'isRefundable' => $isRefundable,
            'refundAmount' => $refundAmount,
        ];
    }

    public function processCancellation(BookingDetail $detail, string $reason): void
    {
        if (strtolower($detail->status) === 'waiting') {
            throw new UnexpectedValueException('Fitur reschedule tidak tersedia. Silakan selesaikan pembayaran terlebih dahulu.');
        }

        if ($detail->status === BookingDetailStatus::CANCELLED->value) {
            throw new DomainException('Booking ini sudah dibatalkan sebelumnya.');
        }

        $cancellationData = $this->getCancellationData($detail);

        DB::connection(self::DB_CONNECTION)->transaction(function () use ($detail, $reason, $cancellationData) {
            BookingCancelled::create([
                'fk_booking_detail_id' => $detail->id,
                'cancle_date' => now()->toDateString(),
                'reason' => $reason,
                'status_refund' => $cancellationData['isRefundable']
                    ? CancelRefundStatus::FULL->value
                    : CancelRefundStatus::NONE->value,
            ]);

            $detail->update(['status' => BookingDetailStatus::CANCELLED->value]);

            if ($cancellationData['isRefundable'] && $cancellationData['refundAmount'] > 0) {
                Payment::create([
                    'fk_booking_id' => $detail->fk_booking_id,
                    'fk_booking_detail_id' => $detail->id,
                    'reference_id' => 'CNL-REF-'.Str::upper(Str::random(10)),
                    'payment_type' => PaymentType::REFUND->value,
                    'method' => 'cash',
                    'amount' => $cancellationData['refundAmount'],
                    'status' => PaymentStatus::SUCCESS->value,
                    'paid_at' => now(),
                ]);
            }
        });
    }

    public function calculatePaymentTotals(BookingDetail $detail): int
    {
        $successfulPayments = Payment::query()
            ->where('fk_booking_id', $detail->fk_booking_id)
            ->where('status', PaymentStatus::SUCCESS->value)
            ->get();

        $hasFinal = $successfulPayments->where('payment_type', PaymentType::FINAL_PAYMENT->value)->isNotEmpty();
        $hasDP = $successfulPayments->where('payment_type', PaymentType::DOWN_PAYMENT->value)->isNotEmpty();

        $paidForThisSlot = 0;
        if ($hasFinal) {
            $paidForThisSlot = $detail->price;
        } elseif ($hasDP) {
            $paidForThisSlot = $detail->price / 2;
        }

        $refunded = Payment::query()
            ->where('fk_booking_detail_id', $detail->id)
            ->where('status', PaymentStatus::SUCCESS->value)
            ->where('payment_type', PaymentType::REFUND->value)
            ->sum(fn ($p) => $p->amount);

        return (int) ($paidForThisSlot - $refunded);
    }
}
