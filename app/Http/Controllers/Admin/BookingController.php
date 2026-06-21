<?php

namespace App\Http\Controllers\Admin;

use App\Models\BookingDetail;
use App\Services\Admin\BookingService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreBookingRequest;
use App\Http\Requests\Admin\RescheduleBookingRequest;
use App\Http\Requests\Admin\CancelBookingRequest;
use App\Http\Controllers\Traits\FieldAccessTrait;
use App\Enums\BookingDetailStatus;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Throwable;

class BookingController extends Controller
{
    use FieldAccessTrait;

    protected BookingService $bookingService;

    public function __construct(BookingService $bookingService)
    {
        $this->bookingService = $bookingService;
    }

    public function index(Request $request): JsonResponse
    {
        $status = 200;
        $data = [];

        try {
            $user = $request->user();
            $fieldIds = [];

            if ($user && $user->role === 'worker') {
                $fieldIds = $this->getAccessibleFieldIds($user);
            }

            $filters = $request->only(['field_id', 'search', 'start_date', 'end_date', 'limit']);
            $result = $this->bookingService->getBookingList($fieldIds, $filters);

            $data = [
                'success' => true,
                'message' => 'Booking list retrieved successfully',
                'data'    => $result
            ];
        } catch (Throwable $e) {
            $status = 500;
            $data = [
                'success' => false,
                'message' => 'Internal server error: ' . $e->getMessage()
            ];
        }

        return response()->json($data, $status);
    }

    public function store(StoreBookingRequest $request): JsonResponse
    {
        $status = 201;
        $data = [];

        try {
            if (!$this->checkFieldAccess($request->user(), $request->field_id)) {
                throw new AccessDeniedHttpException('Anda tidak memiliki hak akses untuk membuat pesanan di lapangan ini.');
            }

            $booking = $this->bookingService->createBooking($request->validated());

            $data = [
                'success' => true,
                'message' => 'Booking created successfully.',
                'data'    => [
                    'booking_id' => $booking->id
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
                'error'   => $e->getMessage(),
            ];
        }

        return response()->json($data, $status);
    }

    public function show(Request $request, $detail_booking_id): JsonResponse
    {
        $status = 200;
        $data = [];

        try {
            $detail = BookingDetail::query()->find($detail_booking_id);
            if (!$detail) {
                throw new NotFoundHttpException('Booking detail not found.');
            }

            if (!$this->checkFieldAccess($request->user(), $detail->booking->fk_field_id)) {
                throw new AccessDeniedHttpException('Unauthorized.');
            }

            $result = $this->bookingService->getBookingDetailInfo($detail);
            $data = ['success' => true, 'data' => $result];
        } catch (HttpException $e) {
            $status = $e->getStatusCode();
            $data = ['success' => false, 'message' => $e->getMessage()];
        } catch (Throwable $e) {
            $status = 500;
            $data = ['success' => false, 'message' => 'Internal server error.'];
        }

        return response()->json($data, $status);
    }

    public function reschedule(RescheduleBookingRequest $request, $detail_booking_id): JsonResponse
    {
        $status = 200;
        $data = [];

        try {
            $detail = BookingDetail::query()->find($detail_booking_id);
            if (!$detail) {
                throw new NotFoundHttpException('Data booking tidak ditemukan.');
            }

            if (!$this->checkFieldAccess($request->user(), $detail->booking->fk_field_id)) {
                throw new AccessDeniedHttpException('Unauthorized.');
            }

            $this->bookingService->executeReschedule($detail, $request->validated());

            $data = [
                'success' => true,
                'message' => 'Jadwal booking berhasil diubah.',
                'data'    => $detail->fresh()
            ];
        } catch (HttpException $e) {
            $status = $e->getStatusCode();
            $data = ['success' => false, 'message' => $e->getMessage()];
        } catch (Throwable $e) {
            $status = 500;
            $data = [
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengubah jadwal: ' . $e->getMessage(),
            ];
        }

        return response()->json($data, $status);
    }

    public function cancel(CancelBookingRequest $request, $detail_booking_id): JsonResponse
    {
        $status = 200;
        $data = [];

        try {
            $detail = BookingDetail::query()->find($detail_booking_id);
            if (!$detail) {
                throw new NotFoundHttpException('Data tidak ditemukan.');
            }

            if (!$this->checkFieldAccess($request->user(), $detail->booking->fk_field_id)) {
                throw new AccessDeniedHttpException('Unauthorized.');
            }

            $this->bookingService->executeCancel($detail, $request->validated());
            $data = ['success' => true, 'message' => 'Booking berhasil dibatalkan.'];
        } catch (HttpException $e) {
            $status = $e->getStatusCode();
            $data = ['success' => false, 'message' => $e->getMessage()];
        } catch (Throwable $e) {
            $status = 500;
            $data = ['success' => false, 'message' => 'Terjadi kesalahan: ' . $e->getMessage()];
        }

        return response()->json($data, $status);
    }

    public function closedBookings(Request $request): JsonResponse
    {
        $status = 200;
        $data = [];

        try {
            $user = $request->user();
            $query = BookingDetail::query()
                ->whereIn('status', [
                    BookingDetailStatus::FIELD_CLOSURE->value,
                    BookingDetailStatus::CLOSED_FIELD_CANCELLED->value,
                    BookingDetailStatus::CLOSED_FIELD_RESCHEDULE->value
                ])
                ->with(['booking.user', 'booking.field'])
                ->orderBy('play_date', 'desc')
                ->orderBy('start_play_time');

            if ($user && $user->role === 'worker') {
                $fieldIds = $this->getAccessibleFieldIds($user);
                $query->whereHas('booking', function($q) use ($fieldIds) {
                    /** @var \Illuminate\Database\Eloquent\Builder $q */
                    $q->whereIn('fk_field_id', $fieldIds);
                });
            }

            if ($request->filled('field_id')) {
                $query->whereHas('booking', function($q) use ($request) {
                    /** @var \Illuminate\Database\Eloquent\Builder $q */
                    $q->where('fk_field_id', $request->field_id);
                });
            }

            if ($request->filled('date')) {
                $query->where('play_date', $request->date);
            }

            $data = [
                'success'         => true,
                'closed_bookings' => $query->paginate(20),
            ];
        } catch (Throwable $e) {
            $status = 500;
            $data = ['success' => false, 'message' => $e->getMessage()];
        }

        return response()->json($data, $status);
    }

    public function refundOverpayment(Request $request, $detail_booking_id): JsonResponse
    {
        $status = 200;
        $data = [];

        try {
            $detail = BookingDetail::query()->find($detail_booking_id);
            if (!$detail) {
                throw new NotFoundHttpException('Data sesi tidak ditemukan.');
            }

            if (!$this->checkFieldAccess($request->user(), $detail->booking->fk_field_id)) {
                throw new AccessDeniedHttpException('Unauthorized.');
            }

            $this->bookingService->executeRefundOverpayment($detail);

            $data = [
                'success' => true,
                'message' => 'Kelebihan pembayaran berhasil dikembalikan secara tunai.'
            ];
        } catch (HttpException $e) {
            $status = $e->getStatusCode();
            $data = ['success' => false, 'message' => $e->getMessage()];
        } catch (Throwable $e) {
            $status = 500;
            $data = [
                'success' => false,
                'message' => 'Gagal memproses pengembalian: ' . $e->getMessage()
            ];
        }

        return response()->json($data, $status);
    }
}
