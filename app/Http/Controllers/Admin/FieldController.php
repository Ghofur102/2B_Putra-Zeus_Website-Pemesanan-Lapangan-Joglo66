<?php

namespace App\Http\Controllers\Admin;

use App\Models\Field;
use App\Models\BookingDetail;
use App\Models\FieldPrice;
use App\Models\FieldClosure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class FieldController extends Controller
{
    use \App\Http\Controllers\Traits\FieldAccessTrait;

    private const STR_SUCCESS = 'success';
    private const STR_ERROR = 'error';
    private const STR_WORKER = 'worker';
    private const STR_ACTIVE = 'active';
    private const STR_CANCELLED = 'cancelled';
    private const STR_FIELD_CLOSURE = 'field closure';
    private const TIME_FORMAT = 'H:i:s';

    // Solusi php:S1192 - Ekstraksi String Duplikat ke Konstanta Kelas
    private const MSG_INTERNAL_ERROR = 'Internal server error.';
    private const MSG_FORBIDDEN_FIELD = 'Forbidden. Anda tidak memiliki akses ke lapangan ini.';

    public function index(Request $request): JsonResponse
    {
        $status = 200;
        try {
            $search = $request->search;
            $limit = $request->limit ?? 20;
            $user = $request->user();

            $query = Field::query();

            if ($user && $user->role === self::STR_WORKER) {
                $query->whereIn('id', function($q) use ($user) {
                    $q->select('fk_field_id')
                      ->from('field_admins')
                      ->where('fk_user_id', $user->id);
                });
            }

            if ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                      ->orWhere('category', 'LIKE', "%{$search}%");
                });
            }

            $fields = $query->limit($limit)->get();

            $fieldsList = $fields->map(function ($field) {
                return [
                    'id' => $field->id,
                    'name' => $field->name,
                    'category' => $field->category,
                    'location' => $field->location ?? 'N/A',
                    'price' => $field->price ?? 0,
                    'image' => $field->image ?? null,
                    'status' => $field->status ?? self::STR_ACTIVE
                ];
            })->toArray();

            $data = [
                'success' => true,
                'message' => 'Field list retrieved successfully',
                'data' => $fieldsList
            ];
        } catch (Throwable $e) {
            $status = 500;
            $data = [
                'success' => false,
                'message' => 'Internal server error: ' . $e->getMessage(),
                'data' => null
            ];
        }
        return response()->json($data, $status);
    }

    public function show(Request $request, $field_id): JsonResponse
    {
        $status = 200;
        try {
            $user = $request->user();

            if (!$this->checkFieldAccess($user, $field_id)) {
                throw new HttpException(403, self::MSG_FORBIDDEN_FIELD);
            }

            $field = Field::with('fieldPrices')->find($field_id);

            if (!$field) {
                throw new HttpException(404, 'Data lapangan tidak ditemukan.');
            }

            $data = [
                'status' => self::STR_SUCCESS,
                'message' => 'Detail lapangan berhasil diambil.',
                'data' => $field
            ];
        } catch (HttpException $e) {
            $status = $e->getStatusCode();
            $data = ['status' => self::STR_ERROR, 'message' => $e->getMessage(), 'data' => null];
        } catch (Throwable $e) {
            $status = 500;
            $data = ['status' => self::STR_ERROR, 'message' => self::MSG_INTERNAL_ERROR];
        }
        return response()->json($data, $status);
    }

    // Solusi php:S3776 - Memangkas Cognitive Complexity fungsi Update dengan Sub-Fungsi Terpisah
    public function update(Request $request): JsonResponse
    {
        $status = 200;
        try {
            $validated = $request->validate([
                'id' => 'required|exists:fields,id',
                'name' => 'sometimes|string|max:255',
                'description' => 'sometimes|string',
                'category' => 'sometimes|string|max:100',
                'image' => 'sometimes|image|mimes:jpeg,png,jpg|max:2048',
                'pricing_rules' => 'sometimes',
            ]);

            $user = $request->user();

            if (!$this->checkFieldAccess($user, $validated['id'])) {
                throw new HttpException(403, 'Forbidden. Anda tidak memiliki akses untuk mengupdate lapangan ini.');
            }

            $field = Field::findOrFail($validated['id']);

            DB::transaction(function () use ($field, $request, $validated) {
                $this->handleFieldTextAndImageUpdate($field, $request, $validated);
                $this->handleFieldPricingRulesUpdate($field, $request);
            });

            $data = [
                'status' => self::STR_SUCCESS,
                'message' => 'Field and pricing rules updated successfully',
                'field' => $field->fresh(['fieldPrices']),
            ];
        } catch (HttpException $e) {
            $status = $e->getStatusCode();
            $data = ['status' => self::STR_ERROR, 'message' => $e->getMessage()];
        } catch (Throwable $e) {
            $status = 400;
            $data = ['status' => self::STR_ERROR, 'message' => $e->getMessage()];
        }
        return response()->json($data, $status);
    }

    public function checkAvailability(Request $request, int $field_id, string $date): JsonResponse
    {
        $status = 200;
        try {
            $request->merge(['date' => $date]);
            $request->validate(['date' => 'required|date_format:Y-m-d']);

            $user = $request->user();

            if (!$this->checkFieldAccess($user, $field_id)) {
                throw new HttpException(403, self::MSG_FORBIDDEN_FIELD);
            }

            $dayName = strtolower(Carbon::parse($date)->englishDayOfWeek);
            $fieldPrices = FieldPrice::where('fk_field_id', $field_id)->where('day_type', $dayName)->get();

            $occupied = BookingDetail::whereHas('booking', function ($query) use ($field_id) {
                    $query->where('fk_field_id', $field_id);
                })
                ->where('play_date', $date)
                ->whereNotIn('status', [self::STR_CANCELLED, self::STR_FIELD_CLOSURE])
                ->get(['start_play_time', 'end_play_time']);

            $availableSlots = [];

            foreach ($fieldPrices as $pricing) {
                $start = Carbon::parse($pricing->start_time);
                $end = Carbon::parse($pricing->end_time);
                $current = $start->copy();

                while ($current < $end) {
                    $slotStart = $current->format(self::TIME_FORMAT);
                    $nextHour = $current->copy()->addHour();
                    $slotEnd = $nextHour->format(self::TIME_FORMAT);

                    if ($nextHour > $end) {
                        break;
                    }

                    $availableSlots[] = [
                        'start' => $slotStart,
                        'end' => $slotEnd,
                        'price' => $pricing->price,
                        'is_available' => !$this->isSlotOccupied($slotStart, $slotEnd, $occupied)
                    ];

                    $current->addHour();
                }
            }

            $data = [
                'status' => self::STR_SUCCESS,
                'field_id' => $field_id,
                'date' => $date,
                'total_available_slots' => count($availableSlots),
                'available_slots' => $availableSlots,
            ];
        } catch (HttpException $e) {
            $status = $e->getStatusCode();
            $data = ['status' => self::STR_ERROR, 'message' => $e->getMessage()];
        } catch (Throwable $e) {
            $status = 500;
            $data = ['status' => self::STR_ERROR, 'message' => self::MSG_INTERNAL_ERROR];
        }
        return response()->json($data, $status);
    }

    public function closeField(Request $request): JsonResponse
    {
        $status = 200;
        try {
            $validatedData = $request->validate([
                'fk_field_id' => ['required', 'integer', 'exists:fields,id'],
                'field_closure_start_time' => ['required', Rule::date()->format('Y-m-d H:i:s')->afterOrEqual(now())],
                'field_closure_end_time' => ['required', Rule::date()->format('Y-m-d H:i:s')->afterOrEqual(now()), 'after:field_closure_start_time'],
                'reason' => ['required', 'string', 'max:300'],
            ]);

            $user = $request->user();

            if (!$this->checkFieldAccess($user, $validatedData['fk_field_id'])) {
                throw new HttpException(403, 'Forbidden. Anda tidak memiliki akses untuk menutup lapangan ini.');
            }

            $addDateFieldClosure = FieldClosure::create([
                'fk_user_id' => $user->id,
                'fk_field_id' => $validatedData['fk_field_id'],
                'field_closure_start_time' => $validatedData['field_closure_start_time'],
                'field_closure_end_time' => $validatedData['field_closure_end_time'],
                'reason' => $validatedData['reason'],
            ]);

            BookingDetail::whereHas('booking', function($query) use ($validatedData) {
                    $query->where('fk_field_id', $validatedData['fk_field_id']);
                })
                ->whereRaw('TIMESTAMP(play_date, start_play_time) < ? && TIMESTAMP(play_date, end_play_time) > ?', [
                    $validatedData['field_closure_end_time'],
                    $validatedData['field_closure_start_time'],
                ])
                ->where('status', '!=', self::STR_CANCELLED)
                ->update(['status' => self::STR_FIELD_CLOSURE]);

            $affectedBookings = BookingDetail::whereHas('booking', function($query) use ($validatedData) {
                    $query->where('fk_field_id', $validatedData['fk_field_id']);
                })
                ->whereRaw('TIMESTAMP(play_date, start_play_time) < ? && TIMESTAMP(play_date, end_play_time) > ?', [
                    $validatedData['field_closure_end_time'],
                    $validatedData['field_closure_start_time'],
                ])
                ->where('status', self::STR_FIELD_CLOSURE)
                ->with('booking.user')
                ->get();

            $data = [
                'status' => self::STR_SUCCESS,
                'data_field_closure' => $addDateFieldClosure,
                'affected_bookings' => $affectedBookings,
            ];
        } catch (HttpException $e) {
            $status = $e->getStatusCode();
            $data = ['status' => self::STR_ERROR, 'message' => $e->getMessage()];
        } catch (Throwable $e) {
            $status = 500;
            $data = ['status' => self::STR_ERROR, 'message' => self::MSG_INTERNAL_ERROR];
        }
        return response()->json($data, $status);
    }

    private function handleFieldTextAndImageUpdate(Field $field, Request $request, array $validated): void
    {
        $fieldData = array_intersect_key($validated, array_flip(['name', 'description', 'category']));

        if ($request->hasFile('image')) {
            $this->deleteOldFieldImage($field->image_url);
            $imagePath = $request->file('image')->store('fields', 'public');
            $fieldData['image_url'] = 'storage/' . $imagePath;
        }

        if (!empty($fieldData)) {
            $field->update($fieldData);
        }
    }

    private function deleteOldFieldImage(?string $imageUrl): void
    {
        if (empty($imageUrl)) {
            return;
        }

        $oldImagePath = str_replace('storage/', '', $imageUrl);
        if (Storage::disk('public')->exists($oldImagePath)) {
            Storage::disk('public')->delete($oldImagePath);
        }
    }

    private function handleFieldPricingRulesUpdate(Field $field, Request $request): void
    {
        if (!$request->has('pricing_rules')) {
            return;
        }

        $rules = is_string($request->pricing_rules)
            ? json_decode($request->pricing_rules, true)
            : $request->pricing_rules;

        if ($this->hasPricingOverlaps($rules)) {
            throw new HttpException(422, 'Terdapat jadwal harga yang bentrok pada hari yang sama.');
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

    private function hasPricingOverlaps(array $rules): bool
    {
        $groupedByDay = collect($rules)->groupBy('day_type');

        foreach ($groupedByDay as $dayRules) {
            $sortedRules = collect($dayRules)->sortBy('start_time')->values()->all();
            $count = count($sortedRules);

            for ($i = 0; $i < $count - 1; $i++) {
                if ($sortedRules[$i]['end_time'] > $sortedRules[$i + 1]['start_time']) {
                    return true;
                }
            }
        }
        return false;
    }

    private function isSlotOccupied(string $slotStart, string $slotEnd, $occupied): bool
    {
        foreach ($occupied as $booking) {
            $cond1 = ($slotStart >= $booking->start_play_time && $slotStart < $booking->end_play_time);
            $cond2 = ($slotEnd > $booking->start_play_time && $slotEnd <= $booking->end_play_time);
            $cond3 = ($slotStart <= $booking->start_play_time && $slotEnd >= $booking->end_play_time);

            if ($cond1 || $cond2 || $cond3) {
                return true;
            }
        }
        return false;
    }
}
