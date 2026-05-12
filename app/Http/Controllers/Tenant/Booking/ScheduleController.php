<?php

namespace App\Http\Controllers\Tenant\Booking;

use App\Http\Controllers\Controller;
use App\Models\FieldPrice;
use App\Models\BookingDetail;
use App\Models\FieldClosure;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    public function fetchSlots(Request $request)
    {
        $fieldId = $request->query('field_id');
        $date = $request->query('date');

        $dayName = strtolower(Carbon::parse($date)->format('l'));

        $fieldPrices = FieldPrice::where('fk_field_id', $fieldId)
            ->where('day_type', $dayName)
            ->get();

        $bookedSlots = BookingDetail::whereHas('booking', function ($query) use ($fieldId) {
                $query->where('fk_field_id', $fieldId);
            })
            ->whereDate('play_date', $date)
            ->where('status', '!=', 'cancelled')
            ->get();

        $slots = [];
        $startHour = 6;
        $endHour = 23;

        for ($i = $startHour; $i < $endHour; $i++) {
            $jam = sprintf('%02d:00', $i);
            $jamAkhir = sprintf('%02d:00', $i + 1);

            $startTimeFull = $jam . ':00';
            $endTimeFull = $jamAkhir . ':00';

            $isBooked = $bookedSlots->contains(function ($booked) use ($startTimeFull, $endTimeFull) {
                return ($startTimeFull >= $booked->start_play_time && $startTimeFull < $booked->end_play_time) ||
                       ($endTimeFull > $booked->start_play_time && $endTimeFull <= $booked->end_play_time);
            });

            $status = $isBooked ? 'terisi' : 'kosong';
            $harga = 0;

            $matchedPrice = $fieldPrices->first(function ($price) use ($startTimeFull, $endTimeFull) {
                return $startTimeFull >= $price->start_time && $endTimeFull <= $price->end_time;
            });

            if ($matchedPrice) {
                $harga = $matchedPrice->price;
            } else {
                $status = 'tutup';
            }

            if ($status !== 'tutup') {
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

    /**
     * Generate available slots for a given field and date
     *
     * @param \Illuminate\Database\Eloquent\Collection $fieldPrices
     * @param int $fieldId
     * @param \Carbon\Carbon $bookingDate
     * @return array
     */
    private function generateAvailableSlots($fieldPrices, $fieldId, $bookingDate)
    {
        $slots = [];

        foreach ($fieldPrices as $price) {
            // Generate 1-hour slots
            // Parse time - handle both H:i and H:i:s formats
            $startTimeStr = substr($price->start_time, 0, 5); // Get HH:mm
            $endTimeStr = substr($price->end_time, 0, 5);

            $startTime = Carbon::createFromFormat('H:i', $startTimeStr);
            $endTime = Carbon::createFromFormat('H:i', $endTimeStr);

            while ($startTime < $endTime) {
                $slotStart = $startTime->format('H:i');
                $slotEnd = $startTime->addHour()->format('H:i');

                $status = $this->getSlotStatus(
                    $fieldId,
                    $bookingDate,
                    $slotStart,
                    $slotEnd
                );

                $slots[] = [
                    'jam' => $slotStart,
                    'jam_akhir' => $slotEnd,
                    'status' => $status,
                    'harga' => $price->price,
                ];
            }
        }

        return $slots;
    }

    /**
     * Determine the status of a slot (tutup, terisi, or kosong)
     *
     * @param int $fieldId
     * @param \Carbon\Carbon $bookingDate
     * @param string $slotStart
     * @param string $slotEnd
     * @return string
     */
    private function getSlotStatus($fieldId, $bookingDate, $slotStart, $slotEnd)
    {
        // Check if field is closed during this time
        $isClosed = FieldClosure::where('fk_field_id', $fieldId)
            ->where(function ($query) use ($bookingDate, $slotStart, $slotEnd) {
                // Parse slot times
                $slotStartDt = $bookingDate->clone()->setTimeFromTimeString($slotStart);
                $slotEndDt = $bookingDate->clone()->setTimeFromTimeString($slotEnd);

                // Check if closure overlaps with this slot
                $query->where('field_closure_start_time', '<', $slotEndDt)
                      ->where('field_closure_end_time', '>', $slotStartDt);
            })
            ->exists();

        if ($isClosed) {
            return 'tutup';
        }

        // Check if this slot is booked
        $isBooked = BookingDetail::where('play_date', $bookingDate->format('Y-m-d'))
            ->where('start_play_time', $slotStart)
            ->where('end_play_time', $slotEnd)
            ->whereIn('status', ['active', 'waiting'])
            ->exists();

        return $isBooked ? 'terisi' : 'kosong';
    }
}
