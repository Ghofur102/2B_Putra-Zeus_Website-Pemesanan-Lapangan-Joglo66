<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\GetTenantDashboardRequest;
use App\Services\Tenant\TenantDashboardService;
use App\Models\Field;
use Carbon\Carbon;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    protected TenantDashboardService $dashboardService;

    public function __construct(TenantDashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    public function index(GetTenantDashboardRequest $request): View
    {
        $userId = Auth::id();
        $fields = Field::all();

        $selectedFieldId = $request->query('field_id');
        $selectedField = null;
        $nearestBookings = collect();
        $userBookings = collect();

        if ($selectedFieldId) {
            $selectedField = Field::query()->findOrFail($selectedFieldId);
            $now = Carbon::now();

            $nearestBookings = $this->dashboardService->getNearestBookings((int)$selectedFieldId, $now);
            $userBookings = $this->dashboardService->getUserBookings((int)$userId, (int)$selectedFieldId);
        }

        return view('tenant.index', compact(
            'fields',
            'selectedField',
            'selectedFieldId',
            'nearestBookings',
            'userBookings'
        ));
    }
}
