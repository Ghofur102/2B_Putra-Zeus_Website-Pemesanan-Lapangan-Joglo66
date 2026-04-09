<?php

namespace App\Http\Controllers;

use App\Models\Field;
use App\Models\Booking;
use App\Models\BookingDetail;
use App\Models\FieldPrice;
use App\Models\FieldClosure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ControllerZami extends Controller
{
    /**
     * GET /api/admin/dashboard
     * Ambil ringkasan dashboard untuk field mini soccer hari ini
     */
    public function dashboard(Request $request): JsonResponse
    {
        try {
            $fieldId = $request->field_id;
            $fieldName = $request->field_name ?? 'mini soccer';
            $today = Carbon::now()->format('Y-m-d');
            $dayType = $this->getDayType(Carbon::now());

            // Cari field: gunakan field_id jika ada, else cari category mini soccer
            $field = $fieldId 
                ? Field::find($fieldId)
                : Field::where('category', 'mini soccer')->first();

            if (!$field) {
                return response()->json([
                    'success' => false,
                    'message' => 'Field not found',
                    'data' => null
                ], 404);
            }

            // Hitung slotTerisi: booking_details active untuk hari ini
            $slotTerisi = BookingDetail::where('status', 'active')
                ->whereDate('play_date', $today)
                ->join('bookings', 'bookings.id', '=', 'booking_details.fk_booking_id')
                ->where('bookings.fk_field_id', $field->id)
                ->count();

            // Hitung totalSlot: field_prices untuk hari ini
            $totalSlot = FieldPrice::where('fk_field_id', $field->id)
                ->where('day_type', $dayType)
                ->count();

            // Hitung totalBooking: DISTINCT bookings untuk hari ini
            $totalBooking = Booking::where('fk_field_id', $field->id)
                ->whereHas('details', function ($query) use ($today) {
                    $query->whereDate('play_date', $today);
                })
                ->distinct()
                ->count('bookings.id');

            // Hitung slotKosong
            $slotKosong = $totalSlot - $slotTerisi;

            return response()->json([
                'success' => true,
                'message' => 'Dashboard data retrieved successfully',
                'data' => [
                    'name' => 'Joglo66',
                    'slotTerisi' => $slotTerisi,
                    'totalSlot' => $totalSlot,
                    'slotKosong' => max(0, $slotKosong), // Ensure non-negative
                    'totalBooking' => $totalBooking,
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Internal server error: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * GET /api/admin/list-booking
     * Ambil daftar booking hari ini dan mendatang
     */
    public function listBooking(Request $request): JsonResponse
    {
        try {
            $fieldId = $request->field_id;
            $search = $request->search;
            $date = $request->date;
            $limit = $request->limit ?? 20;
            $today = Carbon::now()->format('Y-m-d');

            // Default field: mini soccer
            $field = $fieldId 
                ? Field::find($fieldId)
                : Field::where('category', 'mini soccer')->first();

            if (!$field && $fieldId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Field not found',
                    'data' => null
                ], 404);
            }

            // Base query
            $query = Booking::with(['user', 'details'])
                ->where('fk_field_id', $field->id ?? NULL);

            // Apply search filter if provided
            if ($search) {
                $query->where('team_name', 'LIKE', "%{$search}%");
            }

            // Fetch bookings with booking_details
            $bookings = $query->get()->sortBy(function ($booking) {
                return $booking->details->min('play_date');
            });

            // Split into today and upcoming
            $todayBookings = [];
            $upcomingBookings = [];

            foreach ($bookings as $booking) {
                foreach ($booking->details as $detail) {
                    $playDate = $detail->play_date;
                    
                    // Skip if not matching specific date filter
                    if ($date && $playDate !== $date) {
                        continue;
                    }

                    $bookingItem = [
                        'id' => $detail->id,
                        'date' => Carbon::parse($playDate)->format('d'),
                        'month' => Carbon::parse($playDate)->format('M'),
                        'year' => Carbon::parse($playDate)->format('Y'),
                        'title' => "{$booking->team_name} ({$booking->user->name})",
                        'time' => $this->formatTimeWithDot($detail->start_play_time) . ' - ' . $this->formatTimeWithDot($detail->end_play_time),
                        'description' => $this->generateBookingDescription(
                            $field->name,
                            $detail->start_play_time,
                            $detail->end_play_time
                        ),
                        'status' => $detail->status
                    ];

                    if ($playDate === $today) {
                        $todayBookings[] = $bookingItem;
                    } else if ($playDate > $today) {
                        $upcomingBookings[] = $bookingItem;
                    }
                }
            }

            // Sort by time
            usort($todayBookings, function ($a, $b) {
                return strcmp($a['time'], $b['time']);
            });
            usort($upcomingBookings, function ($a, $b) {
                return strcmp($a['date'] . $a['time'], $b['date'] . $b['time']);
            });

            // Apply limit
            $todayBookings = array_slice($todayBookings, 0, $limit);
            $upcomingBookings = array_slice($upcomingBookings, 0, $limit);

            return response()->json([
                'success' => true,
                'message' => 'Booking list retrieved successfully',
                'data' => [
                    'today' => $todayBookings,
                    'upcoming' => $upcomingBookings
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Internal server error: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * GET /api/admin/list-field
     * Ambil daftar semua lapangan dengan status
     */
    public function listField(Request $request): JsonResponse
    {
        try {
            $limit = $request->limit ?? 20;
            $category = $request->category;

            // Query fields
            $query = Field::query();

            if ($category) {
                $query->where('category', $category);
            }

            $fields = $query->limit($limit)->orderBy('name')->get();

            $fieldsData = [];

            foreach ($fields as $field) {
                // Determine status: check field_closures
                $status = $this->getFieldStatus($field->id);

                // Get operating hours from field_prices
                $hours = $this->getFieldOperatingHours($field->id);

                $fieldsData[] = [
                    'id' => $field->id,
                    'nama' => $field->name,
                    'status' => $status,
                    'jam' => $hours,
                    'image_url' => $field->image_url ?? null
                ];
            }

            return response()->json([
                'success' => true,
                'message' => 'Field list retrieved successfully',
                'data' => $fieldsData
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Internal server error: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Helper: Get day type from date
     */
    private function getDayType(Carbon $date): string
    {
        $dayMap = [
            0 => 'sunday',
            1 => 'monday',
            2 => 'tuesday',
            3 => 'wednesday',
            4 => 'thursday',
            5 => 'friday',
            6 => 'saturday',
        ];

        return $dayMap[$date->dayOfWeek];
    }

    /**
     * Helper: Format time with dot (HH.MM)
     */
    private function formatTimeWithDot($time): string
    {
        return Carbon::parse($time)->format('H.i');
    }

    /**
     * Helper: Generate booking description
     */
    private function generateBookingDescription(string $fieldName, $startTime, $endTime): string
    {
        $start = Carbon::parse($startTime);
        $end = Carbon::parse($endTime);
        $duration = $end->diffInHours($start);

        return "Booking lapangan {$fieldName} dengan durasi {$duration} jam";
    }

    /**
     * Helper: Get field status
     */
    private function getFieldStatus(int $fieldId): string
    {
        $now = Carbon::now();
        
        $hasClosure = FieldClosure::where('fk_field_id', $fieldId)
            ->where('field_closure_start_time', '<=', $now)
            ->where('field_closure_end_time', '>=', $now)
            ->exists();

        return $hasClosure ? 'Maintenance' : 'Buka';
    }

    /**
     * Helper: Get field operating hours
     */
    private function getFieldOperatingHours(int $fieldId): string
    {
        $prices = FieldPrice::where('fk_field_id', $fieldId)
            ->select('start_time', 'end_time')
            ->orderBy('start_time')
            ->orderBy('end_time')
            ->get();

        if ($prices->isEmpty()) {
            return '00.00 - 00.00';
        }

        $minTime = $prices->min('start_time');
        $maxTime = $prices->max('end_time');

        $minFormatted = Carbon::parse($minTime)->format('H.i');
        $maxFormatted = Carbon::parse($maxTime)->format('H.i');

        return "{$minFormatted} - {$maxFormatted}";
    }
}
