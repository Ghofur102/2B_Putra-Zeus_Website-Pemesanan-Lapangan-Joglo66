<?php

namespace App\Http\Controllers\Tenant\Booking;

use App\Http\Controllers\Controller;
use App\Models\BookingDetail;
use App\Models\BookingReschedule;
use App\Models\FieldPrice;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use InvalidArgumentException;
use UnexpectedValueException;
use Throwable;

class RescheduleDetailBookingController extends Controller
{
    const PAYMENT_RESCHEDULE_FEE = 'reschedule fee';
    const PAYMENT_REFUND = 'refund';
    const STATUS_REFUND_DEPOSIT = 'deposit required';
    const STATUS_REFUND_REFUND = 'refund required';
    const STATUS_REFUND_NONE = 'none';

    // Solusi php:S1192 - Ekstraksi Seluruh String Duplikat ke Konstanta Kelas
    private const DB_CONN = 'mysql_joglo66_app';
    private const STATUS_CANCELLED = 'cancelled';
    private const STATUS_FAILED = 'failed';
    private const STATUS_EXPIRED = 'expired';
    private const STATUS_RESCHEDULE = 'reschedule';
    private const ROUTE_RESCHEDULE_FORM = 'tenant.booking.form.reschedule';
    private const ROUTE_DASHBOARD = 'tenant.booking.dashboard';
    private const ROUTE_HISTORY_SHOW = 'tenant.booking.history.show';
    private const STR_CASH = 'cash';

    /**
     * Display input form for rescheduling
     * Solusi php:S1142 - Menggunakan Single Exit Point Pattern
     */
    public function formInput(Request $request, $detail_booking_id): RedirectResponse|View
    {
        try {
            $detail = BookingDetail::with('booking.field.fieldPrices')->findOrFail($detail_booking_id);

            $this->authorizeAccess($detail);
            $this->checkRescheduleRules($detail);

            $selectedDate = $request->query('date', date('Y-m-d'));
            $month = (int) $request->query('month', date('m'));
            $year = (int) $request->query('year', date('Y'));

            $calendar = $this->generateCalendar($month, $year);
            $prevMonth = Carbon::create($year, $month, 1)->subMonth();
            $nextMonth = Carbon::create($year, $month, 1)->addMonth();

            $slots = $this->getSlotsForDate($detail->booking->fk_field_id, $selectedDate, $detail);

            $response = view('tenant.booking.reschedule.index', compact(
                'detail', 'calendar', 'month', 'year', 'selectedDate',
                'prevMonth', 'nextMonth', 'slots'
            ));
        } catch (Throwable $e) {
            $bookingId = BookingDetail::find($detail_booking_id)->fk_booking_id ?? 1;
            $response = redirect()->route(self::ROUTE_HISTORY_SHOW, $bookingId)
                ->with('error', $e->getMessage());
        }

        return $response;
    }

    /**
     * Show preview review confirmation
     */
    public function confirmation(Request $request): RedirectResponse|View
    {
        $validator = Validator::make($request->all(), [
            'detail_booking_id' => 'required|exists:' . self::DB_CONN . '.booking_details,id',
            'new_play_date' => 'required|date|after_or_equal:today',
            'new_start_play_time' => 'required|date_format:H:i',
            'new_end_play_time' => 'required|date_format:H:i|after:new_start_play_time',
            'reason' => 'required|string|max:500',
        ]);

        $detailId = $request->detail_booking_id ?? 0;

        if ($validator->fails()) {
            if ($detailId === 0) {
                $response = redirect()->route(self::ROUTE_DASHBOARD)->with('error', 'Sesi tidak valid.');
            } else {
                $response = redirect()->route(self::ROUTE_RESCHEDULE_FORM, ['detail_booking_id' => $detailId])
                    ->with('error', 'Validasi gagal: ' . $validator->errors()->first());
            }
            return $response;
        }

        $validated = $validator->validated();

        try {
            $detail = BookingDetail::with('booking.field')->findOrFail($validated['detail_booking_id']);

            $this->authorizeAccess($detail);
            $this->checkRescheduleRules($detail);
            $this->checkSlotConflict($detail, $validated);

            $newPrice = $this->getNewPrice($detail, $validated);
            $oldPrice = $detail->price;
            $priceDiff = $newPrice - $oldPrice;

            $response = view('tenant.booking.reschedule.review', compact(
                'detail', 'validated', 'newPrice', 'oldPrice', 'priceDiff'
            ));
        } catch (Throwable $e) {
            $response = redirect()->route(self::ROUTE_RESCHEDULE_FORM, ['detail_booking_id' => $detailId])
                ->with('error', $e->getMessage());
        }

        return $response;
    }

    /**
     * Process transaction database update for rescheduling
     */
    public function process(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'detail_booking_id' => 'required|exists:' . self::DB_CONN . '.booking_details,id',
            'new_play_date' => 'required|date|after_or_equal:today',
            'new_start_play_time' => 'required|date_format:H:i',
            'new_end_play_time' => 'required|date_format:H:i|after:new_start_play_time',
            'reason' => 'required|string|max:500',
        ]);

        $detailId = $request->detail_booking_id ?? 0;

        if ($validator->fails()) {
            if ($detailId === 0) {
                $response = redirect()->route(self::ROUTE_DASHBOARD)->with('error', 'Sesi tidak valid.');
            } else {
                $response = redirect()->route(self::ROUTE_RESCHEDULE_FORM, ['detail_booking_id' => $detailId])
                    ->with('error', 'Validasi gagal: ' . $validator->errors()->first());
            }
            return $response;
        }

        $validated = $validator->validated();

        try {
            $detail = BookingDetail::with('booking')->findOrFail($validated['detail_booking_id']);

            $this->authorizeAccess($detail);
            $this->checkRescheduleRules($detail);
            $this->checkSlotConflict($detail, $validated);

            $newPrice = $this->getNewPrice($detail, $validated);
            $priceDiff = $newPrice - $detail->price;

            DB::connection(self::DB_CONN)->transaction(function () use ($detail, $validated, $newPrice, $priceDiff) {
                BookingReschedule::create([
                    'fk_booking_detail_id' => $detail->id,
                    'old_date'             => $detail->play_date,
                    'status_refund'        => $this->getRescheduleStatusRefund($priceDiff),
                    'reason'               => $validated['reason'],
                ]);

                $detail->update([
                    'play_date'       => $validated['new_play_date'],
                    'start_play_time' => $validated['new_start_play_time'],
                    'end_play_time'   => $validated['new_end_play_time'],
                    'price'           => $newPrice,
                    'status'          => self::STATUS_RESCHEDULE,
                ]);

                if ($priceDiff !== 0) {
                    Payment::create([
                        'fk_booking_id'        => $detail->fk_booking_id,
                        'fk_booking_detail_id' => $detail->id,
                        'reference_id'         => ($priceDiff > 0 ? 'RSCH-' : 'REF-') . strtoupper(Str::random(10)),
                        'payment_type'         => $priceDiff > 0 ? self::PAYMENT_RESCHEDULE_FEE : self::PAYMENT_REFUND,
                        'method'               => self::STR_CASH,
                        'amount'               => abs($priceDiff),
                        'status'               => 'success',
                        'paid_at'              => now(),
                    ]);
                }
        });

            $response = redirect()->route(self::ROUTE_HISTORY_SHOW, $detail->fk_booking_id)
                ->with('success', 'Reschedule berhasil! Jadwal booking telah diubah.');
        } catch (Throwable $e) {
            $response = redirect()->route(self::ROUTE_RESCHEDULE_FORM, ['detail_booking_id' => $detailId])
                ->with('error', $e->getMessage());
        }

        return $response;
    }

    /**
     * Solusi php:S112 - Menggunakan Specialized Exception InvalidArgumentException
     */
    private function authorizeAccess(BookingDetail $detail): void
    {
        if ($detail->booking->fk_user_id !== Auth::id()) {
            throw new InvalidArgumentException('Anda tidak memiliki akses ke booking ini.');
        }
    }

    /**
     * Solusi php:S112 - Menggunakan Specialized Exception UnexpectedValueException
     */
    private function checkRescheduleRules(BookingDetail $detail): void
    {
        $playDate = Carbon::parse($detail->play_date)->startOfDay();
        $daysUntilPlay = Carbon::now()->startOfDay()->diffInDays($playDate, false);

        if ($daysUntilPlay < 3) {
            throw new UnexpectedValueException('Reschedule hanya bisa dilakukan minimal H-3 sebelum jadwal bermain.');
        }

        if (BookingReschedule::where('fk_booking_detail_id', $detail->id)->exists()) {
            throw new UnexpectedValueException('Reschedule hanya dapat dilakukan 1 kali.');
        }
    }

    /**
     * Validasi ketersediaan overlap irisan waktu (php:S112 & php:S121)
     */
    private function checkSlotConflict(BookingDetail $detail, array $newSlot): void
    {
        $newStart = $newSlot['new_start_play_time'] . ':00';
        $newEnd = $newSlot['new_end_play_time'] . ':00';

        if ($newSlot['new_play_date'] === $detail->play_date && $newStart >= $detail->start_play_time && $newEnd <= $detail->end_play_time) {
            throw new UnexpectedValueException('Anda tidak bisa memilih waktu yang menjadi bagian dari jadwal Anda saat ini.');
        }

        $conflict = BookingDetail::whereHas('booking', fn($q) => $q->where('fk_field_id', $detail->booking->fk_field_id))
            ->where('id', '!=', $detail->id)
            ->where('play_date', $newSlot['new_play_date'])
            ->whereNotIn('status', [self::STATUS_CANCELLED, self::STATUS_FAILED, self::STATUS_EXPIRED])
            ->where('start_play_time', '<', $newSlot['new_end_play_time'])
            ->where('end_play_time', '>', $newSlot['new_start_play_time'])
            ->exists();

        $isClosed = false;
        if (Schema::connection(self::DB_CONN)->hasTable('field_closures')) {
            $newStartDT = $newSlot['new_play_date'] . ' ' . $newStart;
            $newEndDT = $newSlot['new_play_date'] . ' ' . $newEnd;

            $isClosed = DB::connection(self::DB_CONN)->table('field_closures')
                ->where('fk_field_id', $detail->booking->fk_field_id)
                ->where(function($query) use ($newStartDT, $newEndDT) {
                    $query->where('field_closure_start_time', '<', $newEndDT)
                          ->where('field_closure_end_time', '>', $newStartDT);
                })->exists();
        }

        if ($conflict || $isClosed) {
            throw new UnexpectedValueException('Slot yang dipilih sudah dibooking atau lapangan sedang ditutup.');
        }
    }

    private function getNewPrice(BookingDetail $detail, array $newSlot): int
    {
        $dayName = strtolower(Carbon::parse($newSlot['new_play_date'])->englishDayOfWeek);
        $price = FieldPrice::where('fk_field_id', $detail->booking->fk_field_id)
            ->where('day_type', $dayName)
            ->where('start_time', '<=', $newSlot['new_start_play_time'])
            ->where('end_time', '>=', $newSlot['new_end_play_time'])
            ->value('price');

        if (!$price) {
            throw new UnexpectedValueException('Harga untuk jadwal baru tidak ditemukan.');
        }
        return $price;
    }

    private function getRescheduleStatusRefund(int $priceDiff): string
    {
        if ($priceDiff > 0) {
            return self::STATUS_REFUND_DEPOSIT;
        }
        if ($priceDiff < 0) {
            return self::STATUS_REFUND_REFUND;
        }
        return self::STATUS_REFUND_NONE;
    }

    private function generateCalendar(int $month, int $year): array
    {
        $firstDay = Carbon::create($year, $month, 1);
        $start = $firstDay->copy()->startOfWeek(Carbon::SUNDAY);
        $end = $firstDay->copy()->lastOfMonth()->endOfWeek(Carbon::SATURDAY);

        $days = [];
        for ($current = $start->copy(); $current <= $end; $current->addDay()) {
            $days[] = [
                'date'           => $current->format('Y-m-d'),
                'day'            => (int) $current->format('j'),
                'isCurrentMonth' => $current->month === $month,
                'isToday'        => $current->isToday(),
                'isPast'         => $current->isPast() && !$current->isToday(),
            ];
        }
        return $days;
    }

    /**
     * Get available visual slots for date
     * Solusi php:S3776 - Memecah Kompleksitas Perulangan Bersarang ke Sub-Method Terpisah
     */
    private function getSlotsForDate(int $fieldId, string $date, BookingDetail $originalDetail): array
    {
        $dayName = strtolower(Carbon::parse($date)->englishDayOfWeek);
        $priceRules = FieldPrice::where('fk_field_id', $fieldId)->where('day_type', $dayName)->orderBy('start_time')->get();

        $occupied = BookingDetail::whereHas('booking', fn ($q) => $q->where('fk_field_id', $fieldId))
            ->where('id', '!=', $originalDetail->id)
            ->where('play_date', $date)
            ->whereNotIn('status', [self::STATUS_CANCELLED, self::STATUS_FAILED, self::STATUS_EXPIRED])
            ->get(['start_play_time', 'end_play_time']);

        $closures = $this->getFieldClosuresForDate($fieldId, $date);

        $slots = [];
        foreach ($priceRules as $rule) {
            $slots = array_merge($slots, $this->buildSlotsFromRule($rule, $date, $occupied, $closures, $originalDetail));
        }
        return $slots;
    }

    private function getFieldClosuresForDate(int $fieldId, string $date): array
    {
        if (Schema::connection(self::DB_CONN)->hasTable('field_closures')) {
            return DB::connection(self::DB_CONN)->table('field_closures')
                ->where('fk_field_id', $fieldId)
                ->where('field_closure_start_time', '<=', $date . ' 23:59:59')
                ->where('field_closure_end_time', '>=', $date . ' 00:00:00')
                ->get()
                ->toArray();
        }
        return [];
    }

    private function buildSlotsFromRule($rule, string $date, $occupied, array $closures, BookingDetail $originalDetail): array
    {
        $slots = [];
        $start = Carbon::parse($rule->start_time);
        $end = Carbon::parse($rule->end_time);

        for ($current = $start->copy(); $current < $end; $current->addHour()) {
            $slotStartDB = $current->format('H:i:s');
            $slotEndDB = $current->copy()->addHour()->format('H:i:s');

            $isOccupiedByOther = $occupied->contains(fn($b) => $slotStartDB < $b->end_play_time && $slotEndDB > $b->start_play_time);
            $isClosed = $this->isTimeClosed($date . ' ' . $slotStartDB, $date . ' ' . $slotEndDB, $closures);
            $isOriginalSlot = ($date === $originalDetail->play_date) && ($slotStartDB >= $originalDetail->start_play_time && $slotEndDB <= $originalDetail->end_play_time);

            $slots[] = [
                'start'        => $current->format('H:i'),
                'end'          => $current->copy()->addHour()->format('H:i'),
                'price'        => $rule->price,
                'is_available' => !$isOccupiedByOther && !$isClosed && !$isOriginalSlot,
                'is_original'  => $isOriginalSlot,
                'is_closed'    => $isClosed
            ];
        }
        return $slots;
    }

    private function isTimeClosed(string $startDT, string $endDT, array $closures): bool
    {
        $closed = false;
        foreach ($closures as $closure) {
            if ($startDT < $closure->field_closure_end_time && $endDT > $closure->field_closure_start_time) {
                $closed = true;
                break;
            }
        }
        return $closed;
    }
}
