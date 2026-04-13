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

                    $duration = Carbon::parse($detail->start_play_time)->diffInHours(Carbon::parse($detail->end_play_time));

                    $bookingItem = [
                        'id' => $detail->id,
                        'date' => Carbon::parse($playDate)->format('d'),
                        'month' => Carbon::parse($playDate)->format('M'),
                        'year' => Carbon::parse($playDate)->format('Y'),
                        'title' => "{$booking->team_name} ({$booking->user->name})",
                        'time' => Carbon::parse($detail->start_play_time)->format('H.i') . ' - ' . Carbon::parse($detail->end_play_time)->format('H.i'),
                        'description' => "Booking lapangan {$field->name} dengan durasi {$duration} jam",
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
    // GET: /api/admin/detail-field/{field_id} (Ghofur)
    public function show($field_id)
    {
        // Menampilkan detail spesifik dari satu lapangan
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
