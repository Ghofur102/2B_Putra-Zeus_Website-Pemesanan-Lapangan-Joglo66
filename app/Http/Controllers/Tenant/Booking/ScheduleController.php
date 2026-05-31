<?php

namespace App\Http\Controllers\Tenant\Booking;

use App\Http\Controllers\Controller;
use App\Models\FieldPrice;
use App\Models\BookingDetail;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Http\JsonResponse;
use Throwable;

class ScheduleController extends Controller
{
    // Solusi php:S1192 - Ekstraksi Seluruh String Duplikat ke Konstanta Kelas
    private const DB_CONN = 'mysql_joglo66_app';
    private const TABLE_CLOSURES = 'field_closures';
    private const STATUS_TUTUP = 'tutup';
    private const STATUS_TERISI = 'terisi';
    private const STATUS_KOSONG = 'kosong';

    /**
     * Fetch available hourly slots for a specific field and date
     * Solusi php:S1142 - Menggunakan Single Exit Point Pattern
     */
    public function fetchSlots(Request $request): JsonResponse
    {
        $statusCode = 200;
        try {
            $fieldId = (int) $request->query('field_id');
            $date = $request->query('date');

            $dayName = strtolower(Carbon::parse($date)->format('l'));

            $fieldPrices = FieldPrice::where('fk_field_id', $fieldId)
                ->where('day_type', $dayName)
                ->get();

            $bookedSlots = BookingDetail::whereHas('booking', function ($query) use ($fieldId) {
                    $query->where('fk_field_id', $fieldId);
                })
                ->whereDate('play_date', $date)
                ->whereNotIn('status', ['cancelled', 'failed', 'expired'])
                ->get(['start_play_time', 'end_play_time']);

            $closures = $this->getFieldClosures($date, $fieldId);

            $slots = [];
            $startHour = 6;
            $endHour = 23;

            // Solusi php:S3776 - Memangkas Cognitive Complexity dengan Sub-Method Terpisah
            for ($i = $startHour; $i < $endHour; $i++) {
                $slotData = $this->processSingleSlot($i, $date, $fieldPrices, $bookedSlots, $closures);
                if ($slotData) {
                    $slots[] = $slotData;
                }
            }

            $data = ['slots' => $slots];
        } catch (Throwable $e) {
            $statusCode = 500;
            $data = [
                'success' => false,
                'message' => 'Gagal memuat jadwal slot: ' . $e->getMessage()
            ];
        }

        return response()->json($data, $statusCode);
    }

    /**
     * Private Helper: Memproses kalkulasi status dan harga untuk satu slot jam (php:S3776)
     */
    private function processSingleSlot(int $hour, string $date, $fieldPrices, $bookedSlots, array $closures): ?array
    {
        $jam = sprintf('%02d:00', $hour);
        $jamAkhir = sprintf('%02d:00', $hour + 1);

        $startTimeFull = $jam . ':00';
        $endTimeFull = $jamAkhir . ':00';

        $matchedPrice = $fieldPrices->first(function ($price) use ($startTimeFull, $endTimeFull) {
            return $startTimeFull >= $price->start_time && $endTimeFull <= $price->end_time;
        });

        if (!$matchedPrice) {
            return null;
        }

        $isBooked = $bookedSlots->contains(function ($booked) use ($startTimeFull, $endTimeFull) {
            return $startTimeFull < $booked->end_play_time && $endTimeFull > $booked->start_play_time;
        });

        $slotStartDT = $date . ' ' . $startTimeFull;
        $slotEndDT = $date . ' ' . $endTimeFull;
        $isClosed = $this->checkClosureConflict($slotStartDT, $slotEndDT, $closures);

        $status = self::STATUS_KOSONG;
        if ($isClosed) {
            $status = self::STATUS_TUTUP;
        } elseif ($isBooked) {
            $status = self::STATUS_TERISI;
        }

        return [
            'jam' => $jam,
            'jam_akhir' => $jamAkhir,
            'status' => $status,
            'harga' => $matchedPrice->price
        ];
    }

    /**
     * Private Helper: Mengambil data penutupan operasional lapangan dari database
     */
    private function getFieldClosures(string $date, int $fieldId): array
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

    /**
     * Private Helper: Memeriksa apakah slot jam menabrak jadwal penutupan lapangan (php:S121)
     */
    private function checkClosureConflict(string $slotStartDT, string $slotEndDT, array $closures): bool
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
