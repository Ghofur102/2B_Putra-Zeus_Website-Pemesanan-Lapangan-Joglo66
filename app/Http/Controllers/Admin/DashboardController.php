<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\DashboardService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class DashboardController extends Controller
{
    protected DashboardService $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    public function dashboard(Request $request): JsonResponse
    {
        $status = 200;
        $data = [];

        try {
            $user = $request->user();
            $field = $this->dashboardService->resolveField($user, $request->field_id);
            $metrics = $this->dashboardService->getDashboardMetrics($field);

            $data = [
                'success' => true,
                'message' => 'Dashboard data retrieved successfully',
                'data'    => $metrics
            ];
        } catch (HttpException $e) {
            $status = $e->getStatusCode();
            $data = [
                'success' => false,
                'message' => $e->getMessage(),
                'data'    => $status === 404 ? [
                    'name'         => 'Belum Ada Lapangan',
                    'slotTerisi'   => 0,
                    'totalSlot'    => 0,
                    'slotKosong'   => 0,
                    'totalBooking' => 0,
                ] : null
            ];
        } catch (Throwable $e) {
            $status = 500;
            $data = [
                'success' => false,
                'message' => 'Internal server error: ' . $e->getMessage(),
                'data'    => null
            ];
        }

        return response()->json($data, $status);
    }
}
