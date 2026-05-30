<?php

namespace App\Http\Controllers\Admin;

use App\Models\Field;
use App\Models\Booking;
use App\Models\BookingDetail;
use App\Models\BookingCancelled;
use App\Models\BookingReschedule;
use App\Models\Payment;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Http\Controllers\Controller;

class BookingController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $fieldId = $request->field_id;
            $search = $request->search;
            $startDate = $request->start_date;
            $endDate = $request->end_date;
            $limit = $request->limit ?? 20;
            $today = Carbon::now()->format('Y-m-d');
            $user = $request->user();

            $query = Booking::with(['user', 'details', 'payments']);

            if ($user && $user->role === 'worker') {
                $query->whereIn('fk_field_id', function($q) use ($user) {
                    $q->select('fk_field_id')
                      ->from('field_admins')
                      ->where('fk_user_id', $user->id);
                });
            }

            if ($fieldId) {
                $query->where('fk_field_id', $fieldId);
            }

            if ($search) {
                $query->where('team_name', 'LIKE', "%{$search}%");
            }

            $bookings = $query->get();
            $todayBookings = [];
            $upcomingBookings = [];

            foreach ($bookings as $booking) {
                $fieldName = Field::find($booking->fk_field_id)->name ?? 'Unknown Field';

                foreach ($booking->details as $detail) {
                    $playDate = $detail->play_date;

                    if ($startDate && $endDate) {
                        if ($playDate < $startDate || $playDate > $endDate) {
                            continue;
                        }
                    } elseif ($startDate) {
                        if ($playDate !== $startDate) {
                            continue;
                        }
                    }

                    $allPayments = $booking->payments->where('status', 'success');
                    $totalBookingPaid = $allPayments->whereIn('payment_type', ['down payment', 'final payment', 'reschedule fee'])->sum('amount');
                    $totalBookingRefund = $allPayments->where('payment_type', 'refund')->sum('amount');
                    $totalDetailsCount = $booking->details->count();

                    if ($totalDetailsCount == 1) {
                        $totalPaid = $totalBookingPaid - $totalBookingRefund;
                    } else {
                        $specificPaid = $allPayments->where('fk_booking_detail_id', $detail->id)->whereIn('payment_type', ['down payment', 'final payment', 'reschedule fee'])->sum('amount');
                        $specificRefund = $allPayments->where('fk_booking_detail_id', $detail->id)->where('payment_type', 'refund')->sum('amount');
                        $genericPaid = $allPayments->where('fk_booking_detail_id', null)->whereIn('payment_type', ['down payment', 'final payment'])->sum('amount');
                        
                        $totalPaid = ($specificPaid - $specificRefund) + ($genericPaid / $totalDetailsCount);
                    }

                    $remainingPayment = $detail->price - $totalPaid;
                    
                    $refundAmount = $booking->payments->where('status', 'success')
                        ->where('payment_type', 'refund')
                        ->where('fk_booking_detail_id', $detail->id)
                        ->sum('amount');

                    $bookingItem = [
                        'id' => $detail->id,
                        'sort_datetime' => $playDate . ' ' . $detail->start_play_time,
                        'date' => Carbon::parse($playDate)->format('d'),
                        'month' => Carbon::parse($playDate)->format('M'),
                        'year' => Carbon::parse($playDate)->format('Y'),
                        'title' => "{$booking->team_name}",
                        'tenant_name' => "{$booking->user->name}",
                        'time' => Carbon::parse($detail->start_play_time)->format('H:i') . ' - ' . Carbon::parse($detail->end_play_time)->format('H:i'),
                        'description' => $fieldName,
                        'price' => $detail->price,
                        'status' => $detail->status,
                        'total_paid' => $totalPaid,
                        'remaining_payment' => $remainingPayment,
                        'refund_amount' => $refundAmount
                    ];

                    if ($startDate || $endDate || $search) {
                        $todayBookings[] = $bookingItem;
                    } else {
                        if ($playDate === $today) {
                            $todayBookings[] = $bookingItem;
                        } else if ($playDate > $today) {
                            $upcomingBookings[] = $bookingItem;
                        }
                    }
                }
            }

            usort($todayBookings, function ($a, $b) {
                return strcmp($a['sort_datetime'], $b['sort_datetime']);
            });
            usort($upcomingBookings, function ($a, $b) {
                return strcmp($a['sort_datetime'], $b['sort_datetime']);
            });

            return response()->json([
                'success' => true,
                'message' => 'Booking list retrieved successfully',
                'data' => [
                    'today' => array_slice($todayBookings, 0, $limit),
                    'upcoming' => array_slice($upcomingBookings, 0, $limit)
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

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'field_id' => ['required', 'integer', 'exists:fields,id'],
            'team_name' => ['required', 'string', 'max:50'],
            'booking_date' => ['required', 'date'],
            'customer_phone' => ['nullable', 'string', 'max:20'],
            'customer_email' => ['nullable', 'email', 'max:100'],
            'notes' => ['nullable', 'string'],
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
        $userLogin = $request->user();

        if ($userLogin && $userLogin->role === 'worker') {
            $isAuthorized = DB::table('field_admins')
                ->where('fk_user_id', $userLogin->id)
                ->where('fk_field_id', $payload['field_id'])
                ->exists();

            if (!$isAuthorized) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki hak akses untuk membuat pesanan di lapangan ini.',
                ], 403);
            }
        }

        $field = Field::find($payload['field_id']);
        $user = User::find($payload['user_id']);

        if (!$field || !$user) {
            return response()->json([
                'success' => false,
                'message' => 'User or field not found.',
            ], 400);
        }

        $details = $payload['details'];
        $totalPrice = 0;
        $conflictDetail = null;

        $todayDate = Carbon::now()->format('Y-m-d');
        $currentTime = Carbon::now()->format('H:i');

        foreach ($details as $index => $detail) {
            if ($detail['start_play_time'] >= $detail['end_play_time']) {
                return response()->json([
                    'success' => false,
                    'message' => "Detail #{$index} has invalid time range.",
                ], 400);
            }

            if ($detail['play_date'] < $todayDate) {
                return response()->json([
                    'success' => false,
                    'message' => "Tidak dapat memesan untuk tanggal yang sudah lewat.",
                ], 400);
            }

            if ($detail['play_date'] === $todayDate && $detail['start_play_time'] <= $currentTime) {
                return response()->json([
                    'success' => false,
                    'message' => "Waktu main sudah terlewat untuk hari ini.",
                ], 400);
            }

            if ($this->hasFieldConflict($payload['field_id'], $detail)) {
                $conflictDetail = $detail;
                break;
            }

            if (!$this->validateFieldPrice($payload['field_id'], $detail)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Price validation failed for booking detail.',
                ], 400);
            }

            $totalPrice += $detail['price'];
        }

        if ($conflictDetail) {
            return response()->json([
                'success' => false,
                'message' => 'Field is already booked or closed for the requested time range.',
                'conflict' => $conflictDetail,
            ], 400);
        }

        try {
            $booking = DB::transaction(function () use ($payload, $details, &$totalPrice) {
                $booking = Booking::create([
                    'fk_user_id' => $payload['user_id'],
                    'fk_field_id' => $payload['field_id'],
                    'team_name' => $payload['team_name'],
                    'booking_date' => $payload['booking_date'],
                    'customer_phone' => $payload['customer_phone'] ?? null,
                    'customer_email' => $payload['customer_email'] ?? null,
                    'notes' => $payload['notes'] ?? null,
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

    public function show(Request $request, $detail_booking_id)
    {
        $user = $request->user();
        $detail = BookingDetail::with(['booking.field', 'booking.user', 'booking.payments', 'booking.details'])->find($detail_booking_id);

        if (!$detail) {
            return response()->json(['status' => 'error', 'message' => 'Booking detail not found.'], 404);
        }

        if ($user && $user->role === 'worker') {
            $isAuthorized = DB::table('field_admins')->where('fk_user_id', $user->id)->where('fk_field_id', $detail->booking->fk_field_id)->exists();
            if (!$isAuthorized) return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 403);
        }

        $start = Carbon::parse($detail->start_play_time);
        $end = Carbon::parse($detail->end_play_time);
        $duration = max(1, $start->diffInHours($end));

        $allPayments = $detail->booking->payments->where('status', 'success');
        $totalBookingPaid = $allPayments->whereIn('payment_type', ['down payment', 'final payment', 'reschedule fee'])->sum('amount');
        $totalBookingRefund = $allPayments->where('payment_type', 'refund')->sum('amount');
        $totalDetailsCount = $detail->booking->details->count();

        $sessions = $detail->booking->details->map(function ($item) use ($allPayments, $totalBookingPaid, $totalBookingRefund, $totalDetailsCount) {
            if ($totalDetailsCount == 1) {
                $sessionPaid = $totalBookingPaid - $totalBookingRefund;
            } else {
                $specificPaid = $allPayments->where('fk_booking_detail_id', $item->id)->whereIn('payment_type', ['down payment', 'final payment', 'reschedule fee'])->sum('amount');
                $specificRefund = $allPayments->where('fk_booking_detail_id', $item->id)->where('payment_type', 'refund')->sum('amount');
                $genericPaid = $allPayments->where('fk_booking_detail_id', null)->whereIn('payment_type', ['down payment', 'final payment'])->sum('amount');
                
                $sessionPaid = ($specificPaid - $specificRefund) + ($genericPaid / $totalDetailsCount);
            }

            return [
                'id' => $item->id,
                'play_date' => Carbon::parse($item->play_date)->format('d M Y'),
                'start_time' => Carbon::parse($item->start_play_time)->format('H:i'),
                'end_time' => Carbon::parse($item->end_play_time)->format('H:i'),
                'price' => (int)$item->price,
                'status' => $item->status,
                'total_paid' => (int)$sessionPaid,
                'remaining_payment' => (int)($item->price - $sessionPaid),
                'refund_amount' => (int)$allPayments->where('payment_type', 'refund')->where('fk_booking_detail_id', $item->id)->sum('amount')
            ];
        });

        // 1. Sisipkan Field Closures Secara Langsung Disini
        $closures = DB::table('field_closures')
            ->where('fk_field_id', $detail->booking->fk_field_id)
            ->get(['field_closure_start_time', 'field_closure_end_time']);

        $responseData = [
            'booking_id' => $detail->booking->id,
            'user_info' => [
                'name' => $detail->booking->team_name ?? 'Guest',
                'email' => $detail->booking->customer_email ?? '-',
                'phone' => $detail->booking->customer_phone ?? '-',
                'team_name' => $detail->booking->team_name ?? '-',
                'notes' => $detail->booking->notes ?? '-',
            ],
            'field_info' => [
                'id' => $detail->booking->fk_field_id,
                'name' => $detail->booking->field->name ?? 'Unknown Field',
                'category' => $detail->booking->field->category ?? 'Unknown Category',
                'image_url' => $detail->booking->field->image_url,
            ],
            'service_info' => [
                'duration' => $duration,
                'price_per_hour' => $detail->price / $duration,
                'total_price' => (int)$detail->booking->details->sum('price'),
                'total_down_payment' => (int)($totalBookingPaid - $totalBookingRefund),
            ],
            'payment_details' => [
                'total_price' => (int)$detail->booking->details->sum('price'),
                'total_paid' => (int)($totalBookingPaid - $totalBookingRefund),
                'payment_method' => $detail->booking->payments->last()->method ?? '-',
            ],
            'sessions' => $sessions,
            'field_closures' => $closures // Ditambahkan!
        ];

        return response()->json(['status' => 'success', 'data' => $responseData], 200);
    }

    public function reschedule(Request $request, $detail_booking_id)
    {
        $request->validate([
            'new_play_date' => 'required|date',
            'new_start_time' => 'required|date_format:H:i',
            'new_end_time' => 'required|date_format:H:i|after:new_start_time',
            'reason' => 'required|string',
            'fk_field_closure_id' => 'nullable|integer',
            'new_price' => 'nullable|integer|min:0',
        ]);

        $detail = BookingDetail::find($detail_booking_id);

        if (!$detail) {
            return response()->json(['status' => 'error', 'message' => 'Data booking tidak ditemukan.'], 404);
        }

        $isFromClosure = strtolower($detail->status) === 'field closure';

        if (!$isFromClosure) {
            $playDate = Carbon::parse($detail->play_date)->startOfDay();
            $today = Carbon::now()->startOfDay();
            $diffDays = $today->diffInDays($playDate, false);

            if ($diffDays < 3) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Reschedule ditolak: Jadwal main kurang dari H-3.'
                ], 400);
            }
        }

        // 2. Format Waktu Ketat
        $startFormatted = Carbon::parse($request->new_play_date . ' ' . $request->new_start_time)->format('Y-m-d H:i:s');
        $endFormatted = Carbon::parse($request->new_play_date . ' ' . $request->new_end_time)->format('Y-m-d H:i:s');

        // 3. Validasi Irisan Waktu Ketat (Overlap Range Formula: StartA < EndB AND EndA > StartB)
        $isClosed = DB::table('field_closures')
            ->where('fk_field_id', $detail->booking->fk_field_id)
            ->where('field_closure_start_time', '<', $endFormatted)
            ->where('field_closure_end_time', '>', $startFormatted)
            ->exists();

        if ($isClosed) {
            return response()->json([
                'status' => 'error',
                'message' => 'Reschedule ditolak: Lapangan sedang ditutup operasional (Field Closure) pada slot waktu pilihan Anda.'
            ], 400);
        }

        try {
            DB::transaction(function () use ($detail, $request, $isFromClosure) {
                BookingReschedule::create([
                    'fk_booking_detail_id' => $detail->id,
                    'fk_field_closure_id' => $request->fk_field_closure_id,
                    'old_date' => $detail->play_date, 
                    'reason' => $request->reason,
                ]);

                $updateData = [
                    'play_date' => $request->new_play_date, 
                    'start_play_time' => $request->new_start_time,
                    'end_play_time' => $request->new_end_time,
                    'status' => $isFromClosure ? 'closed field reschedule' : 'reschedule',
                ];

                if ($request->has('new_price')) {
                    $updateData['price'] = $request->new_price;
                }

                $detail->update($updateData);
            });

            return response()->json([
                'status' => 'success',
                'message' => 'Jadwal booking berhasil diubah.',
                'data' => BookingDetail::find($detail_booking_id)
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat mengubah jadwal: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function cancel(Request $request, $detail_booking_id)
    {
        $request->validate([
            'reason' => 'required|string',
            'status_refund' => 'nullable|string',
            'fk_field_closure_id' => 'nullable|integer',
        ]);

        $detail = BookingDetail::find($detail_booking_id);
        if (!$detail) return response()->json(['status' => 'error', 'message' => 'Data tidak ditemukan.'], 404);

        $currentStatus = strtolower($detail->status);
        if (str_contains($currentStatus, 'cancel')) {
            return response()->json(['status' => 'error', 'message' => 'Booking ini sudah dibatalkan.'], 400);
        }

        $statusRefund = ucfirst(strtolower($request->status_refund ?? 'None'));
        $isFromClosure = $currentStatus === 'field closure';

        if (!$isFromClosure) {
            $playDate = Carbon::parse($detail->play_date)->startOfDay();
            $diffDays = Carbon::now()->startOfDay()->diffInDays($playDate, false);
            if ($diffDays < 3) {
                $statusRefund = 'None';
            }
        }

        $refundAmount = 0;
        if ($statusRefund !== 'None') {
            $successfulPayments = Payment::where('fk_booking_id', $detail->fk_booking_id)
                ->where('status', 'success')
                ->whereIn('payment_type', ['down payment', 'final payment'])
                ->sum('amount');
            
            $refunded = Payment::where('fk_booking_detail_id', $detail->id)
                ->where('payment_type', 'refund')->sum('amount');

            $netPaid = $successfulPayments - $refunded;
            $refundAmount = ($statusRefund === 'Full') ? $netPaid : ($netPaid / 2);
        }

        try {
            DB::transaction(function () use ($detail, $request, $statusRefund, $isFromClosure, $refundAmount) {
                BookingCancelled::create([
                    'fk_booking_detail_id' => $detail->id,
                    'fk_field_closure_id' => $request->fk_field_closure_id,
                    'cancle_date' => Carbon::now()->toDateString(),
                    'reason' => $request->reason,
                    'status_refund' => $statusRefund,
                ]);

                $detail->update([
                    'status' => $isFromClosure ? 'closed field cancelled' : 'cancelled',
                ]);

                if ($refundAmount > 0) {
                    Payment::create([
                        'fk_booking_id' => $detail->fk_booking_id,
                        'fk_booking_detail_id' => $detail->id,
                        'reference_id' => 'CNL-REF-' . strtoupper(Str::random(10)),
                        'payment_type' => 'refund',
                        'method' => 'cash',
                        'amount' => $refundAmount,
                        'status' => 'success',
                        'paid_at' => now(),
                    ]);
                }
            });

            return response()->json(['status' => 'success', 'message' => 'Booking berhasil dibatalkan.'], 200);

        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Terjadi kesalahan: ' . $e->getMessage()], 500);
        }
    }

    private function hasFieldConflict(int $fieldId, array $detail): bool
    {
        $slotStart = Carbon::parse($detail['play_date'] . ' ' . $detail['start_play_time'])->format('Y-m-d H:i:s');
        $slotEnd = Carbon::parse($detail['play_date'] . ' ' . $detail['end_play_time'])->format('Y-m-d H:i:s');

        $isClosed = DB::table('field_closures')
            ->where('fk_field_id', $fieldId)
            ->where('field_closure_start_time', '<', $slotEnd)
            ->where('field_closure_end_time', '>', $slotStart)
            ->exists();

        if ($isClosed) return true;

        return BookingDetail::query()
            ->whereHas('booking', function ($query) use ($fieldId) {
                $query->where('fk_field_id', $fieldId);
            })
            ->where('play_date', $detail['play_date'])
            ->whereNotIn('status', ['cancelled', 'field closure', 'closed field cancelled'])
            ->where('start_play_time', '<', $detail['end_play_time'])
            ->where('end_play_time', '>', $detail['start_play_time'])
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

    public function closedBookings(Request $request)
    {
        $user = $request->user();

        $query = BookingDetail::whereIn('status', ['field closure', 'closed field cancelled', 'closed field reschedule'])
            ->with(['booking.user', 'booking.field'])
            ->orderBy('play_date', 'desc')
            ->orderBy('start_play_time');

        if ($user && $user->role === 'worker') {
            $query->whereHas('booking', function($q) use ($user) {
                $q->whereIn('fk_field_id', function($subQuery) use ($user) {
                    $subQuery->select('fk_field_id')
                             ->from('field_admins')
                             ->where('fk_user_id', $user->id);
                });
            });
        }

        if ($request->has('field_id')) {
            $query->whereHas('booking', function($q) use ($request) {
                $q->where('fk_field_id', $request->field_id);
            });
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

    public function refundOverpayment(Request $request, $detail_booking_id)
    {
        $detail = BookingDetail::find($detail_booking_id);
        if (!$detail) {
            return response()->json(['status' => 'error', 'message' => 'Data sesi tidak ditemukan.'], 404);
        }

        $allPayments = $detail->booking->payments->where('status', 'success');
        $totalBookingPaid = $allPayments->whereIn('payment_type', ['down payment', 'final payment', 'reschedule fee'])->sum('amount');
        $totalBookingRefund = $allPayments->where('payment_type', 'refund')->sum('amount');
        $totalDetailsCount = $detail->booking->details->count();

        if ($totalDetailsCount == 1) {
            $totalPaid = $totalBookingPaid - $totalBookingRefund;
        } else {
            $specificPaid = $allPayments->where('fk_booking_detail_id', $detail->id)->whereIn('payment_type', ['down payment', 'final payment', 'reschedule fee'])->sum('amount');
            $specificRefund = $allPayments->where('fk_booking_detail_id', $detail->id)->where('payment_type', 'refund')->sum('amount');
            $genericPaid = $allPayments->where('fk_booking_detail_id', null)->whereIn('payment_type', ['down payment', 'final payment'])->sum('amount');
            
            $totalPaid = ($specificPaid - $specificRefund) + ($genericPaid / $totalDetailsCount);
        }

        $overpayment = $totalPaid - $detail->price;

        if ($overpayment <= 0) {
            return response()->json(['status' => 'error', 'message' => 'Tidak ada kelebihan pembayaran pada sesi ini.'], 400);
        }

        try {
            DB::transaction(function () use ($detail, $overpayment) {
                Payment::create([
                    'fk_booking_id' => $detail->fk_booking_id,
                    'fk_booking_detail_id' => $detail->id,
                    'reference_id' => 'RFD-' . strtoupper(Str::random(8)),
                    'payment_type' => 'refund',
                    'method' => 'cash',
                    'amount' => $overpayment,
                    'status' => 'success',
                    'paid_at' => now(),
                ]);
            });

            return response()->json([
                'status' => 'success',
                'message' => 'Kelebihan pembayaran berhasil dikembalikan secara tunai.'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memproses pengembalian: ' . $e->getMessage()
            ], 500);
        }
    }
}