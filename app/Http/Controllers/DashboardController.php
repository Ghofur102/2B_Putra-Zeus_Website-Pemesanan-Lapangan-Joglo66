<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Field;
use App\Models\Booking;
use App\Models\BookingDetail;
use App\Models\FieldPrice;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class DashboardController extends Controller
{
    // GET: /api/admin/dashboard
    public function dashboard(Request $request): JsonResponse
    {
        try {
            $fieldId = $request->field_id;
            $fieldName = $request->field_name ?? 'mini soccer';
            $today = Carbon::now()->format('Y-m-d');
            $dayType = strtolower(Carbon::now()->englishDayOfWeek);

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
}
