<?php

namespace App\Http\Controllers\Tenant\Booking;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\Booking\ConfirmBookingRequest;
use App\Http\Requests\Tenant\Booking\StoreTenantBookingRequest;
use App\Services\Tenant\Booking\TenantBookingService;
use App\Models\Field;
use App\Models\Booking;
use App\Services\DuitkuService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Throwable;
use Illuminate\Support\Facades\Cache;

class BookingController extends Controller
{
    private const ROUTE_DASHBOARD = 'tenant.booking.dashboard';

    protected TenantBookingService $bookingService;

    public function __construct(TenantBookingService $bookingService)
    {
        $this->bookingService = $bookingService;
    }

    public function createForm(Request $request)
    {
        $fieldId = $request->query('field_id');

        if (!$fieldId) {
            $response = redirect()->route(self::ROUTE_DASHBOARD)->with('info', 'Silakan pilih lapangan terlebih dahulu');
        } else {
            $field = Field::query()->findOrFail($fieldId);
            $response = view('tenant.booking.create', ['field' => $field]);
        }

        return $response;
    }

    public function confirmForm(ConfirmBookingRequest $request): View
    {
        $field = Field::query()->findOrFail($request->field_id);
        $selectedSlotsRaw = json_decode($request->selected_slots, true);

        $calculatedData = $this->bookingService->calculateAndGroupSlots($field->id, $selectedSlotsRaw);

        return view('tenant.booking.confirmation', [
            'field'        => $field,
            'groupedSlots' => $calculatedData['groupedSlots'],
            'totalPrice'   => $calculatedData['totalPrice']
        ]);
    }

    public function store(StoreTenantBookingRequest $request, DuitkuService $duitkuService): RedirectResponse|View
    {
        $user = Auth::user();
        $userId = $user ? $user->id : 1;
        $groupedSlots = json_decode($request->booking_data, true);

        if (empty($groupedSlots)) {
            return redirect()->route(self::ROUTE_DASHBOARD)->with('error', 'Pilih minimal satu slot pemesanan.');
        }

        try {
            $result = $this->bookingService->processBookingTransaction($userId, $user, $request->validated(), $groupedSlots, $duitkuService);

            $response = view('tenant.booking.checkout', [
                'booking'     => $result['booking'],
            ]);
        } catch (Throwable $e) {
            $response = redirect()->route(self::ROUTE_DASHBOARD)->with('error', 'Transaksi gagal: ' . $e->getMessage());
        }

        return $response;
    }

    public function success($booking_id): View|RedirectResponse
    {
        try {
            $booking = $this->bookingService->getBookingSuccessData((int)$booking_id, Auth::id());

            return view('tenant.booking.success', compact('booking'));
        } catch (Throwable $e) {
            return redirect()->route('tenant.booking.dashboard')
                ->with('error', $e->getMessage());
        }
    }

}
