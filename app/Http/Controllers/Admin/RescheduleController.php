<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RescheduleBookingRequest;
use App\Services\Admin\RescheduleService;
use App\Models\BookingDetail;
use App\Http\Controllers\Traits\FieldAccessTrait;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class RescheduleController extends Controller
{
    use FieldAccessTrait;

    protected RescheduleService $rescheduleService;

    public function __construct(RescheduleService $rescheduleService)
    {
        $this->rescheduleService = $rescheduleService;
    }

    public function __invoke(RescheduleBookingRequest $request, $detail_booking_id): JsonResponse
    {
        try {
            $detail = BookingDetail::query()->findOrFail($detail_booking_id);

            if (!$this->checkFieldAccess($request->user(), $detail->booking->fk_field_id)) {
                throw new HttpException(403, 'Unauthorized field access.');
            }

            $this->rescheduleService->execute($detail, $request->validated());

            return response()->json([
                'status' => 'success',
                'message' => 'Jadwal booking berhasil diubah dan finansial disesuaikan.',
                'data' => $detail->fresh()
            ], 200);
        } catch (HttpException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], $e->getStatusCode());
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Gagal reschedule: ' . $e->getMessage()], 500);
        }
    }
}
