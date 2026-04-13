<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Gate;
use App\Models\Field;
use App\Models\Booking;
use App\Models\BookingDetail;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class BookingController extends Controller
{
    // GET: /api/admin/list-booking (Zami)
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

                    $bookingItem = [
                        'id' => $detail->id,
                        'date' => Carbon::parse($playDate)->format('d'),
                        'month' => Carbon::parse($playDate)->format('M'),
                        'year' => Carbon::parse($playDate)->format('Y'),
                        'title' => "{$booking->team_name} ({$booking->user->name})",
                        'time' => Carbon::parse($detail->start_play_time)->format('H.i') . ' - ' . Carbon::parse($detail->end_play_time)->format('H.i'),
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

    // POST: /api/admin/create-booking (Danil)
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'field_id' => ['required', 'integer', 'exists:fields,id'],
            'team_name' => ['required', 'string', 'max:50'],
            'booking_date' => ['required', 'date'],
            'details' => ['required', 'array', 'min:1'],
            'details.*.start_play_time' => ['required', 'date_format:H:i'],
            'details.*.end_play_time' => ['required', 'date_format:H:i'],
            'details.*.play_date' => ['required', 'date'],
            'details.*.price' => ['required', 'integer', 'min:0'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $payload = $validator->validated();
        $field = Field::find($payload['field_id']);
        $user = User::find($payload['user_id']);

        if (! $field || ! $user) {
            return response()->json([
                'success' => false,
                'message' => 'User or field not found.',
            ], 400);
        }

        $details = $payload['details'];
        $totalPrice = 0;
        $conflictDetail = null;

        foreach ($details as $index => $detail) {
            if ($detail['start_play_time'] >= $detail['end_play_time']) {
                return response()->json([
                    'success' => false,
                    'message' => "Detail #{$index} has invalid time range.",
                ], 400);
            }

            if ($this->hasFieldConflict($payload['field_id'], $detail)) {
                $conflictDetail = $detail;
                break;
            }

            if (! $this->validateFieldPrice($payload['field_id'], $detail)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Price validation failed for booking detail. Please use the active field price for the requested schedule.',
                ], 400);
            }

            $totalPrice += $detail['price'];
        }

        if ($conflictDetail) {
            return response()->json([
                'success' => false,
                'message' => 'Field is already booked for the requested time range.',
                'conflict' => $conflictDetail,
            ], 400);
        }

        try {
            $booking = DB::transaction(function () use ($payload, $details, $user, $field, &$totalPrice) {
                $booking = Booking::create([
                    'fk_user_id' => $payload['user_id'],
                    'fk_field_id' => $payload['field_id'],
                    'team_name' => $payload['team_name'],
                    'booking_date' => $payload['booking_date'],
                ]);

                foreach ($details as $detail) {
                    BookingDetail::create([
                        'fk_booking_id' => $booking->id,
                        'start_play_time' => $detail['start_play_time'],
                        'end_play_time' => $detail['end_play_time'],
                        'play_date' => $detail['play_date'],
                        'price' => $detail['price'],
                        'status' => 'waiting',
                    ]);
                }

                return $booking;
            });
        } catch (\Exception $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create booking. Please try again.',
                'error' => $exception->getMessage(),
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Booking created successfully.',
            'data' => [
                'booking_id' => $booking->id,
                'total_price' => $totalPrice,
            ],
        ], 201);
    }

    // GET: /api/admin/detail-booking/{detail_booking_id} (Ghofur)
    public function show($detail_booking_id)
    {
        // Menampilkan rincian pesanan, status bayar, dll
    }

    // POST/PUT: /api/admin/reschedule-booking/{detail_booking_id} (Ghofur)
    public function reschedule(Request $request, $detail_booking_id)
    {
        // Mengubah jadwal main (tanggal/jam) dari pesanan yang sudah ada
    }

    // POST/PUT: /api/admin/cancel-booking/{detail_booking_id} (Ghofur)
    public function cancel(Request $request, $detail_booking_id)
    {
        // Membatalkan pesanan (mengubah status menjadi dibatalkan)
    }

    private function hasFieldConflict(int $fieldId, array $detail): bool
    {
        return BookingDetail::query()
            ->whereHas('booking', function ($query) use ($fieldId) {
                $query->where('fk_field_id', $fieldId);
            })
            ->where('play_date', $detail['play_date'])
            ->where(function ($query) use ($detail) {
                $query->whereBetween('start_play_time', [$detail['start_play_time'], $detail['end_play_time']])
                    ->orWhereBetween('end_play_time', [$detail['start_play_time'], $detail['end_play_time']])
                    ->orWhere(function ($subQuery) use ($detail) {
                        $subQuery->where('start_play_time', '<=', $detail['start_play_time'])
                            ->where('end_play_time', '>=', $detail['end_play_time']);
                    });
            })
            ->exists();
    }

    private function validateFieldPrice(int $fieldId, array $detail): bool
    {
        $dayName = strtolower(date('l', strtotime($detail['play_date'])));

        $price = DB::table('field_prices')
            ->where('fk_field_id', $fieldId)
            ->where('day_type', $dayName)
            ->where('start_time', '<=', $detail['start_play_time'])
            ->where('end_time', '>=', $detail['end_play_time'])
            ->value('price');

        if (! is_null($price) && (int) $price !== (int) $detail['price']) {
            return false;
        }

        return true;
    }

    // GET: /api/admin/list-close-booking (Huda)
    public function closedBookings(Request $request)
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

    private function generateBookingDescription(string $fieldName, $startTime, $endTime): string
    {
        $start = Carbon::parse($startTime);
        $end = Carbon::parse($endTime);
        $duration = $end->diffInHours($start);

        return "Booking lapangan {$fieldName} dengan durasi {$duration} jam";
    }
}
