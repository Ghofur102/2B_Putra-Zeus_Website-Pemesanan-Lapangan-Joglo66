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
        $response = null;
        $detailId = $request->detail_booking_id;

        try {
            $detail = BookingDetail::query()->with('booking')->findOrFail($detailId);
            $this->authorizeAccess($detail);

            $fieldId = $detail->booking->fk_field_id ?? $request->field_id;
            $oldDate = $detail->play_date;

            $validatedData = $request->validated();
            $newDate = $validatedData['new_play_date'] ?? $request->new_play_date;
            $newTime = $validatedData['new_start_play_time'] ?? $request->new_start_play_time;

            $lockKey = "lock_field_{$fieldId}_date_{$newDate}_slot_{$newTime}";
            $lock = Cache::lock($lockKey, 15);

            if (!$lock->get()) {
                $response = redirect()->route(self::ROUTE_RESCHEDULE_FORM, ['detail_booking_id' => $detailId])
                    ->with('error', 'Jadwal baru pilihan Anda sedang diproses oleh pengguna lain. Silakan tunggu 15 detik atau pilih waktu yang berbeda.');
            } else {
                try {
                    $this->rescheduleService->executeReschedule($detail, $validatedData);
                    $this->clearRescheduleCache((int)$fieldId, $oldDate, $newDate);

                    $response = redirect()->route(self::ROUTE_HISTORY_SHOW, $detail->fk_booking_id)
                        ->with('success', 'Reschedule berhasil! Jadwal booking telah diubah.');
                } finally {
                    $lock->release();
                }
            }
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

    private function clearRescheduleCache(int $fieldId, string $oldDate, string $newDate): void
    {
        $cleanOldDate = \Carbon\Carbon::parse($oldDate)->format('Y-m-d');
        $cleanNewDate = \Carbon\Carbon::parse($newDate)->format('Y-m-d');

        Cache::forget("tenant_slots_field_{$fieldId}_{$cleanOldDate}");
        Cache::forget("tenant_slots_field_{$fieldId}_{$cleanNewDate}");

        if ($fieldId) {
            Cache::forget("tenant_nearest_bookings_field_{$fieldId}");
        }
    }
}
