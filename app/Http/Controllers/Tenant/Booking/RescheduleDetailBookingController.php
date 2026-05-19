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

class RescheduleDetailBookingController extends Controller
{
    const PAYMENT_RESCHEDULE_FEE = 'reschedule fee';
    const PAYMENT_REFUND = 'refund';
    const STATUS_REFUND_DEPOSIT = 'deposit required';
    const STATUS_REFUND_REFUND = 'refund required';
    const STATUS_REFUND_NONE = 'none';

    public function formInput(Request $request, $detail_booking_id)
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

            return view('tenant.bookings.reschedule.index', compact(
                'detail', 'calendar', 'month', 'year', 'selectedDate',
                'prevMonth', 'nextMonth', 'slots'
            ));
        } catch (\Exception $e) {
            $bookingId = BookingDetail::find($detail_booking_id)->fk_booking_id ?? 1;
            return redirect()->route('tenant.booking.history.show', $bookingId)
                ->with('error', $e->getMessage());
        }
    }

    public function confirmation(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'detail_booking_id' => 'required|exists:mysql_joglo66_app.booking_details,id',
            'new_play_date' => 'required|date|after_or_equal:today',
            'new_start_play_time' => 'required|date_format:H:i',
            'new_end_play_time' => 'required|date_format:H:i|after:new_start_play_time',
            'reason' => 'required|string|max:500',
        ]);

        // Gunakan $request->detail_booking_id, jika null arahkan ke dashboard
        $detailId = $request->detail_booking_id ?? 0;

        if ($validator->fails()) {
            if ($detailId === 0) return redirect()->route('tenant.booking.dashboard')->with('error', 'Sesi tidak valid.');
            return redirect()->route('tenant.booking.form.reschedule', ['detail_booking_id' => $detailId])
                ->with('error', 'Validasi gagal: ' . $validator->errors()->first());
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

            return view('tenant.bookings.reschedule.review', compact(
                'detail', 'validated', 'newPrice', 'oldPrice', 'priceDiff'
            ));
        } catch (\Exception $e) {
            return redirect()->route('tenant.booking.form.reschedule', ['detail_booking_id' => $detailId])
                ->with('error', $e->getMessage());
        }
    }

    public function process(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'detail_booking_id' => 'required|exists:mysql_joglo66_app.booking_details,id',
            'new_play_date' => 'required|date|after_or_equal:today',
            'new_start_play_time' => 'required|date_format:H:i',
            'new_end_play_time' => 'required|date_format:H:i|after:new_start_play_time',
            'reason' => 'required|string|max:500',
        ]);

        $detailId = $request->detail_booking_id ?? 0;

        if ($validator->fails()) {
            if ($detailId === 0) return redirect()->route('tenant.booking.dashboard')->with('error', 'Sesi tidak valid.');
            return redirect()->route('tenant.booking.form.reschedule', ['detail_booking_id' => $detailId])
                ->with('error', 'Validasi gagal: ' . $validator->errors()->first());
        }

        $validated = $validator->validated();

        try {
            $detail = BookingDetail::with('booking')->findOrFail($validated['detail_booking_id']);

            $this->authorizeAccess($detail);
            $this->checkRescheduleRules($detail);
            $this->checkSlotConflict($detail, $validated);

            $newPrice = $this->getNewPrice($detail, $validated);
            $priceDiff = $newPrice - $detail->price;

            DB::connection('mysql_joglo66_app')->transaction(function () use ($detail, $validated, $newPrice, $priceDiff) {
                BookingReschedule::create([
                    'fk_booking_detail_id' => $detail->id,
                    'old_date' => $detail->play_date,
                    'status_refund' => $this->getRescheduleStatusRefund($priceDiff),
                    'reason' => $validated['reason'],
                ]);

                $detail->update([
                    'play_date' => $validated['new_play_date'],
                    'start_play_time' => $validated['new_start_play_time'],
                    'end_play_time' => $validated['new_end_play_time'],
                    'price' => $newPrice,
                    'status' => 'reschedule',
                ]);

                if ($priceDiff !== 0) {
                    Payment::create([
                        'fk_booking_id' => $detail->fk_booking_id,
                        'fk_booking_detail_id' => $detail->id,
                        'reference_id' => ($priceDiff > 0 ? 'RSCH-' : 'REF-') . strtoupper(Str::random(10)),
                        'payment_type' => $priceDiff > 0 ? self::PAYMENT_RESCHEDULE_FEE : self::PAYMENT_REFUND,
                        'method' => 'cash',
                        'amount' => abs($priceDiff),
                        'status' => 'success',
                        'paid_at' => now(),
                    ]);
                }
            });

            return redirect()->route('tenant.booking.history.show', $detail->fk_booking_id)
                ->with('success', 'Reschedule berhasil! Jadwal booking telah diubah.');

        } catch (\Exception $e) {
            return redirect()->route('tenant.booking.form.reschedule', ['detail_booking_id' => $detailId])
                ->with('error', $e->getMessage());
        }
    }

    // ============================================
    // PRIVATE HELPERS (DRY & Clean Code)
    // ============================================

    private function authorizeAccess($detail)
    {
        if ($detail->booking->fk_user_id !== Auth::id()) {
            throw new \Exception('Anda tidak memiliki akses ke booking ini.');
        }
    }

    private function checkRescheduleRules($detail)
    {
        $playDate = Carbon::parse($detail->play_date)->startOfDay();
        $daysUntilPlay = Carbon::now()->startOfDay()->diffInDays($playDate, false);

        if ($daysUntilPlay < 3) {
            throw new \Exception('Reschedule hanya bisa dilakukan minimal H-3 sebelum jadwal bermain.');
        }

        if (BookingReschedule::where('fk_booking_detail_id', $detail->id)->exists()) {
            throw new \Exception('Reschedule hanya dapat dilakukan 1 kali.');
        }
    }

    private function checkSlotConflict($detail, $newSlot)
    {
        $newStart = $newSlot['new_start_play_time'] . ':00';
        $newEnd = $newSlot['new_end_play_time'] . ':00';

        if ($newSlot['new_play_date'] === $detail->play_date &&
            $newStart >= $detail->start_play_time &&
            $newEnd <= $detail->end_play_time) {
            throw new \Exception('Anda tidak bisa memilih waktu yang menjadi bagian dari jadwal Anda saat ini.');
        }

        $conflict = BookingDetail::whereHas('booking', fn($q) => $q->where('fk_field_id', $detail->booking->fk_field_id))
            ->where('id', '!=', $detail->id)
            ->where('play_date', $newSlot['new_play_date'])
            ->whereNotIn('status', ['cancelled', 'failed', 'expired'])
            ->where('start_play_time', '<', $newSlot['new_end_play_time'])
            ->where('end_play_time', '>', $newSlot['new_start_play_time'])
            ->exists();

        $isClosed = false;
        if (Schema::connection('mysql_joglo66_app')->hasTable('field_closures')) {
            $newStartDT = $newSlot['new_play_date'] . ' ' . $newStart;
            $newEndDT = $newSlot['new_play_date'] . ' ' . $newEnd;

            $isClosed = DB::connection('mysql_joglo66_app')->table('field_closures')
                ->where('fk_field_id', $detail->booking->fk_field_id)
                ->where(function($query) use ($newStartDT, $newEndDT) {
                    $query->where('field_closure_start_time', '<', $newEndDT)
                          ->where('field_closure_end_time', '>', $newStartDT);
                })->exists();
        }

        if ($conflict || $isClosed) throw new \Exception('Slot yang dipilih sudah dibooking atau lapangan sedang ditutup.');
    }

    private function getNewPrice($detail, $newSlot)
    {
        $dayName = strtolower(Carbon::parse($newSlot['new_play_date'])->englishDayOfWeek);
        $price = FieldPrice::where('fk_field_id', $detail->booking->fk_field_id)
            ->where('day_type', $dayName)
            ->where('start_time', '<=', $newSlot['new_start_play_time'])
            ->where('end_time', '>=', $newSlot['new_end_play_time'])
            ->value('price');

        if (!$price) throw new \Exception('Harga untuk jadwal baru tidak ditemukan.');
        return $price;
    }

    private function getRescheduleStatusRefund(int $priceDiff): string
    {
        if ($priceDiff > 0) return self::STATUS_REFUND_DEPOSIT;
        if ($priceDiff < 0) return self::STATUS_REFUND_REFUND;
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
                'date' => $current->format('Y-m-d'),
                'day' => (int) $current->format('j'),
                'isCurrentMonth' => $current->month === $month,
                'isToday' => $current->isToday(),
                'isPast' => $current->isPast() && !$current->isToday(),
            ];
        }
        return $days;
    }

    private function getSlotsForDate(int $fieldId, string $date, BookingDetail $originalDetail): array
    {
        $dayName = strtolower(Carbon::parse($date)->englishDayOfWeek);
        $priceRules = FieldPrice::where('fk_field_id', $fieldId)->where('day_type', $dayName)->orderBy('start_time')->get();

        $occupied = BookingDetail::whereHas('booking', fn ($q) => $q->where('fk_field_id', $fieldId))
            ->where('id', '!=', $originalDetail->id)
            ->where('play_date', $date)
            ->whereNotIn('status', ['cancelled', 'failed', 'expired'])
            ->get(['start_play_time', 'end_play_time']);

        $closures = [];
        if (Schema::connection('mysql_joglo66_app')->hasTable('field_closures')) {
            $startOfDay = $date . ' 00:00:00';
            $endOfDay = $date . ' 23:59:59';

            $closures = DB::connection('mysql_joglo66_app')->table('field_closures')
                ->where('fk_field_id', $fieldId)
                ->where('field_closure_start_time', '<=', $endOfDay)
                ->where('field_closure_end_time', '>=', $startOfDay)
                ->get();
        }

        $slots = [];
        foreach ($priceRules as $rule) {
            for ($current = Carbon::parse($rule->start_time); $current < Carbon::parse($rule->end_time); $current->addHour()) {
                $slotStartDB = $current->format('H:i:s');
                $slotEndDB = $current->copy()->addHour()->format('H:i:s');

                $slotStartView = $current->format('H:i');
                $slotEndView = $current->copy()->addHour()->format('H:i');

                $isOccupiedByOther = $occupied->contains(function ($b) use ($slotStartDB, $slotEndDB) {
                    return $slotStartDB < $b->end_play_time && $slotEndDB > $b->start_play_time;
                });

                $slotStartDT = $date . ' ' . $slotStartDB;
                $slotEndDT = $date . ' ' . $slotEndDB;

                $isClosed = false;
                foreach ($closures as $closure) {
                    if ($slotStartDT < $closure->field_closure_end_time && $slotEndDT > $closure->field_closure_start_time) {
                        $isClosed = true;
                        break;
                    }
                }

                $isOriginalSlot = false;
                if ($date === $originalDetail->play_date) {
                    if ($slotStartDB >= $originalDetail->start_play_time && $slotEndDB <= $originalDetail->end_play_time) {
                        $isOriginalSlot = true;
                    }
                }

                $isAvailable = !$isOccupiedByOther && !$isClosed && !$isOriginalSlot;

                $slots[] = [
                    'start' => $slotStartView,
                    'end' => $slotEndView,
                    'price' => $rule->price,
                    'is_available' => $isAvailable,
                    'is_original' => $isOriginalSlot,
                    'is_closed' => $isClosed
                ];
            }
        }
        return $slots;
    }
}
