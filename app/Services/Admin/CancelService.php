<?php

namespace App\Services\Admin;

use App\Enums\BookingDetailStatus;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Models\BookingCancelled;
use App\Models\BookingDetail;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CancelService
{
    public function execute(BookingDetail $detail, array $data): void
    {
        DB::transaction(function () use ($detail, $data) {
            BookingCancelled::create([
                'fk_booking_detail_id' => $detail->id,
                'cancle_date' => now()->toDateString(),
                'reason' => $data['reason'],
                'status_refund' => $data['status_refund'],
            ]);

            $detail->update([
                'status' => BookingDetailStatus::CANCELLED->value,
            ]);

            if ($data['status_refund'] !== 'None' && ($data['refund_amount'] ?? 0) > 0) {
                Payment::create([
                    'fk_booking_id' => $detail->fk_booking_id,
                    'fk_booking_detail_id' => $detail->id,
                    'reference_id' => 'CNL-REF-'.strtoupper(Str::random(10)),
                    'payment_type' => PaymentType::REFUND->value,
                    'method' => 'cash',
                    'amount' => $data['refund_amount'],
                    'status' => PaymentStatus::SUCCESS->value,
                    'paid_at' => now(),
                ]);
            }
        });
    }
}
