<?php

namespace App\Http\Controllers\Tenant\Booking;

use App\Http\Controllers\Controller;
use App\Models\Field;
use App\Models\FieldPrice;
use App\Models\BookingDetail;
use App\Models\FieldClosure;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    /**
     * Redirect to consolidated booking form
     * The schedule and form are now consolidated in create-form page
     */
    public function index(Request $request)
    {
        // Get field_id from query param or redirect to dashboard
        $fieldId = $request->query('field_id');
        if (!$fieldId) {
            return redirect()->route('tenant.booking.dashboard')
                ->with('info', 'Silakan pilih lapangan terlebih dahulu');
        }

        // Redirect to consolidated create-form page
        return redirect()->route('tenant.booking.create-form', ['field_id' => $fieldId]);
    }

    /**
     * API endpoint to fetch available slots for a date
     */
    public function fetchSlots(Request $request)
    {
        $fieldId = $request->input('field_id');
        $date = $request->input('date'); // format: Y-m-d

        if (!$fieldId || !$date) {
            return response()->json(['error' => 'Invalid parameters'], 400);
        }

        try {
            $bookingDate = Carbon::createFromFormat('Y-m-d', $date);
            $field = Field::with('fieldPrices')->findOrFail($fieldId);

            // Get day type for pricing
            $dayName = strtolower($bookingDate->format('l'));
            $dayTypeMap = [
                'monday' => 'monday',
                'tuesday' => 'tuesday',
                'wednesday' => 'wednesday',
                'thursday' => 'thursday',
                'friday' => 'friday',
                'saturday' => 'saturday',
                'sunday' => 'sunday',
            ];
            $dayType = $dayTypeMap[$dayName];

            // Get field prices for this day
            $fieldPrices = $field->fieldPrices()
                ->where('day_type', $dayType)
                ->orderBy('start_time')
                ->get();

            if ($fieldPrices->isEmpty()) {
                return response()->json(['slots' => [], 'message' => 'Tidak ada harga untuk hari ini']);
            }

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

                    // Check if this slot is booked
                    $isBooked = BookingDetail::where('play_date', $bookingDate->format('Y-m-d'))
                        ->where('start_play_time', $slotStart)
                        ->where('end_play_time', $slotEnd)
                        ->whereIn('status', ['active', 'waiting'])
                        ->exists();

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

                    $status = $isClosed ? 'tutup' : ($isBooked ? 'terisi' : 'kosong');

                    $slots[] = [
                        'jam' => $slotStart,
                        'jam_akhir' => $slotEnd,
                        'status' => $status,
                        'harga' => $price->price,
                    ];
                }
            }

            return response()->json(['slots' => $slots]);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}
