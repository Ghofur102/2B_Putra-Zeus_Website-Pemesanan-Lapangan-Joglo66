<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BookingDetail;
use App\Services\Admin\BookingService;
use App\Http\Controllers\Traits\FieldAccessTrait;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Throwable;

class RefundOverpaymentController extends Controller
{
    use FieldAccessTrait;

    protected BookingService $bookingService;

    public function __construct(BookingService $bookingService)
    {
        $this->bookingService = $bookingService;
    }

    public function __invoke(Request $request, $id): JsonResponse
    {
        $status = 200;
        $data = [];

        try {
            $detail = BookingDetail::query()->find($id);
            if (!$detail) {
                throw new NotFoundHttpException('Data sesi tidak ditemukan.');
            }

            if (!$this->checkFieldAccess($request->user(), $detail->booking->fk_field_id)) {
                throw new AccessDeniedHttpException('Unauthorized field access.');
            }

            $this->bookingService->executeRefundOverpayment($detail);

            $data = [
                'success' => true,
                'message' => 'Kelebihan pembayaran berhasil dikembalikan secara tunai oleh kasir.'
            ];
        } catch (HttpException $e) {
            $status = $e->getStatusCode();
            $data = ['success' => false, 'message' => $e->getMessage()];
        } catch (Throwable $e) {
            $status = 500;
            $data = [
                'success' => false,
                'message' => 'Gagal memproses pengembalian kelebihan dana: ' . $e->getMessage()
            ];
        }

        return response()->json($data, $status);
    }
}
