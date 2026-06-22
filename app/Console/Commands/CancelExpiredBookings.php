<?php

namespace App\Console\Commands;

use App\Enums\BookingDetailStatus;
use App\Enums\PaymentStatus;
use App\Models\BookingDetail;
use App\Models\Payment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CancelExpiredBookings extends Command
{
    protected $signature = 'booking:cancel-expired';

    protected $description = 'Otomatis membatalkan booking yang tidak dibayar dalam waktu 60 menit';

    public function handle()
    {
        $this->info('Memulai pengecekan booking kedaluwarsa...');

        $expiredPayments = Payment::query()
            ->where('status', PaymentStatus::PENDING->value ?? 'pending')
            ->where('created_at', '<=', now()->subMinutes(60))
            ->with('booking.details')
            ->get();

        if ($expiredPayments->isEmpty()) {
            $this->info('Tidak ada booking yang kedaluwarsa saat ini.');

            return Command::SUCCESS;
        }

        foreach ($expiredPayments as $payment) {
            $booking = $payment->booking;
            if (! $booking) {
                continue;
            }

            DB::transaction(function () use ($payment, $booking) {
                $payment->update([
                    'status' => PaymentStatus::FAILED->value ?? 'failed',
                ]);

                BookingDetail::query()
                    ->where('fk_booking_id', $booking->id)
                    ->update([
                        'status' => BookingDetailStatus::CANCELLED->value ?? 'cancelled',
                    ]);
            });

            $fieldId = $booking->fk_field_id;

            $uniqueDates = BookingDetail::query()
                ->where('fk_booking_id', $booking->id)
                ->pluck('play_date')
                ->unique();

            foreach ($uniqueDates as $date) {
                $cleanDate = \Carbon\Carbon::parse($date)->format('Y-m-d');
                $cacheKey = "tenant_slots_field_{$fieldId}_{$cleanDate}";
                Cache::forget($cacheKey);
                $this->info("Cache dibersihkan untuk kunci: {$cacheKey}");
            }

            Log::info("Sistem Otomatis: Booking #{$booking->id} dibatalkan karena melewati batas waktu pembayaran.");
            $this->info("Booking ID #{$booking->id} berhasil dibatalkan.");
        }

        $this->info('Proses pembatalan selesai.');

        return Command::SUCCESS;
    }
}
