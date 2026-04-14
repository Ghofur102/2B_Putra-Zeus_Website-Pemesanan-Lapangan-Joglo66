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

            // ============================================
            // HITUNG SLOT TERISI (Booking yang ACTIVE)
            // ============================================
            // Ambil semua booking_details yang:
            // 1. Status = 'active' (sudah terkonfirmasi)
            // 2. play_date = hari ini
            // 3. Dari field yang sama
            $slotTerisi = BookingDetail::where('status', 'active')
                ->whereDate('play_date', $today)
                ->whereHas('booking', function ($query) use ($field) {
                    $query->where('fk_field_id', $field->id);
                })
                ->count();

            // ============================================
            // HITUNG TOTAL SLOT TERSEDIA
            // ============================================
            // Jumlah slot yang tersedia untuk hari ini
            // 1 slot = 1 jam (misal: 08:00-09:00, 09:00-10:00, dst)
            $totalSlot = FieldPrice::where('fk_field_id', $field->id)
                ->where('day_type', $dayType)
                ->count();

            // Default jika belum ada field_prices
            if ($totalSlot === 0) {
                $totalSlot = 1; // Minimal 1 slot
            }

            // ============================================
            // HITUNG TOTAL BOOKING HARI INI
            // ============================================
            // Jumlah DISTINCT bookings (order) yang masuk hari ini
            // 1 booking bisa punya multiple booking_details (multiple slots)
            $totalBooking = Booking::where('fk_field_id', $field->id)
                ->whereHas('details', function ($query) use ($today) {
                    $query->whereDate('play_date', $today)
                          ->where('status', '!=', 'cancelled');
                })
                ->count();

            // ============================================
            // HITUNG SLOT KOSONG
            // ============================================
            $slotKosong = max(0, $totalSlot - $slotTerisi);

            return response()->json([
                'success' => true,
                'message' => 'Dashboard data retrieved successfully',
                'data' => [
                    'name' => $field->name ?? 'Joglo66',
                    'slotTerisi' => $slotTerisi,
                    'totalSlot' => $totalSlot,
                    'slotKosong' => $slotKosong,
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
