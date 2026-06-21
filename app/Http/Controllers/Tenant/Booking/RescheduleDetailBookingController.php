<?php

namespace App\Http\Controllers\Tenant\Booking;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\Booking\TenantRescheduleRequest;
use App\Services\Tenant\Booking\TenantRescheduleService;
use App\Models\BookingDetail;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;
use Throwable;
use Illuminate\Support\Facades\Cache;

class RescheduleDetailBookingController extends Controller
{
    private const ROUTE_RESCHEDULE_FORM = 'tenant.booking.form.reschedule';
    private const ROUTE_HISTORY_SHOW = 'tenant.booking.history.show';

    protected TenantRescheduleService $rescheduleService;

    public function __construct(TenantRescheduleService $rescheduleService)
    {
        $this->rescheduleService = $rescheduleService;
    }

    public function formInput(Request $request, $detail_booking_id): RedirectResponse|View
    {
        try {
            $detail = BookingDetail::query()->with('booking.field.fieldPrices')->findOrFail($detail_booking_id);

            $this->authorizeAccess($detail);
            $formData = $this->rescheduleService->getFormPreparationData($detail, $request->query());

            $response = view('tenant.booking.reschedule.index', array_merge(['detail' => $detail], $formData));
        } catch (Throwable $e) {
            $bookingId = BookingDetail::query()->find($detail_booking_id)->fk_booking_id ?? 1;
            $response = redirect()->route(self::ROUTE_HISTORY_SHOW, $bookingId)->with('error', $e->getMessage());
        }

        return $response;
    }

    public function confirmation(TenantRescheduleRequest $request): RedirectResponse|View
    {
        $detailId = $request->detail_booking_id;

        try {
            $detail = BookingDetail::query()->with('booking.field')->findOrFail($detailId);

            $this->authorizeAccess($detail);
            $reviewData = $this->rescheduleService->validateAndPrepareReview($detail, $request->validated());

            $response = view('tenant.booking.reschedule.review', array_merge([
                'detail'    => $detail,
                'validated' => $request->validated()
            ], $reviewData));
        } catch (Throwable $e) {
            $response = redirect()->route(self::ROUTE_RESCHEDULE_FORM, ['detail_booking_id' => $detailId])->with('error', $e->getMessage());
        }

        return $response;
    }

    public function process(TenantRescheduleRequest $request): RedirectResponse
    {
        $detailId = $request->detail_booking_id;

        try {
            $detail = BookingDetail::query()->with('booking')->findOrFail($detailId);

            $this->authorizeAccess($detail);
            $this->rescheduleService->executeReschedule($detail, $request->validated());

            Cache::forget("tenant_nearest_bookings_field_{$request->field_id}");

            $response = redirect()->route(self::ROUTE_HISTORY_SHOW, $detail->fk_booking_id)
                ->with('success', 'Reschedule berhasil! Jadwal booking telah diubah.');
        } catch (Throwable $e) {
            $response = redirect()->route(self::ROUTE_RESCHEDULE_FORM, ['detail_booking_id' => $detailId])->with('error', $e->getMessage());
        }

        return $response;
    }

    private function authorizeAccess(BookingDetail $detail): void
    {
        if ($detail->booking->fk_user_id !== Auth::id()) {
            throw new InvalidArgumentException('Anda tidak memiliki akses ke booking ini.');
        }
    }
}
