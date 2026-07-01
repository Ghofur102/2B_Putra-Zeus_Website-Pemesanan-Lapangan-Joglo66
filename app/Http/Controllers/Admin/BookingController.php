<?php

namespace App\Http\Controllers\Admin;

use App\Models\BookingDetail;
use App\Services\Admin\BookingService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreBookingRequest;
use App\Http\Controllers\Traits\FieldAccessTrait;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
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
            $detail = BookingDetail::query()
                ->with(['booking.payments', 'booking.details'])
                ->find($detail_booking_id);

            if (!$detail) {
                throw new NotFoundHttpException('Booking detail not found.');
            }

            if (!$this->checkFieldAccess($request->user(), $detail->booking->fk_field_id)) {
                throw new AccessDeniedHttpException('Unauthorized. Anda tidak memiliki akses ke lapangan ini.');
            }

            $result = $this->bookingService->getBookingDetailInfo($detail);
            $data = ['success' => true, 'data' => $result];
        } catch (HttpException $e) {
            $status = $e->getStatusCode();
            $data = ['success' => false, 'message' => $e->getMessage()];
        } catch (Throwable $e) {
            $status = 500;
            $data = [
                'success' => false,
                'message' => 'Internal server error.',
                'error'   => $e->getMessage()
            ];
        }

        return response()->json($data, $status);
    }
}
