<?php

namespace App\Http\Controllers\Tenant\Booking;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\Booking\FetchSlotsRequest;
use App\Services\Tenant\Booking\TenantScheduleService;
use Illuminate\Http\JsonResponse;
use Throwable;

class ScheduleController extends Controller
{
    protected TenantScheduleService $scheduleService;

    public function __construct(TenantScheduleService $scheduleService)
    {
        $this->scheduleService = $scheduleService;
    }

    public function fetchSlots(FetchSlotsRequest $request): JsonResponse
    {
        $statusCode = 200;
        $data = [];

        try {
            $validated = $request->validated();

            $slots = $this->scheduleService->getAvailableHourlySlots(
                (int)$validated['field_id'],
                $validated['date']
            );

            $data = ['slots' => $slots];
        } catch (Throwable $e) {
            $statusCode = 500;
            $data = [
                'success' => false,
                'message' => 'Gagal memuat jadwal slot: ' . $e->getMessage()
            ];
        }

        return response()->json($data, $statusCode);
    }
}
