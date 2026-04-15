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
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class FieldController extends Controller
{
    // GET: /api/admin/list-field (Zami)
    public function index(Request $request): JsonResponse
    {
         try {
            $search = $request->search;
            $limit = $request->limit ?? 20;

            // Query ALL fields (lapangan), not bookings!
            $query = Field::query();

            // Apply search filter if provided
            if ($search) {
                $query->where('name', 'LIKE', "%{$search}%")
                      ->orWhere('category', 'LIKE', "%{$search}%");
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
    public function show($field_id)
    {
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
    // Tip: Akan lebih RESTful jika URL-nya /api/admin/update-field/{field_id}
    public function update(Request $request)
    {
         $validated = $request->validate([
            'id' => 'required|exists:fields,id',
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'image_url' => 'sometimes|url',
            'category' => 'sometimes|string|max:100',
        ]);

        $field = Field::findOrFail($validated['id']);
        Gate::authorize('update', $field);

        $field->update(array_filter($validated, fn($key) => $key !== 'id', ARRAY_FILTER_USE_KEY));

        return response()->json([
            'status' => 'success',
            'message' => 'Field updated successfully',
            'field' => $field->fresh(),
        ]);
    }

    // GET: /api/admin/check-slot-availability/{field_id}/{date} (Huda)
    public function checkAvailability(Request $request, int $field_id, string $date)
    {
        $request->validate([
            'date' => 'required|date_format:Y-m-d',
        ]);

        Gate::authorize('viewAny', Field::class);

        $field = Field::findOrFail($field_id);

        Gate::authorize('view', $field);

        $occupied = BookingDetail::where('fk_field_id', $field_id)
            ->where('play_date', $date)
            ->whereNotIn('status', ['cancelled', 'field closure'])
            ->get(['start_play_time', 'end_play_time']);

        // Assume operating hours 06:00 to 24:00, 1-hour slots
        $startHour = 6;
        $endHour = 24;
        $availableSlots = [];

        for ($hour = $startHour; $hour < $endHour; $hour++) {
            $slotStart = sprintf('%02d:00:00', $hour);
            $slotEnd = sprintf('%02d:00:00', $hour + 1);
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

            if (!$isOccupied) {
                $availableSlots[] = [
                    'start' => $slotStart,
                    'end' => $slotEnd,
                ];
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

        Gate::authorize('create', [FieldClosure::class, $validatedData['fk_field_id']]);

        $addDateFieldClosure = FieldClosure::create([
            'fk_user_id' => Auth::id(),
            'fk_field_id' => $validatedData['fk_field_id'],
            'field_closure_start_time' => $validatedData['field_closure_start_time'],
            'field_closure_end_time' => $validatedData['field_closure_end_time'],
            'reason' => $validatedData['reason'],
        ]);

        // Update overlapping bookings
        BookingDetail::where('fk_field_id', $validatedData['fk_field_id'])

            ->whereRaw('TIMESTAMP(play_date, start_play_time) < ? && TIMESTAMP(play_date, end_play_time) > ?', [
                $validatedData['field_closure_end_time'],
                $validatedData['field_closure_start_time'],
            ])
            ->update(['status' => 'field closure']);

        $affectedBookings = BookingDetail::where('fk_field_id', $validatedData['fk_field_id'])
            ->whereRaw('TIMESTAMP(play_date, start_play_time) < ? && TIMESTAMP(play_date, end_play_time) > ?', [
                $validatedData['field_closure_end_time'],
                $validatedData['field_closure_start_time'],
            ])
            ->with('booking')
            ->get();

        return response()->json([
            'status' => 'success',
            'data_field_closure' => $addDateFieldClosure,
            'affected_bookings' => $affectedBookings,
        ]);
    }
}
