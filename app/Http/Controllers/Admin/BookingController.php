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
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class BookingController extends Controller
{
    private const STR_SUCCESS = 'success';
    private const STR_ERROR = 'error';
    private const STR_REFUND = 'refund';
    private const STR_CANCELLED = 'cancelled';
    private const STR_FIELD_CLOSURE = 'field closure';
    private const STR_WORKER = 'worker';
    private const DOWN_PAYMENT = 'down payment';
    private const FINAL_PAYMENT = 'final payment';
    private const RESCHEDULE_FEE = 'reschedule fee';
    private const DATE_TIME_FORMAT = 'Y-m-d H:i:s';
    private const CLOSED_FIELD_CANCELLED = 'closed field cancelled';

    public function index(Request $request): JsonResponse
    {
        $status = 200;
        try {
            $fieldId = $request->field_id;
            $search = $request->search;
            $startDate = $request->start_date;
            $endDate = $request->end_date;
            $limit = $request->limit ?? 20;
            $today = Carbon::now()->format('Y-m-d');
            $user = $request->user();

            $query = Booking::with(['user', 'details', 'payments']);

            if ($user && $user->role === self::STR_WORKER) {
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
                $this->processBookingDetails($booking, $startDate, $endDate, $search, $today, $todayBookings, $upcomingBookings);
            }

            usort($todayBookings, fn($a, $b) => strcmp($a['sort_datetime'], $b['sort_datetime']));
            usort($upcomingBookings, fn($a, $b) => strcmp($a['sort_datetime'], $b['sort_datetime']));

            $data = [
                'success' => true,
                'message' => 'Booking list retrieved successfully',
                'data' => [
                    'today' => array_slice($todayBookings, 0, $limit),
                    'upcoming' => array_slice($upcomingBookings, 0, $limit)
                ]
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

    public function store(Request $request): JsonResponse
    {
        $status = 201;
        try {
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
                $status = 422;
                $data = [
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors(),
                ];
                return response()->json($data, $status);
            }

            $payload = $validator->validated();
            $userLogin = $request->user();

            if ($userLogin && $userLogin->role === self::STR_WORKER) {
                $isAuthorized = DB::table('field_admins')
                    ->where('fk_user_id', $userLogin->id)
                    ->where('fk_field_id', $payload['field_id'])
                    ->exists();

                if (!$isAuthorized) {
                    throw new HttpException(403, 'Anda tidak memiliki hak akses untuk membuat pesanan di lapangan ini.');
                }
            }

            $field = Field::find($payload['field_id']);
            $user = User::find($payload['user_id']);

            if (!$field || !$user) {
                throw new HttpException(400, 'User or field not found.');
            }

            $totalPrice = $this->processAndValidateDetails($payload['field_id'], $payload['details']);

            $booking = DB::transaction(function () use ($payload) {
                $booking = Booking::create([
                    'fk_user_id' => $payload['user_id'],
                    'fk_field_id' => $payload['field_id'],
                    'team_name' => $payload['team_name'],
                    'booking_date' => $payload['booking_date'],
                    'customer_phone' => $payload['customer_phone'] ?? null,
                    'customer_email' => $payload['customer_email'] ?? null,
                    'notes' => $payload['notes'] ?? null,
                ]);

                foreach ($payload['details'] as $detail) {
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

            $data = [
                'success' => true,
                'message' => 'Booking created successfully.',
                'data' => [
                    'booking_id' => $booking->id,
                    'total_price' => $totalPrice,
                ],
            ];
        } catch (HttpException $e) {
            $status = $e->getStatusCode();
            $data = ['success' => false, 'message' => $e->getMessage()];
        } catch (Throwable $e) {
            $status = 500;
            $data = [
                'success' => false,
                'message' => 'Failed to create booking. Please try again.',
                'error' => $e->getMessage(),
            ];
        }
        return response()->json($data, $status);
    }

    public function show(Request $request, $detail_booking_id): JsonResponse
    {
        $status = 200;
        try {
            $user = $request->user();
            $detail = BookingDetail::with(['booking.field', 'booking.user', 'booking.payments', 'booking.details'])->find($detail_booking_id);

            if (!$detail) {
                throw new HttpException(404, 'Booking detail not found.');
            }

            if ($user && $user->role === self::STR_WORKER) {
                $isAuthorized = DB::table('field_admins')->where('fk_user_id', $user->id)->where('fk_field_id', $detail->booking->fk_field_id)->exists();
                if (!$isAuthorized) {
                    throw new HttpException(403, 'Unauthorized.');
                }
            }

            $start = Carbon::parse($detail->start_play_time);
            $end = Carbon::parse($detail->end_play_time);
            $duration = max(1, $start->diffInHours($end));

            $allPayments = $detail->booking->payments->where('status', self::STR_SUCCESS);
            $totalBookingPaid = $allPayments->whereIn('payment_type', [self::DOWN_PAYMENT, self::FINAL_PAYMENT, self::RESCHEDULE_FEE])->sum('amount');
            $totalBookingRefund = $allPayments->where('payment_type', self::STR_REFUND)->sum('amount');

            // PERBAIKAN DI SINI: Menambahkan $detail ke dalam statement 'use'
            $sessions = $detail->booking->details->map(function ($item) use ($allPayments, $detail) {
                $sessionPaid = $this->calculateTotalPaidForDetail($detail->booking, $item);

                return [
                    'id' => $item->id,
                    'play_date' => Carbon::parse($item->play_date)->format('d M Y'),
                    'start_time' => Carbon::parse($item->start_play_time)->format('H:i'),
                    'end_time' => Carbon::parse($item->end_play_time)->format('H:i'),
                    'price' => (int)$item->price,
                    'status' => $item->status,
                    'total_paid' => (int)$sessionPaid,
                    'remaining_payment' => (int)($item->price - $sessionPaid),
                    'refund_amount' => (int)$allPayments->where('payment_type', self::STR_REFUND)->where('fk_booking_detail_id', $item->id)->sum('amount')
                ];
            });

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
                'field_closures' => $closures
            ];

            $data = ['status' => self::STR_SUCCESS, 'data' => $responseData];
        } catch (HttpException $e) {
            $status = $e->getStatusCode();
            $data = ['status' => self::STR_ERROR, 'message' => $e->getMessage()];
        } catch (Throwable $e) {
            $status = 500;
            $data = ['status' => self::STR_ERROR, 'message' => 'Internal server error.'];
        }
        return response()->json($data, $status);
    }

    public function reschedule(Request $request, $detail_booking_id): JsonResponse
    {
        $status = 200;
        try {
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
                throw new HttpException(404, 'Data booking tidak ditemukan.');
            }

            $this->validateRescheduleTimeline($detail);

            $startFormatted = Carbon::parse($request->new_play_date . ' ' . $request->new_start_time)->format(self::DATE_TIME_FORMAT);
            $endFormatted = Carbon::parse($request->new_play_date . ' ' . $request->new_end_time)->format(self::DATE_TIME_FORMAT);

            $isClosed = DB::table('field_closures')
                ->where('fk_field_id', $detail->booking->fk_field_id)
                ->where('field_closure_start_time', '<', $endFormatted)
                ->where('field_closure_end_time', '>', $startFormatted)
                ->exists();

            if ($isClosed) {
                throw new HttpException(400, 'Reschedule ditolak: Lapangan sedang ditutup operasional (Field Closure) pada slot waktu pilihan Anda.');
            }

            DB::transaction(function () use ($detail, $request) {
                BookingReschedule::create([
                    'fk_booking_detail_id' => $detail->id,
                    'fk_field_closure_id' => $request->fk_field_closure_id,
                    'old_date' => $detail->play_date,
                    'reason' => $request->reason,
                ]);

                $isFromClosure = strtolower($detail->status) === self::STR_FIELD_CLOSURE;
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

            $data = [
                'status' => self::STR_SUCCESS,
                'message' => 'Jadwal booking berhasil diubah.',
                'data' => BookingDetail::find($detail_booking_id)
            ];
        } catch (HttpException $e) {
            $status = $e->getStatusCode();
            $data = ['status' => self::STR_ERROR, 'message' => $e->getMessage()];
        } catch (Throwable $e) {
            $status = 500;
            $data = [
                'status' => self::STR_ERROR,
                'message' => 'Terjadi kesalahan saat mengubah jadwal: ' . $e->getMessage(),
            ];
        }
        return response()->json($data, $status);
    }

    public function cancel(Request $request, $detail_booking_id): JsonResponse
    {
        $status = 200;
        try {
            $request->validate([
                'reason' => 'required|string',
                'status_refund' => 'nullable|string',
                'fk_field_closure_id' => 'nullable|integer',
            ]);

            $detail = BookingDetail::find($detail_booking_id);
            if (!$detail) {
                throw new HttpException(404, 'Data tidak ditemukan.');
            }

            $currentStatus = strtolower($detail->status);
            if (str_contains($currentStatus, 'cancel')) {
                throw new HttpException(400, 'Booking ini sudah dibatalkan.');
            }

            $statusRefund = $this->determineRefundStatus($detail, $request->status_refund ?? 'None');
            $refundAmount = $this->calculateCancelRefund($detail, $statusRefund);

            DB::transaction(function () use ($detail, $request, $statusRefund, $refundAmount) {
                BookingCancelled::create([
                    'fk_booking_detail_id' => $detail->id,
                    'fk_field_closure_id' => $request->fk_field_closure_id,
                    'cancle_date' => Carbon::now()->toDateString(),
                    'reason' => $request->reason,
                    'status_refund' => $statusRefund,
                ]);

                $isFromClosure = strtolower($detail->status) === self::STR_FIELD_CLOSURE;
                $detail->update([
                    'status' => $isFromClosure ? self::CLOSED_FIELD_CANCELLED : self::STR_CANCELLED,
                ]);

                if ($refundAmount > 0) {
                    Payment::create([
                        'fk_booking_id' => $detail->fk_booking_id,
                        'fk_booking_detail_id' => $detail->id,
                        'reference_id' => 'CNL-REF-' . strtoupper(Str::random(10)),
                        'payment_type' => self::STR_REFUND,
                        'method' => 'cash',
                        'amount' => $refundAmount,
                        'status' => self::STR_SUCCESS,
                        'paid_at' => now(),
                    ]);
                }
            });

            $data = ['status' => self::STR_SUCCESS, 'message' => 'Booking berhasil dibatalkan.'];
        } catch (HttpException $e) {
            $status = $e->getStatusCode();
            $data = ['status' => self::STR_ERROR, 'message' => $e->getMessage()];
        } catch (Throwable $e) {
            $status = 500;
            $data = ['status' => self::STR_ERROR, 'message' => 'Terjadi kesalahan: ' . $e->getMessage()];
        }
        return response()->json($data, $status);
    }

    public function closedBookings(Request $request): JsonResponse
    {
        $status = 200;
        try {
            $user = $request->user();

            $query = BookingDetail::whereIn('status', [self::STR_FIELD_CLOSURE, self::CLOSED_FIELD_CANCELLED, 'closed field reschedule'])
                ->with(['booking.user', 'booking.field'])
                ->orderBy('play_date', 'desc')
                ->orderBy('start_play_time');

            if ($user && $user->role === self::STR_WORKER) {
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

            $data = [
                'status' => self::STR_SUCCESS,
                'closed_bookings' => $query->paginate(20),
            ];
        } catch (Throwable $e) {
            $status = 500;
            $data = ['status' => self::STR_ERROR, 'message' => $e->getMessage()];
        }
        return response()->json($data, $status);
    }

    public function refundOverpayment(Request $request, $detail_booking_id): JsonResponse
    {
        $status = 200;
        try {
            $detail = BookingDetail::find($detail_booking_id);
            if (!$detail) {
                throw new HttpException(404, 'Data sesi tidak ditemukan.');
            }

            $totalPaid = $this->calculateTotalPaidForDetail($detail->booking, $detail);
            $overpayment = $totalPaid - $detail->price;

            if ($overpayment <= 0) {
                throw new HttpException(400, 'Tidak ada kelebihan pembayaran pada sesi ini.');
            }

            DB::transaction(function () use ($detail, $overpayment) {
                Payment::create([
                    'fk_booking_id' => $detail->fk_booking_id,
                    'fk_booking_detail_id' => $detail->id,
                    'reference_id' => 'RFD-' . strtoupper(Str::random(8)),
                    'payment_type' => self::STR_REFUND,
                    'method' => 'cash',
                    'amount' => $overpayment,
                    'status' => self::STR_SUCCESS,
                    'paid_at' => now(),
                ]);
            });

            $data = [
                'status' => self::STR_SUCCESS,
                'message' => 'Kelebihan pembayaran berhasil dikembalikan secara tunai.'
            ];
        } catch (HttpException $e) {
            $status = $e->getStatusCode();
            $data = ['status' => self::STR_ERROR, 'message' => $e->getMessage()];
        } catch (Throwable $e) {
            $status = 500;
            $data = [
                'status' => self::STR_ERROR,
                'message' => 'Gagal memproses pengembalian: ' . $e->getMessage()
            ];
        }
        return response()->json($data, $status);
    }

    private function processBookingDetails($booking, $startDate, $endDate, $search, $today, &$todayBookings, &$upcomingBookings): void
    {
        $fieldName = Field::find($booking->fk_field_id)->name ?? 'Unknown Field';

        foreach ($booking->details as $detail) {
            if ($this->shouldSkipDetail($detail->play_date, $startDate, $endDate)) {
                continue;
            }

            $bookingItem = $this->buildBookingItem($booking, $detail, $fieldName);

            if ($startDate || $endDate || $search) {
                $todayBookings[] = $bookingItem;
            } elseif ($detail->play_date === $today) {
                $todayBookings[] = $bookingItem;
            } elseif ($detail->play_date > $today) {
                $upcomingBookings[] = $bookingItem;
            }
        }
    }

    private function shouldSkipDetail(string $playDate, ?string $startDate, ?string $endDate): bool
    {
        if ($startDate && $endDate) {
            return $playDate < $startDate || $playDate > $endDate;
        }
        if ($startDate) {
            return $playDate !== $startDate;
        }
        return false;
    }

    private function calculateTotalPaidForDetail($booking, $detail): float
    {
        $allPayments = $booking->payments->where('status', self::STR_SUCCESS);
        $totalBookingPaid = $allPayments->whereIn('payment_type', [self::DOWN_PAYMENT, self::FINAL_PAYMENT, self::RESCHEDULE_FEE])->sum('amount');
        $totalBookingRefund = $allPayments->where('payment_type', self::STR_REFUND)->sum('amount');
        $totalDetailsCount = $booking->details->count();

        if ($totalDetailsCount == 1) {
            return $totalBookingPaid - $totalBookingRefund;
        }

        $specificPaid = $allPayments->where('fk_booking_detail_id', $detail->id)->whereIn('payment_type', [self::DOWN_PAYMENT, self::FINAL_PAYMENT, self::RESCHEDULE_FEE])->sum('amount');
        $specificRefund = $allPayments->where('fk_booking_detail_id', $detail->id)->where('payment_type', self::STR_REFUND)->sum('amount');
        $genericPaid = $allPayments->where('fk_booking_detail_id', null)->whereIn('payment_type', [self::DOWN_PAYMENT, self::FINAL_PAYMENT])->sum('amount');

        return ($specificPaid - $specificRefund) + ($genericPaid / $totalDetailsCount);
    }

    private function buildBookingItem($booking, $detail, string $fieldName): array
    {
        $totalPaid = $this->calculateTotalPaidForDetail($booking, $detail);
        $remainingPayment = $detail->price - $totalPaid;

        $refundAmount = $booking->payments->where('status', self::STR_SUCCESS)
            ->where('payment_type', self::STR_REFUND)
            ->where('fk_booking_detail_id', $detail->id)
            ->sum('amount');

        return [
            'id' => $detail->id,
            'sort_datetime' => $detail->play_date . ' ' . $detail->start_play_time,
            'date' => Carbon::parse($detail->play_date)->format('d'),
            'month' => Carbon::parse($detail->play_date)->format('M'),
            'year' => Carbon::parse($detail->play_date)->format('Y'),
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
    }

    private function processAndValidateDetails(int $fieldId, array $details): int
    {
        $totalPrice = 0;
        $todayDate = Carbon::now()->format('Y-m-d');
        $currentTime = Carbon::now()->format('H:i');

        foreach ($details as $index => $detail) {
            if ($detail['start_play_time'] >= $detail['end_play_time']) {
                throw new HttpException(400, "Detail #{$index} has invalid time range.");
            }

            if ($detail['play_date'] < $todayDate) {
                throw new HttpException(400, 'Tidak dapat memesan untuk tanggal yang sudah lewat.');
            }

            if ($detail['play_date'] === $todayDate && $detail['start_play_time'] <= $currentTime) {
                throw new HttpException(400, 'Waktu main sudah terlewat untuk hari ini.');
            }

            if ($this->hasFieldConflict($fieldId, $detail)) {
                throw new HttpException(400, 'Field is already booked or closed for the requested time range.');
            }

            if (!$this->validateFieldPrice($fieldId, $detail)) {
                throw new HttpException(400, 'Price validation failed for booking detail.');
            }

            $totalPrice += $detail['price'];
        }

        return $totalPrice;
    }

    private function hasFieldConflict(int $fieldId, array $detail): bool
    {
        $slotStart = Carbon::parse($detail['play_date'] . ' ' . $detail['start_play_time'])->format(self::DATE_TIME_FORMAT);
        $slotEnd = Carbon::parse($detail['play_date'] . ' ' . $detail['end_play_time'])->format(self::DATE_TIME_FORMAT);

        $isClosed = DB::table('field_closures')
            ->where('fk_field_id', $fieldId)
            ->where('field_closure_start_time', '<', $slotEnd)
            ->where('field_closure_end_time', '>', $slotStart)
            ->exists();

        if ($isClosed) {
            return true;
        }

        return BookingDetail::query()
            ->whereHas('booking', function ($query) use ($fieldId) {
                $query->where('fk_field_id', $fieldId);
            })
            ->where('play_date', $detail['play_date'])
            ->whereNotIn('status', [self::STR_CANCELLED, self::STR_FIELD_CLOSURE, self::CLOSED_FIELD_CANCELLED])
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

    private function validateRescheduleTimeline($detail): void
    {
        if (strtolower($detail->status) !== self::STR_FIELD_CLOSURE) {
            $playDate = Carbon::parse($detail->play_date)->startOfDay();
            $today = Carbon::now()->startOfDay();
            $diffDays = $today->diffInDays($playDate, false);

            if ($diffDays < 3) {
                throw new HttpException(400, 'Reschedule ditolak: Jadwal main kurang dari H-3.');
            }
        }
    }

    private function determineRefundStatus($detail, string $inputStatus): string
    {
        $statusRefund =  ucfirst(strtolower($inputStatus));
        if (strtolower($detail->status) !== self::STR_FIELD_CLOSURE) {
            $playDate = Carbon::parse($detail->play_date)->startOfDay();
            $diffDays = Carbon::now()->startOfDay()->diffInDays($playDate, false);
            if ($diffDays < 3) {
                return 'None';
            }
        }
        return $statusRefund;
    }

    private function calculateCancelRefund($detail, string $statusRefund): float
    {
        if ($statusRefund === 'None') {
            return 0;
        }

        $successfulPayments = Payment::where('fk_booking_id', $detail->fk_booking_id)
            ->where('status', self::STR_SUCCESS)
            ->whereIn('payment_type', [self::DOWN_PAYMENT, self::FINAL_PAYMENT])
            ->sum('amount');

        $refunded = Payment::where('fk_booking_detail_id', $detail->id)
            ->where('payment_type', self::STR_REFUND)->sum('amount');

        $netPaid = $successfulPayments - $refunded;
        return ($statusRefund === 'Full') ? $netPaid : ($netPaid / 2);
    }
}
