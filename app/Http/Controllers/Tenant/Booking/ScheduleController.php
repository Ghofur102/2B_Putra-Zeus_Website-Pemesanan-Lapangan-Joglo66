<?php

namespace App\Http\Controllers\Tenant\Booking;

use App\Http\Controllers\Controller;
use App\Models\FieldPrice;
use App\Models\BookingDetail;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ScheduleController extends Controller
{
    public function fetchSlots(Request $request)
    {
        $fieldId = $request->query('field_id');
        $date = $request->query('date'); // Format: Y-m-d

        $dayName = strtolower(Carbon::parse($date)->format('l'));

        $fieldPrices = FieldPrice::where('fk_field_id', $fieldId)
            ->where('day_type', $dayName)
            ->get();

        // 1. Ambil data Booking yang sudah terisi (Abaikan yang batal/gagal/expired)
        $bookedSlots = BookingDetail::whereHas('booking', function ($query) use ($fieldId) {
                $query->where('fk_field_id', $fieldId);
            })
            ->whereDate('play_date', $date)
            ->whereNotIn('status', ['cancelled', 'failed', 'expired'])
            ->get(['start_play_time', 'end_play_time']);

        // 2. Ambil data Tutup Lapangan (Field Closures)
        $closures = [];
        if (Schema::connection('mysql_joglo66_app')->hasTable('field_closures')) {
            $startOfDay = $date . ' 00:00:00';
            $endOfDay = $date . ' 23:59:59';

            $closures = DB::connection('mysql_joglo66_app')->table('field_closures')
                ->where('fk_field_id', $fieldId)
                ->where('field_closure_start_time', '<=', $endOfDay)
                ->where('field_closure_end_time', '>=', $startOfDay)
                ->get();
        }

        $slots = [];
        $startHour = 6;
        $endHour = 23;

        for ($i = $startHour; $i < $endHour; $i++) {
            $jam = sprintf('%02d:00', $i);
            $jamAkhir = sprintf('%02d:00', $i + 1);

            $startTimeFull = $jam . ':00';
            $endTimeFull = $jamAkhir . ':00';

            $slotStartDT = $date . ' ' . $startTimeFull;
            $slotEndDT = $date . ' ' . $endTimeFull;

            // Cek apakah Dipesan Orang Lain
            $isBooked = $bookedSlots->contains(function ($booked) use ($startTimeFull, $endTimeFull) {
                return $startTimeFull < $booked->end_play_time && $endTimeFull > $booked->start_play_time;
            });

            // Cek apakah Ditutup (Field Closure)
            $isClosed = false;
            foreach ($closures as $closure) {
                if ($slotStartDT < $closure->field_closure_end_time && $slotEndDT > $closure->field_closure_start_time) {
                    $isClosed = true;
                    break;
                }
            }

            // Dapatkan Harga
            $matchedPrice = $fieldPrices->first(function ($price) use ($startTimeFull, $endTimeFull) {
                return $startTimeFull >= $price->start_time && $endTimeFull <= $price->end_time;
            });

            $harga = $matchedPrice ? $matchedPrice->price : 0;

            // Tentukan Status Final
            $status = 'kosong';
            if (!$matchedPrice) {
                $status = 'tutup'; // Di luar jam operasional harga
            } elseif ($isClosed) {
                $status = 'tutup';
            } elseif ($isBooked) {
                $status = 'terisi';
            }

            if ($matchedPrice) {
                $slots[] = [
                    'jam' => $jam,
                    'jam_akhir' => $jamAkhir,
                    'status' => $status,
                    'harga' => $harga
                ];
            }
        }

        return response()->json(['slots' => $slots]);
    }
}
