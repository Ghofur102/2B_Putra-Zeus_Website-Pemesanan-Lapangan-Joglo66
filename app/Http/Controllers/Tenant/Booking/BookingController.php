<?php

namespace App\Http\Controllers\Tenant\Booking;

use App\Http\Controllers\Controller;
use App\Models\BookingCancle;
use App\Models\BookingDetail;
use App\Models\BookingReschedule;
use App\Models\FieldPrice;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BookingController extends Controller
{
    public function createForm($scheduleId) {}

    public function confirmForm(Request $request) {}

    public function store(Request $request) {}

    // ============================================
    // RESCHEDULE
    // ============================================

    public function showRescheduleForm(Request $request, $detail_booking_id)
    {
        $detail = BookingDetail::with('booking.field.fieldPrices')
            ->findOrFail($detail_booking_id);

        if ($detail->booking->fk_user_id !== auth()->id()) {
            abort(403);
        }

        $playDate = Carbon::parse($detail->play_date);
        $daysUntilPlay = Carbon::now()->diffInDays($playDate, false);
        if ($daysUntilPlay < 3) {
            return redirect()->route('booking.history.show', $detail_booking_id)
                ->with('error', 'Reschedule hanya bisa dilakukan minimal H-3 sebelum jadwal bermain.');
        }

        $alreadyRescheduled = BookingReschedule::where('fk_booking_detail_id', $detail->id)->exists();
        if ($alreadyRescheduled) {
            return redirect()->route('booking.history.show', $detail_booking_id)
                ->with('error', 'Reschedule hanya dapat dilakukan 1 kali.');
        }

        $selectedDate = $request->query('date', date('Y-m-d'));
        $month = (int) $request->query('month', date('m'));
        $year = (int) $request->query('year', date('Y'));

        $calendar = $this->generateCalendar($month, $year);
        $prevMonth = Carbon::create($year, $month, 1)->subMonth();
        $nextMonth = Carbon::create($year, $month, 1)->addMonth();
        $slots = $this->getSlotsForDate($detail->booking->fk_field_id, $selectedDate, $detail->id);

        return view('tenant.booking.reschedule.index', compact(
            'detail', 'calendar', 'month', 'year', 'selectedDate',
            'prevMonth', 'nextMonth', 'slots'
        ));
    }

    public function processReschedule(Request $request, $detail_booking_id)
    {
        $detail = BookingDetail::with('booking.field')
            ->findOrFail($detail_booking_id);

        if ($detail->booking->fk_user_id !== auth()->id()) {
            return redirect()->back()->with('error', 'Anda tidak memiliki akses ke booking ini.');
        }

        $validated = $request->validate([
            'new_play_date' => 'required|date|after_or_equal:today',
            'new_start_play_time' => 'required|date_format:H:i',
            'new_end_play_time' => 'required|date_format:H:i|after:new_start_play_time',
            'reason' => 'required|string|max:500',
            'confirmed' => 'nullable|in:1',
        ]);

        // Cek H-3
        $playDate = Carbon::parse($detail->play_date);
        $daysUntilPlay = Carbon::now()->diffInDays($playDate, false);
        if ($daysUntilPlay < 3) {
            return redirect()->back()->with('error', 'Reschedule hanya bisa dilakukan minimal H-3 sebelum jadwal bermain.');
        }

        // Cek 1x reschedule
        $existingReschedule = BookingReschedule::where('fk_booking_detail_id', $detail->id)->exists();
        if ($existingReschedule) {
            return redirect()->back()->with('error', 'Reschedule hanya dapat dilakukan 1 kali.');
        }

        // Cek ketersediaan slot baru
        $newSlot = [
            'play_date' => $validated['new_play_date'],
            'start_play_time' => $validated['new_start_play_time'],
            'end_play_time' => $validated['new_end_play_time'],
        ];
        if ($this->hasSlotConflict($detail->booking->fk_field_id, $newSlot, $detail->id)) {
            return redirect()->back()->with('error', 'Slot yang dipilih sudah dibooking oleh orang lain.');
        }

        // Hitung harga baru
        $dayName = strtolower(Carbon::parse($validated['new_play_date'])->englishDayOfWeek);
        $newPrice = FieldPrice::where('fk_field_id', $detail->booking->fk_field_id)
            ->where('day_type', $dayName)
            ->where('start_time', '<=', $validated['new_start_play_time'])
            ->where('end_time', '>=', $validated['new_end_play_time'])
            ->value('price');

        if (! $newPrice) {
            return redirect()->back()->with('error', 'Harga untuk jadwal baru tidak ditemukan.');
        }

        $oldPrice = $detail->price;
        $priceDiff = $newPrice - $oldPrice;

        // Jika belum konfirmasi, tampilkan halaman review
        if (! $request->has('confirmed')) {
            return view('tenant.booking.reschedule.review', compact(
                'detail', 'validated', 'newPrice', 'oldPrice', 'priceDiff'
            ));
        }

        // Proses reschedule
        try {
            DB::transaction(function () use ($detail, $validated, $newPrice, $priceDiff) {
                BookingReschedule::create([
                    'fk_booking_detail_id' => $detail->id,
                    'fk_field_closure_id' => null,
                    'old_date' => $detail->play_date,
                    'status_refund' => $priceDiff > 0
                        ? 'deposit required'
                        : ($priceDiff < 0 ? 'refund required' : 'none'),
                    'reason' => $validated['reason'],
                ]);

                $detail->update([
                    'play_date' => $validated['new_play_date'],
                    'start_play_time' => $validated['new_start_play_time'],
                    'end_play_time' => $validated['new_end_play_time'],
                    'price' => $newPrice,
                    'status' => 'reschedule',
                ]);

                if ($priceDiff > 0) {
                    Payment::create([
                        'fk_booking_id' => $detail->fk_booking_id,
                        'fk_booking_detail_id' => $detail->id,
                        'reference_id' => 'RSCH-'.strtoupper(Str::random(10)),
                        'payment_url' => null,
                        'payment_type' => 'reschedule fee',
                        'method' => 'cash',
                        'amount' => $priceDiff,
                        'status' => 'success',
                        'paid_at' => now(),
                    ]);
                } elseif ($priceDiff < 0) {
                    Payment::create([
                        'fk_booking_id' => $detail->fk_booking_id,
                        'fk_booking_detail_id' => $detail->id,
                        'reference_id' => 'REF-'.strtoupper(Str::random(10)),
                        'payment_url' => null,
                        'payment_type' => 'refund',
                        'method' => 'cash',
                        'amount' => abs($priceDiff),
                        'status' => 'success',
                        'paid_at' => now(),
                    ]);
                }
            });
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal mereschedule: '.$e->getMessage());
        }

        return redirect()->route('booking.history.show', $detail_booking_id)
            ->with('success', 'Reschedule berhasil! Jadwal booking telah diubah.');
    }

    // ============================================
    // CANCEL BOOKING
    // ============================================

    public function showCancelForm($detail_booking_id)
    {
        $detail = BookingDetail::with('booking.field')
            ->findOrFail($detail_booking_id);

        if ($detail->booking->fk_user_id !== auth()->id()) {
            abort(403);
        }

        $playDate = Carbon::parse($detail->play_date);
        $daysUntilPlay = Carbon::now()->diffInDays($playDate, false);

        $totalPaid = Payment::where('fk_booking_id', $detail->fk_booking_id)
            ->where('status', 'success')
            ->whereIn('payment_type', ['down payment', 'final payment', 'reschedule fee'])
            ->sum('amount');

        $totalRefunded = Payment::where('fk_booking_id', $detail->fk_booking_id)
            ->where('status', 'success')
            ->where('payment_type', 'refund')
            ->sum('amount');

        $netPaid = $totalPaid - $totalRefunded;
        $isRefundable = $daysUntilPlay >= 3;
        $refundAmount = $isRefundable ? $netPaid : 0;

        return view('tenant.booking.cancel.index', compact(
            'detail', 'playDate', 'daysUntilPlay', 'netPaid', 'isRefundable', 'refundAmount'
        ));
    }

    public function processCancel(Request $request, $detail_booking_id)
    {
        $detail = BookingDetail::with('booking')
            ->findOrFail($detail_booking_id);

        if ($detail->booking->fk_user_id !== auth()->id()) {
            return redirect()->back()->with('error', 'Anda tidak memiliki akses ke booking ini.');
        }

        if ($detail->status === 'cancelled') {
            return redirect()->back()->with('error', 'Booking ini sudah dibatalkan sebelumnya.');
        }

        $validated = $request->validate([
            'reason' => 'required|string|max:500',
            'confirmed' => 'nullable|in:1',
        ]);

        $playDate = Carbon::parse($detail->play_date);
        $daysUntilPlay = Carbon::now()->diffInDays($playDate, false);

        $totalPaid = Payment::where('fk_booking_id', $detail->fk_booking_id)
            ->where('status', 'success')
            ->whereIn('payment_type', ['down payment', 'final payment', 'reschedule fee'])
            ->sum('amount');

        $totalRefunded = Payment::where('fk_booking_id', $detail->fk_booking_id)
            ->where('status', 'success')
            ->where('payment_type', 'refund')
            ->sum('amount');

        $netPaid = $totalPaid - $totalRefunded;
        $isRefundable = $daysUntilPlay >= 3;
        $refundAmount = $isRefundable ? $netPaid : 0;

        if (! $request->has('confirmed')) {
            return view('tenant.booking.cancel.review', compact(
                'detail', 'validated', 'isRefundable', 'refundAmount', 'netPaid', 'daysUntilPlay'
            ));
        }

        try {
            DB::transaction(function () use ($detail, $validated, $isRefundable, $refundAmount) {
                BookingCancle::create([
                    'fk_booking_detail_id' => $detail->id,
                    'fk_field_closure_id' => null,
                    'cancle_date' => Carbon::now()->toDateString(),
                    'reason' => $validated['reason'],
                    'status_refund' => $isRefundable ? 'refundable' : 'non-refundable',
                ]);

                $detail->update([
                    'status' => 'cancelled',
                ]);

                if ($isRefundable && $refundAmount > 0) {
                    Payment::create([
                        'fk_booking_id' => $detail->fk_booking_id,
                        'fk_booking_detail_id' => $detail->id,
                        'reference_id' => 'CNL-REF-'.strtoupper(Str::random(10)),
                        'payment_url' => null,
                        'payment_type' => 'refund',
                        'method' => 'cash',
                        'amount' => $refundAmount,
                        'status' => 'success',
                        'paid_at' => now(),
                    ]);
                }
            });
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal membatalkan booking: '.$e->getMessage());
        }

        return redirect()->route('booking.history')
            ->with('success', 'Booking berhasil dibatalkan.');
    }

    // ============================================
    // HELPER
    // ============================================

    private function generateCalendar(int $month, int $year): array
    {
        $firstDay = Carbon::create($year, $month, 1);
        $lastDay = $firstDay->copy()->lastOfMonth();
        $start = $firstDay->copy()->startOfWeek(Carbon::SUNDAY);
        $end = $lastDay->copy()->endOfWeek(Carbon::SATURDAY);

        $days = [];
        $current = $start->copy();
        while ($current <= $end) {
            $days[] = [
                'date' => $current->format('Y-m-d'),
                'day' => (int) $current->format('j'),
                'isCurrentMonth' => $current->month === $month,
                'isToday' => $current->isToday(),
                'isPast' => $current->isPast() && ! $current->isToday(),
            ];
            $current->addDay();
        }

        return $days;
    }

    private function getSlotsForDate(int $fieldId, string $date, int $excludeDetailId): array
    {
        $dayName = strtolower(Carbon::parse($date)->englishDayOfWeek);

        $priceRules = FieldPrice::where('fk_field_id', $fieldId)
            ->where('day_type', $dayName)
            ->orderBy('start_time')
            ->get();

        $occupied = BookingDetail::whereHas('booking', fn ($q) => $q->where('fk_field_id', $fieldId))
            ->where('id', '!=', $excludeDetailId)
            ->where('play_date', $date)
            ->whereNotIn('status', ['cancelled', 'field closure'])
            ->get(['start_play_time', 'end_play_time']);

        $slots = [];
        foreach ($priceRules as $rule) {
            $start = Carbon::parse($rule->start_time);
            $end = Carbon::parse($rule->end_time);
            $current = $start->copy();

            while ($current < $end) {
                $slotStart = $current->format('H:i');
                $next = $current->copy()->addHour();
                $slotEnd = $next->format('H:i');
                if ($next > $end) {
                    break;
                }

                $isAvailable = true;
                foreach ($occupied as $b) {
                    if ($slotStart < $b->end_play_time && $slotEnd > $b->start_play_time) {
                        $isAvailable = false;
                        break;
                    }
                }

                $slots[] = [
                    'start' => $slotStart,
                    'end' => $slotEnd,
                    'price' => $rule->price,
                    'is_available' => $isAvailable,
                ];
                $current->addHour();
            }
        }

        return $slots;
    }

    private function hasSlotConflict(int $fieldId, array $newSlot, int $excludeDetailId): bool
    {
        return BookingDetail::whereHas('booking', function ($q) use ($fieldId) {
            $q->where('fk_field_id', $fieldId);
        })
            ->where('id', '!=', $excludeDetailId)
            ->where('play_date', $newSlot['play_date'])
            ->whereNotIn('status', ['cancelled', 'field closure'])
            ->where('start_play_time', '<', $newSlot['end_play_time'])
            ->where('end_play_time', '>', $newSlot['start_play_time'])
            ->exists();
    }
}
