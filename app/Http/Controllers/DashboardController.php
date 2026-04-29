<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Field;
use App\Models\Booking;
use App\Models\BookingDetail;
use App\Models\FieldPrice;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB; // Pastikan ini ditambahkan

class DashboardController extends Controller
{
    // GET: /api/admin/dashboard
    public function dashboard(Request $request): JsonResponse
    {
        try {
            $fieldId = $request->field_id;
            $today = Carbon::now()->format('Y-m-d');
            $dayType = strtolower(Carbon::now()->englishDayOfWeek);
            $user = $request->user();

            // 1. Base Query untuk Lapangan
            $fieldQuery = Field::query();

            // 2. FILTER BERDASARKAN HAK AKSES WORKER (Tabel field_admins)
            if ($user && $user->role === 'worker') {
                $fieldQuery->whereIn('id', function($q) use ($user) {
                    $q->select('fk_field_id')
                      ->from('field_admins')
                      ->where('fk_user_id', $user->id);
                });
            }

            // 3. Menentukan Lapangan mana yang akan ditampilkan datanya
            if ($fieldId) {
                // Jika request meminta field tertentu, pastikan field tersebut ada di dalam hak aksesnya
                $field = $fieldQuery->where('id', $fieldId)->first();

                if (!$field) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Anda tidak memiliki hak akses ke lapangan ini atau lapangan tidak ditemukan.',
                        'data' => null
                    ], 403);
                }
            } else {
                // Jika tidak ada field_id yang direquest, ambil lapangan PERTAMA yang boleh diakses worker
                $field = $fieldQuery->first();

                // Jika worker ternyata belum ditugaskan ke lapangan manapun oleh Super Admin
                if (!$field) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Anda belum ditugaskan ke lapangan manapun. Hubungi Admin.',
                        'data' => [
                            'name' => 'Belum Ada Lapangan',
                            'slotTerisi' => 0,
                            'totalSlot' => 0,
                            'slotKosong' => 0,
                            'totalBooking' => 0,
                        ]
                    ], 404);
                }
            }

            // ============================================
            // HITUNG SLOT TERISI (Booking yang ACTIVE)
            // ============================================
            $slotTerisi = BookingDetail::where('status', 'active')
                ->whereDate('play_date', $today)
                ->whereHas('booking', function ($query) use ($field) {
                    $query->where('fk_field_id', $field->id);
                })
                ->count();

            // ============================================
            // HITUNG TOTAL SLOT TERSEDIA
            // ============================================
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
