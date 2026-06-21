<?php

namespace App\Http\Controllers\Tenant\Booking;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\Booking\GetTransactionHistoryRequest;
use App\Services\Tenant\Booking\TenantHistoryService;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

class HistoryController extends Controller
{
    protected TenantHistoryService $historyService;

    public function __construct(TenantHistoryService $historyService)
    {
        $this->historyService = $historyService;
    }

    public function index(GetTransactionHistoryRequest $request): View
    {
        $userId = Auth::id();

        $availableStatuses = $this->historyService->getAvailablePaymentStatuses();
        $transactions = $this->historyService->getPaginatedHistory((int)$userId, $request->validated());

        return view('tenant.booking.history.index', compact('transactions', 'availableStatuses'));
    }

    public function show(int $id): View
    {
        $userId = Auth::id();
        $booking = $this->historyService->getBookingDetail((int)$userId, $id);

        return view('tenant.booking.history.show', compact('booking'));
    }
}
