<?php

namespace App\Http\Controllers\Admin;

use App\Models\Field;
use App\Models\BookingDetail;
use App\Models\FieldPrice;
use App\Models\FieldClosure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;

class FieldController extends Controller
{
    // Helper method untuk memvalidasi hak akses worker ke suatu lapangan
    private function checkFieldAccess($user, $fieldId): bool
    {
        if ($user && $user->role === 'worker') {
            return DB::table('field_admins')
                ->where('fk_user_id', $user->id)
                ->where('fk_field_id', $fieldId)
                ->exists();
        }
        return true;
    }

    // GET: /api/admin/list-field (Zami)
    public function index(Request $request): JsonResponse
    {
         try {
            $search = $request->search;
            $limit = $request->limit ?? 20;
            $user = $request->user();

            $query = Field::query();

            // 1. FILTER BERDASARKAN HAK AKSES WORKER
            if ($user && $user->role === 'worker') {
                $query->whereIn('id', function($q) use ($user) {
                    $q->select('fk_field_id')
                      ->from('field_admins')
                      ->where('fk_user_id', $user->id);
                });
            }

            // Apply search filter if provided
            if ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                      ->orWhere('category', 'LIKE', "%{$search}%");
                });
            }

            // Fetch fields
            $fields = $query->limit($limit)->get();

            // Format response
            $fieldsList = $fields->map(function ($field) {
                return [
                    'id' => $field->id,
                    'name' => $field->name,
                    'category' => $field->category,
                    'location' => $field->location ?? 'N/A',
                    'price' => $field->price ?? 0,
                    'image' => $field->image ?? null,
                    'status' => $field->status ?? 'active'
                ];
            })->toArray();

            return response()->json([
                'success' => true,
                'message' => 'Field list retrieved successfully',
                'data' => $fieldsList
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Internal server error: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    // GET: /api/admin/detail-field/{field_id} (Ghofur)
    public function show(Request $request, $field_id)
    {
        $user = $request->user();

        // 1. Validasi Hak Akses
        if (!$this->checkFieldAccess($user, $field_id)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Forbidden. Anda tidak memiliki akses ke lapangan ini.',
                'data' => null
            ], 403);
        }

        $field = Field::with('fieldPrices')->find($field_id);

        if (!$field) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data lapangan tidak ditemukan.',
                'data' => null
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Detail lapangan berhasil diambil.',
            'data' => $field
        ], 200);
    }

    // POST/PUT: /api/admin/update-field (Huda)
    public function update(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|exists:fields,id',
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'category' => 'sometimes|string|max:100',
            // --- VALIDASI UNTUK FILE GAMBAR ASLI ---
            'image' => 'sometimes|image|mimes:jpeg,png,jpg|max:2048',
            'pricing_rules' => 'sometimes', // Validasi mendalam akan dilakukan di bawah
        ]);

        $user = $request->user();

        if (!$this->checkFieldAccess($user, $validated['id'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Forbidden. Anda tidak memiliki akses untuk mengupdate lapangan ini.',
            ], 403);
        }

        $field = Field::findOrFail($validated['id']);

        try {
            DB::transaction(function () use ($field, $request, $validated) {
                // 1. Update Data Teks
                $fieldData = array_intersect_key($validated, array_flip(['name', 'description', 'category']));

                // 2. PROSES UPLOAD GAMBAR FISIK
                if ($request->hasFile('image')) {

                    // Cek apakah lapangan sebelumnya sudah punya gambar
                    if (!empty($field->image_url)) {
                        $oldImagePath = str_replace('storage/', '', $field->image_url);

                        // Jika file fisiknya benar-benar ada di dalam folder, maka hapus!
                        if (Storage::disk('public')->exists($oldImagePath)) {
                            Storage::disk('public')->delete($oldImagePath);
                        }
                    }
                    // --------------------------------------

                    // Simpan gambar baru ke folder storage/app/public/fields
                    $imagePath = $request->file('image')->store('fields', 'public');
                    // Buat path relatif untuk disimpan ke database
                    $fieldData['image_url'] = 'storage/' . $imagePath;
                }

                if (!empty($fieldData)) {
                    $field->update($fieldData);
                }

                // 3. PROSES PRICING RULES (Decode dari JSON String)
                if ($request->has('pricing_rules')) {
                    $rules = is_string($request->pricing_rules)
                        ? json_decode($request->pricing_rules, true)
                        : $request->pricing_rules;

                    if ($this->hasPricingOverlaps($rules)) {
                        throw new \Exception("Terdapat jadwal harga yang bentrok pada hari yang sama.");
                    }

                    FieldPrice::where('fk_field_id', $field->id)->delete();

                    foreach ($rules as $rule) {
                        FieldPrice::create([
                            'fk_field_id' => $field->id,
                            'day_type'    => $rule['day_type'],
                            'start_time'  => $rule['start_time'],
                            'end_time'    => $rule['end_time'],
                            'price'       => $rule['price'],
                        ]);
                    }
                }
            });

            return response()->json([
                'status' => 'success',
                'message' => 'Field and pricing rules updated successfully',
                'field' => $field->fresh(['fieldPrices']),
            ]);

        } catch (\Exception $e) {
             return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    private function hasPricingOverlaps(array $rules): bool
    {
        $groupedByDay = collect($rules)->groupBy('day_type');

        foreach ($groupedByDay as $day => $dayRules) {
            $count = count($dayRules);
            for ($i = 0; $i < $count; $i++) {
                for ($j = $i + 1; $j < $count; $j++) {
                    $startA = $dayRules[$i]['start_time'];
                    $endA = $dayRules[$i]['end_time'];
                    $startB = $dayRules[$j]['start_time'];
                    $endB = $dayRules[$j]['end_time'];

                    if ($startA < $endB && $endA > $startB) {
                        return true;
                    }
                }
            }
        }
        return false;
    }

   // GET: /api/admin/check-slot-availability/{field_id}/{date} (Huda)
    public function checkAvailability(Request $request, int $field_id, string $date)
    {
        $request->merge(['date' => $date]);

        $request->validate([
            'date' => 'required|date_format:Y-m-d',
        ]);

        $user = $request->user();

        if (!$this->checkFieldAccess($user, $field_id)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Forbidden. Anda tidak memiliki akses ke lapangan ini.',
            ], 403);
        }

        $dayName = strtolower(Carbon::parse($date)->englishDayOfWeek);

        $fieldPrices = FieldPrice::where('fk_field_id', $field_id)
            ->where('day_type', $dayName)
            ->get();

        $occupied = BookingDetail::whereHas('booking', function ($query) use ($field_id) {
                $query->where('fk_field_id', $field_id);
            })
            ->where('play_date', $date)
            ->whereNotIn('status', ['cancelled', 'field closure'])
            ->get(['start_play_time', 'end_play_time']);

        $availableSlots = [];

        foreach ($fieldPrices as $pricing) {
            $start = Carbon::parse($pricing->start_time);
            $end = Carbon::parse($pricing->end_time);

            $current = $start->copy();

            while ($current < $end) {
                $slotStart = $current->format('H:i:s');
                $nextHour = $current->copy()->addHour();
                $slotEnd = $nextHour->format('H:i:s');

                if ($nextHour > $end) break;

                $isOccupied = false;
                foreach ($occupied as $booking) {
                    if (
                        ($slotStart >= $booking->start_play_time && $slotStart < $booking->end_play_time) ||
                        ($slotEnd > $booking->start_play_time && $slotEnd <= $booking->end_play_time) ||
                        ($slotStart <= $booking->start_play_time && $slotEnd >= $booking->end_play_time)
                    ) {
                        $isOccupied = true;
                        break;
                    }
                }

                $availableSlots[] = [
                    'start' => $slotStart,
                    'end' => $slotEnd,
                    'price' => $pricing->price,
                    'is_available' => !$isOccupied
                ];

                $current->addHour();
            }
        }

        return response()->json([
            'status' => 'success',
            'field_id' => $field_id,
            'date' => $date,
            'total_available_slots' => count($availableSlots),
            'available_slots' => $availableSlots,
        ]);
    }

    // POST: /api/admin/close-field (Huda)
    public function closeField(Request $request)
    {
         $validatedData = $request->validate([
            'fk_field_id' => ['required', 'integer', 'exists:fields,id'],
            'field_closure_start_time' => ['required', Rule::date()->format('Y-m-d H:i:s')->afterOrEqual(now())],
            'field_closure_end_time' => ['required', Rule::date()->format('Y-m-d H:i:s')->afterOrEqual(now()), 'after:field_closure_start_time'],
            'reason' => ['required', 'string', 'max:300'],
        ]);

        $user = $request->user();

        // 1. Validasi Hak Akses
        if (!$this->checkFieldAccess($user, $validatedData['fk_field_id'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Forbidden. Anda tidak memiliki akses untuk menutup lapangan ini.',
            ], 403);
        }

        $addDateFieldClosure = FieldClosure::create([
            'fk_user_id' => Auth::id(),
            'fk_field_id' => $validatedData['fk_field_id'],
            'field_closure_start_time' => $validatedData['field_closure_start_time'],
            'field_closure_end_time' => $validatedData['field_closure_end_time'],
            'reason' => $validatedData['reason'],
        ]);

        // Update overlapping bookings
        BookingDetail::whereHas('booking', function($query) use ($validatedData) {
                $query->where('fk_field_id', $validatedData['fk_field_id']);
            })
            ->whereRaw('TIMESTAMP(play_date, start_play_time) < ? && TIMESTAMP(play_date, end_play_time) > ?', [
                $validatedData['field_closure_end_time'],
                $validatedData['field_closure_start_time'],
            ])
            // --- KUNCI PENCEGAHAN BUG ---
            // Jangan ubah status yang memang sudah batal dari awal
            ->where('status', '!=', 'cancelled')
            // ----------------------------
            ->update(['status' => 'field closure']);

        // 2. Ambil data yang terdampak
        $affectedBookings = BookingDetail::whereHas('booking', function($query) use ($validatedData) {
                $query->where('fk_field_id', $validatedData['fk_field_id']);
            })
            ->whereRaw('TIMESTAMP(play_date, start_play_time) < ? && TIMESTAMP(play_date, end_play_time) > ?', [
                $validatedData['field_closure_end_time'],
                $validatedData['field_closure_start_time'],
            ])
            ->where('status', 'field closure') // Hanya kembalikan yang benar-benar tertimpa
            ->with('booking.user')
            ->get();

        return response()->json([
            'status' => 'success',
            'data_field_closure' => $addDateFieldClosure,
            'affected_bookings' => $affectedBookings,
        ]);
    }
}
