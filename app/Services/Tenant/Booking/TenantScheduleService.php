<?php

namespace App\Services\Tenant\Booking;

use App\Models\FieldPrice;
use App\Models\BookingDetail;
use App\Enums\BookingDetailStatus;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TenantScheduleService
{
    private const DB_CONN = 'mysql_joglo66_app';
    private const TABLE_CLOSURES = 'field_closures';
    private const STATUS_TUTUP = 'tutup';
    private const STATUS_TERISI = 'terisi';
    private const STATUS_KOSONG = 'kosong';

    public function getAvailableHourlySlots(int $fieldId, string $date): array
    {
        $dayName = strtolower(Carbon::parse($date)->format('l'));

        $fieldPrices = FieldPrice::query()
            ->where('fk_field_id', $fieldId)
            ->where('day_type', $dayName)
            ->get();

        $bookedSlots = BookingDetail::query()
            ->whereHas('booking', function ($query) use ($fieldId) {
                /** @var \Illuminate\Database\Eloquent\Builder $query */
                $query->where('fk_field_id', $fieldId);
            })
            ->whereDate('play_date', $date)
            ->whereNotIn('status', [BookingDetailStatus::CANCELLED->value, 'failed', 'expired'])
            ->get(['start_play_time', 'end_play_time']);

        $closures = $this->getFieldClosuresFromDatabase($date, $fieldId);

        $slots = [];
        $startHour = 6;
        $endHour = 23;

        for ($hour = $startHour; $hour < $endHour; $hour++) {
            $slotData = $this->calculateSingleSlotMetrics($hour, $date, $fieldPrices, $bookedSlots, $closures);
            if ($slotData !== null) {
                $slots[] = $slotData;
            }
        }

        return $slots;
    }

    private function calculateSingleSlotMetrics(int $hour, string $date, $fieldPrices, $bookedSlots, array $closures): ?array
    {
        $jam = sprintf('%02d:00', $hour);
        $jamAkhir = sprintf('%02d:00', $hour + 1);

        $startTimeFull = $jam . ':00';
        $endTimeFull = $jamAkhir . ':00';

        $matchedPrice = $fieldPrices->first(function ($price) use ($startTimeFull, $endTimeFull) {
            /** @var FieldPrice $price */
            return $startTimeFull >= $price->start_time && $endTimeFull <= $price->end_time;
        });

        if (!$matchedPrice) {
            return null;
        }

        $isBooked = $bookedSlots->contains(function ($booked) use ($startTimeFull, $endTimeFull) {
            /** @var BookingDetail $booked */
            return $startTimeFull < $booked->end_play_time && $endTimeFull > $booked->start_play_time;
        });

        $slotStartDT = $date . ' ' . $startTimeFull;
        $slotEndDT = $date . ' ' . $endTimeFull;
        $isClosed = $this->isSlotOverlappingWithClosures($slotStartDT, $slotEndDT, $closures);

        $status = self::STATUS_KOSONG;
        if ($isClosed) {
            $status = self::STATUS_TUTUP;
        } elseif ($isBooked) {
            $status = self::STATUS_TERISI;
        }

        return [
            'jam'       => $jam,
            'jam_akhir' => $jamAkhir,
            'status'    => $status,
            'harga'     => (int)$matchedPrice->price
        ];
    }

    private function getFieldClosuresFromDatabase(string $date, int $fieldId): array
    {
        $closures = [];
        if (Schema::connection(self::DB_CONN)->hasTable(self::TABLE_CLOSURES)) {
            $startOfDay = $date . ' 00:00:00';
            $endOfDay = $date . ' 23:59:59';

            $closures = DB::connection(self::DB_CONN)->table(self::TABLE_CLOSURES)
                ->where('fk_field_id', $fieldId)
                ->where('field_closure_start_time', '<=', $endOfDay)
                ->where('field_closure_end_time', '>=', $startOfDay)
                ->get()
                ->toArray();
        }
        return $closures;
    }

    private function isSlotOverlappingWithClosures(string $slotStartDT, string $slotEndDT, array $closures): bool
    {
        $conflict = false;
        foreach ($closures as $closure) {
            if ($slotStartDT < $closure->field_closure_end_time && $slotEndDT > $closure->field_closure_start_time) {
                $conflict = true;
                break;
            }
        }
        return $conflict;
    }
}
