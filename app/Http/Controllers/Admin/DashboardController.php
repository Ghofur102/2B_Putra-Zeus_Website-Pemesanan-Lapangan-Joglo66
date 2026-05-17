<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Models\Field;
use App\Models\Booking;
use App\Models\BookingDetail;
use App\Models\FieldPrice;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;
use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
// GET: /api/admin/dashboard
    public function dashboard(Request $request): JsonResponse
    {
        try {
            $fieldId = $request->field_id;
            $today = Carbon::now()->format('Y-m-d');
            $currentTime = Carbon::now()->format('H:i:s'); // Untuk cek waktu terlewat
            $dayType = strtolower(Carbon::now()->englishDayOfWeek);
            $user = $request->user();

            // 1. Base Query untuk Lapangan
            $fieldQuery = Field::query();

            // 2. FILTER BERDASARKAN HAK AKSES WORKER
            if ($user && $user->role === 'worker') {
                $fieldQuery->whereIn('id', function($q) use ($user) {
                    $q->select('fk_field_id')
                      ->from('field_admins')
                      ->where('fk_user_id', $user->id);
                });
            }

            // 3. Menentukan Lapangan mana yang akan ditampilkan datanya
            if ($fieldId) {
                $field = $fieldQuery->where('id', $fieldId)->first();
                if (!$field) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Anda tidak memiliki hak akses ke lapangan ini atau lapangan tidak ditemukan.',
                        'data' => null
                    ], 403);
                }
            } else {
                $field = $fieldQuery->first();
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
            // 4. AMBIL DATA HARGA/SLOT HARI INI
            // ============================================
            $fieldPrices = FieldPrice::where('fk_field_id', $field->id)
                ->where('day_type', $dayType)
                ->get();

            $allSlots = []; // Wadah untuk menyimpan semua jam yang ada
            $totalSlot = 0;

            // Hitung total jam operasional dari field_prices
            foreach ($fieldPrices as $price) {
                $start = Carbon::parse($price->start_time);
                $end = Carbon::parse($price->end_time);

                while ($start < $end) {
                    $allSlots[] = $start->format('H:i:s');
                    $start->addHour();
                    $totalSlot++;
                }
            }

            // ============================================
            // 5. AMBIL DATA BOOKING HARI INI
            // ============================================
            $validDetails = BookingDetail::whereHas('booking', function ($query) use ($field) {
                    $query->where('fk_field_id', $field->id);
                })
                ->whereDate('play_date', $today)
                // Hanya hitung yang aktif (bukan cancel/tutup)
                ->whereNotIn('status', ['cancelled', 'field closure'])
                ->get(['start_play_time', 'end_play_time', 'fk_booking_id']);

            $bookedSlots = []; // Menyimpan jam mana saja yang sudah dipesan
            $slotTerisi = 0;

            // Hitung jam yang terisi
            foreach ($validDetails as $detail) {
                $start = Carbon::parse($detail->start_play_time);
                $end = Carbon::parse($detail->end_play_time);

                while ($start < $end) {
                    $slotTime = $start->format('H:i:s');
                    if (!in_array($slotTime, $bookedSlots)) {
                        $bookedSlots[] = $slotTime;
                        $slotTerisi++;
                    }
                    $start->addHour();
                }
            }

            // ============================================
            // 6. HITUNG SLOT KOSONG (Total - Terisi - Terlewat)
            // ============================================
            $slotKosong = 0;
            foreach ($allSlots as $slot) {
                $isBooked = in_array($slot, $bookedSlots);
                $isPassed = $slot < $currentTime; // Jika jam di slot < jam sekarang

                // Hitung sebagai kosong JIKA belum dibooking DAN belum kelewat waktunya
                if (!$isBooked && !$isPassed) {
                    $slotKosong++;
                }
            }

            // ============================================
            // 7. HITUNG TOTAL BOOKING (Transaksi Unik)
            // ============================================
            // Menghitung berapa ID transaksi parent (Booking) unik yang terjadi hari ini
            $totalBooking = collect($validDetails)->pluck('fk_booking_id')->unique()->count();

            // Fallback jika lupa di setting di admin
            if ($totalSlot === 0) {
                $totalSlot = 0;
                $slotKosong = 0;
            }

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
