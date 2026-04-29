<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Gate;
use App\Models\Field;
use App\Models\Booking;
use App\Models\BookingDetail;
use App\Models\BookingCancle;
use App\Models\BookingReschedule;
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
            $user = $request->user();

            // 1. Base Query untuk Booking (Dilengkapi Relasi)
            $query = Booking::with(['user', 'details']);

            // 2. FILTER BERDASARKAN HAK AKSES WORKER (Tabel field_admins)
            if ($user && $user->role === 'worker') {
                $query->whereIn('fk_field_id', function($q) use ($user) {
                    $q->select('fk_field_id')
                      ->from('field_admins')
                      ->where('fk_user_id', $user->id);
                });
            }

            // 3. Filter berdasarkan lapangan spesifik (Jika dikirim via request)
            if ($fieldId) {
                $query->where('fk_field_id', $fieldId);
            }

            // 4. Apply search filter if provided
            if ($search) {
                $query->where('team_name', 'LIKE', "%{$search}%");
            }

            // Ambil semua booking yang memenuhi syarat filter di atas
            $bookings = $query->get()->sortBy(function ($booking) {
                return $booking->details->min('play_date');
            });

            // Split into today and upcoming
            $todayBookings = [];
            $upcomingBookings = [];

            foreach ($bookings as $booking) {
                // Ambil relasi field secara manual dari database jika tidak ada (Karena eager loading dihapus di query awal untuk keamanan)
                $fieldName = Field::find($booking->fk_field_id)->name ?? 'Unknown Field';

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
                            $fieldName,
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
    // (Fungsi store tidak saya ubah karena sudah cukup aman. Hanya bisa membuat pesanan jika field_id dikirim)
    // Jika Anda ingin memastikan worker hanya bisa membuat pesanan di lapangannya sendiri, tambahkan pengecekan DB::table('field_admins') di fungsi store ini.
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

        // Cek Hak Akses Worker saat membuat pesanan (Tambahan Keamanan)
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

    // GET: /api/admin/detail-booking/{detail_booking_id} (Ghofur)
    public function show(Request $request, $detail_booking_id)
    {
        $user = $request->user();

        // 1. Mengambil data beserta relasi INDUKNYA (booking.payments dan booking.details)
        $detail = BookingDetail::with(['booking.field', 'booking.user', 'booking.payments', 'booking.details'])->find($detail_booking_id);

        // 2. Validasi eksistensi
        if (!$detail) {
            return response()->json([
                'status' => 'error',
                'message' => 'Booking detail not found.',
                'data' => null
            ], 404);
        }

        // 3. VALIDASI HAK AKSES
        if ($user && $user->role === 'worker') {
            $isAuthorized = DB::table('field_admins')
                ->where('fk_user_id', $user->id)
                ->where('fk_field_id', $detail->booking->fk_field_id)
                ->exists();

            if (!$isAuthorized) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Anda tidak memiliki hak akses untuk melihat detail pesanan ini.',
                ], 403);
            }
        }

        // 4. Kalkulasi Durasi (Untuk detail jam ini saja)
        $start = Carbon::parse($detail->start_play_time);
        $end = Carbon::parse($detail->end_play_time);
        $duration = $start->diffInHours($end);
        $duration = $duration > 0 ? $duration : 1;
        $pricePerHour = $detail->price / $duration;

        // =========================================================================
        // PERBAIKAN: HITUNG TOTAL HARGA DAN PEMBAYARAN DARI KESELURUHAN PESANAN
        // =========================================================================
        $bookingTotalPrice = $detail->booking->details->sum('price');

        // Cari status 'success' (Sesuai dengan yang disimpan di PaymentController)
        $bookingTotalPaid = $detail->booking->payments->where('status', 'success')->sum('amount');

        $paymentMethod = '-';
        if ($detail->booking->payments->count() > 0) {
            $paymentMethod = $detail->booking->payments->last()->method ?? '-';
        }

        $responseData = [
            'id' => $detail->id,
            'status' => $detail->status,
            'user_info' => [
                'name' => $detail->booking->team_name ?? 'Guest',
                'email' => $detail->booking->customer_email ?? '-',
                'phone' => $detail->booking->customer_phone ?? '-',
                'team_name' => $detail->booking->team_name ?? '-',
                'notes' => $detail->booking->notes ?? '-',
            ],
            'field_info' => [
                'name' => $detail->booking->field->name ?? 'Unknown Field',
                'category' => $detail->booking->field->category ?? 'Unknown Category',
                'image_url' => $detail->booking->field->image_url,
            ],
            'time_info' => [
                'play_date' => Carbon::parse($detail->play_date)->format('l, F d, Y'),
                'play_time' => $start->format('H:i') . ' - ' . $end->format('H:i'),
                'order_time' => Carbon::parse($detail->booking->booking_date)->format('l, F d, Y H:i'),
            ],
            'service_info' => [
                'duration' => $duration,
                'price_per_hour' => $pricePerHour,
                'total_price' => $bookingTotalPrice,
                'total_down_payment' => $bookingTotalPaid,
            ],
            'payment_details' => [
                'total_price' => $bookingTotalPrice,
                'payment_method' => $paymentMethod,
            ]
        ];

        return response()->json([
            'status' => 'success',
            'message' => 'Booking detail retrieved successfully.',
            'data' => $responseData
        ], 200);
    }

    // POST/PUT: /api/admin/reschedule-booking/{detail_booking_id} (Ghofur)
    public function reschedule(Request $request, $detail_booking_id)
    {
        $request->validate([
            'new_play_date' => 'required|date',
            'new_start_time' => 'required|date_format:H:i',
            'new_end_time' => 'required|date_format:H:i|after:new_start_time',
            'reason' => 'required|string',
            'fk_field_closure_id' => 'nullable|integer',
            // TAMBAHKAN VALIDASI INI
            'new_price' => 'nullable|integer|min:0',
        ]);

        $detail = BookingDetail::find($detail_booking_id);

        if (!$detail) {
            return response()->json(['status' => 'error', 'message' => 'Data booking tidak ditemukan.'], 404);
        }

        try {
            DB::transaction(function () use ($detail, $request) {
                BookingReschedule::create([
                    'fk_booking_detail_id' => $detail->id,
                    'fk_field_closure_id' => $request->fk_field_closure_id,
                    'old_date' => $detail->play_date,
                    'reason' => $request->reason,
                ]);

                // UBAH BAGIAN INI UNTUK MENYIMPAN HARGA BARU JIKA ADA
                $updateData = [
                    'play_date' => $request->new_play_date,
                    'start_play_time' => $request->new_start_time,
                    'end_play_time' => $request->new_end_time,
                    'status' => 'reschedule',
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

        if (!$detail) {
            return response()->json(['status' => 'error', 'message' => 'Data booking tidak ditemukan.'], 404);
        }

        if ($detail->status === 'Cancelled') {
            return response()->json(['status' => 'error', 'message' => 'Booking ini sudah dibatalkan sebelumnya.'], 400);
        }

        try {
            DB::transaction(function () use ($detail, $request) {
                BookingCancle::create([
                    'fk_booking_detail_id' => $detail->id,
                    'fk_field_closure_id' => $request->fk_field_closure_id,
                    'cancle_date' => Carbon::now()->toDateString(),
                    'reason' => $request->reason,
                    'status_refund' => $request->status_refund ?? 'None',
                ]);

                $detail->update([
                    'status' => 'cancelled',
                ]);
            });

            return response()->json(['status' => 'success', 'message' => 'Booking berhasil dibatalkan.'], 200);

        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Terjadi kesalahan: ' . $e->getMessage()], 500);
        }
    }

    private function hasFieldConflict(int $fieldId, array $detail): bool
    {
        return BookingDetail::query()
            ->whereHas('booking', function ($query) use ($fieldId) {
                $query->where('fk_field_id', $fieldId);
            })
            ->where('play_date', $detail['play_date'])
            // 1. Abaikan booking yang sudah dibatalkan atau karena lapangan tutup
            ->whereNotIn('status', ['cancelled', 'field closure'])

            // 2. Logika Overlap yang Benar (Tidak menganggap bentrok jika jamnya hanya bersebelahan)
            // Rumus Overlap: (StartA < EndB) AND (EndA > StartB)
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

    // GET: /api/admin/list-close-booking (Huda)
    public function closedBookings(Request $request)
    {
        $user = $request->user();

        // 1. Ambil data dengan relasinya
        $query = BookingDetail::where('status', 'field closure')
            ->with(['booking.user', 'field'])
            ->orderBy('play_date', 'desc')
            ->orderBy('start_play_time');

        // 2. FILTER BERDASARKAN HAK AKSES WORKER
        if ($user && $user->role === 'worker') {
            $query->whereHas('booking', function($q) use ($user) {
                $q->whereIn('fk_field_id', function($subQuery) use ($user) {
                    $subQuery->select('fk_field_id')
                             ->from('field_admins')
                             ->where('fk_user_id', $user->id);
                });
            });
        }

        // 3. Filter Query string
        if ($request->has('field_id')) {
            // Karena relasi booking ada di tabel Booking, kita harus join atau menggunakan whereHas
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

    private function generateBookingDescription(string $fieldName, $startTime, $endTime): string
    {
        $start = Carbon::parse($startTime);
        $end = Carbon::parse($endTime);
        $duration = $end->diffInHours($start);

        return "Booking lapangan {$fieldName} dengan durasi {$duration} jam";
    }
}
