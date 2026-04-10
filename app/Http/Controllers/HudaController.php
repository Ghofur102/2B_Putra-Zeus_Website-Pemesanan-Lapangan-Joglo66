<?php

namespace App\Http\Controllers;

use App\Models\Field;
use App\Models\FieldClosure;
use App\Models\BookingDetail;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;


class HudaController extends Controller
{
    /**
     * Check slot availability for a field on specific date.
     */
    public function checkSlotAvailability(Request $request, int $field_id, string $date)
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

    /**
     * Update field details.
     */
    public function updateField(Request $request)
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

    /**
     * Close field (create field closure).
     */
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

    /**
     * List closed bookings.
     */
    public function listCloseBooking(Request $request)
    {
        Gate::authorize('viewAny', BookingDetail::class);

        $query = BookingDetail::where('status', 'field closure')
            ->with(['booking.user', 'field'])
            ->orderBy('play_date', 'desc')
            ->orderBy('start_play_time');

        if ($request->has('field_id')) {
            $query->where('fk_field_id', $request->field_id);
        }

        if ($request->has('date')) {
            $query->where('play_date', $request->date);
        }

        $closedBookings = $query->paginate(20);

        return response()->json([
            'status' => 'success',
            'closed_bookings' => $closedBookings,
        ]);
    }
}

