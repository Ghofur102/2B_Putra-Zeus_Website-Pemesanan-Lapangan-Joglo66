<?php

namespace App\Services\Admin;

use App\Enums\BookingDetailStatus;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Models\BookingDetail;
use App\Models\BookingReschedule;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RescheduleService
{
    public function execute(BookingDetail $detail, array $data): void
    {
        DB::transaction(function () use ($detail, $data) {

            $oldPrice = (int) $detail->price;
            $newPrice = (int) $data['new_price'];

            if ($newPrice > $oldPrice) {
                $statusRefund = 'deposit required';
            } elseif ($newPrice < $oldPrice) {
                $statusRefund = 'refund required';
            } else {
                $statusRefund = 'none';
            }

            BookingReschedule::create([
                'fk_booking_detail_id' => $detail->id,
                'old_date' => $detail->play_date,
                'reason' => $data['reason'],
                'status_refund' => $statusRefund,
            ]);

            $detail->update([
                'play_date' => $data['new_play_date'],
                'start_play_time' => $data['new_start_time'],
                'end_play_time' => $data['new_end_time'],
                'price' => $data['new_price'],
                'status' => BookingDetailStatus::RESCHEDULE->value,
            ]);

            if ($data['financial_action'] === 'Lunas' && ($data['reconciled_amount'] ?? 0) > 0) {
                Payment::create([
                    'fk_booking_id' => $detail->fk_booking_id,
                    'fk_booking_detail_id' => $detail->id,
                    'reference_id' => 'RSC-'.strtoupper(Str::random(10)),
                    'payment_type' => PaymentType::FINAL_PAYMENT->value,
                    'method' => 'cash',
                    'amount' => $data['reconciled_amount'],
                    'status' => PaymentStatus::SUCCESS->value,
                    'paid_at' => now(),
                ]);
            }
        });
    }
}
