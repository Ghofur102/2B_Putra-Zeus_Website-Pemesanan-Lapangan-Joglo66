<?php

namespace App\Http\Controllers\Tenant\Booking;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingCancelled;
use App\Models\BookingDetail;
use App\Models\BookingReschedule;
use App\Models\Field;
use App\Models\FieldPrice;
use App\Models\Payment;
use App\Services\DuitkuService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BookingController extends Controller
{
    public function createForm(Request $request)
    {
        $fieldId = $request->query('field_id');
        if (!$fieldId) {
            return redirect()->route('tenant.booking.dashboard')
                ->with('info', 'Silakan pilih lapangan terlebih dahulu');
        }

        $field = Field::findOrFail($fieldId);

        return view('tenant.booking.create', [
            'field' => $field,
        ]);
    }

    public function confirmForm(Request $request)
    {
        $request->validate([
            'field_id' => 'required|exists:mysql_joglo66_app.fields,id',
            'selected_slots' => 'required|string',
        ]);

        $field = Field::findOrFail($request->field_id);

        $selectedSlotsRaw = json_decode($request->selected_slots, true);

        $totalPrice = 0;
        $groupedSlots = [];

        foreach ($selectedSlotsRaw as $item) {
            $playDate = $item['date'];
            $startTime = Carbon::parse($item['jam'])->format('H:i:s');
            $endTime = Carbon::parse($item['jam_akhir'])->format('H:i:s');
            $dayType = strtolower(Carbon::parse($playDate)->format('l'));

            $fieldPrice = FieldPrice::where('fk_field_id', $field->id)
                ->where('day_type', $dayType)
                ->whereTime('start_time', '<=', $startTime)
                ->whereTime('end_time', '>=', $endTime)
                ->first();

            $price = $fieldPrice ? $fieldPrice->price : 0;
            $totalPrice += $price;

            if (!isset($groupedSlots[$playDate])) {
                $groupedSlots[$playDate] = [];
            }
            $groupedSlots[$playDate][] = [
                'jam' => $item['jam'],
                'jam_akhir' => $item['jam_akhir'],
                'harga' => $price
            ];
        }

        ksort($groupedSlots);

        return view('tenant.booking.confirmation', compact(
            'field',
            'groupedSlots',
            'totalPrice'
        ));
    }

    public function store(Request $request, DuitkuService $duitkuService)
    {
        $validated = $request->validate([
            'field_id' => 'required|exists:mysql_joglo66_app.fields,id',
            'team_name' => 'required|string|max:50',
            'phone_number' => 'required|string|max:50',
            'customer_email' => 'required|email|max:50',
            'notes' => 'nullable|string',
            'booking_data' => 'required|json',
            'payment_type' => 'required|in:down payment,final payment',
        ]);

        $fieldId = $validated['field_id'];
        $userId = \Illuminate\Support\Facades\Auth::id() ?? 1;
        $groupedSlots = json_decode($validated['booking_data'], true);

        if (empty($groupedSlots)) {
            return redirect()->route('tenant.booking.dashboard')
                ->with('error', 'Pilih minimal satu slot pemesanan.');
        }

        DB::connection('mysql_joglo66_app')->beginTransaction();

        try {
            foreach ($groupedSlots as $playDate => $slots) {
                foreach ($slots as $slot) {
                    $isBooked = BookingDetail::whereHas('booking', function ($query) use ($fieldId) {
                            $query->where('fk_field_id', $fieldId);
                        })
                        ->where('play_date', $playDate)
                        ->where('start_play_time', $slot['jam'])
                        ->where('end_play_time', $slot['jam_akhir'])
                        ->whereIn('status', ['active', 'waiting'])
                        ->lockForUpdate()
                        ->exists();

                    if ($isBooked) {
                        throw new \Exception("Slot {$slot['jam']} - {$slot['jam_akhir']} pada {$playDate} sudah dipesan.");
                    }
                }
            }

            $booking = new Booking();
            $booking->fk_user_id = $userId;
            $booking->fk_field_id = $fieldId;
            $booking->team_name = $validated['team_name'];
            $booking->customer_phone = $validated['phone_number'];
            $booking->customer_email = $validated['customer_email'];
            $booking->notes = $validated['notes'] ?? '-';
            $booking->booking_date = now()->format('Y-m-d');
            $booking->save();

            $totalPrice = 0;

            foreach ($groupedSlots as $playDate => $slots) {
                foreach ($slots as $slot) {
                    $totalPrice += $slot['harga'];

                    $bookingDetail = new BookingDetail();
                    $bookingDetail->fk_booking_id = $booking->id;
                    $bookingDetail->start_play_time = $slot['jam'];
                    $bookingDetail->end_play_time = $slot['jam_akhir'];
                    $bookingDetail->play_date = $playDate;
                    $bookingDetail->price = $slot['harga'];
                    $bookingDetail->status = 'waiting';
                    $bookingDetail->save();
                }
            }

            $amountToPay = $request->payment_type === 'down payment' ? ($totalPrice / 2) : $totalPrice;

            $duitkuResponse = $duitkuService->createInvoice($booking, $amountToPay);

            $payment = new Payment();
            $payment->fk_booking_id = $booking->id;
            $payment->reference_id = $duitkuResponse->reference;
            $payment->payment_url = $duitkuResponse->paymentUrl ?? '-';
            $payment->payment_type = $request->payment_type;
            $payment->method = 'transfer';
            $payment->amount = $amountToPay;
            $payment->status = 'pending';
            $payment->save();

            DB::connection('mysql_joglo66_app')->commit();

            return view('tenant.booking.checkout', [
                'booking' => $booking,
                'reference' => $duitkuResponse->reference,
                'amountToPay' => $amountToPay
            ]);
        } catch (\Exception $e) {
            DB::connection('mysql_joglo66_app')->rollBack();

            return redirect()->route('tenant.booking.dashboard')
                ->with('error', 'Transaksi gagal: ' . $e->getMessage() . ' Silakan ulangi.');
        }
    }

    public function success(int $bookingId)
    {
        $booking = \App\Models\Booking::with(['details', 'field', 'user'])
            ->findOrFail($bookingId);

        return view('tenant.booking.success', [
            'booking' => $booking,
        ]);
    }

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

        $playDate = Carbon::parse($detail->play_date);
        $daysUntilPlay = Carbon::now()->diffInDays($playDate, false);
        if ($daysUntilPlay < 3) {
            return redirect()->back()->with('error', 'Reschedule hanya bisa dilakukan minimal H-3 sebelum jadwal bermain.');
        }

        $existingReschedule = BookingReschedule::where('fk_booking_detail_id', $detail->id)->exists();
        if ($existingReschedule) {
            return redirect()->back()->with('error', 'Reschedule hanya dapat dilakukan 1 kali.');
        }

        $newSlot = [
            'play_date' => $validated['new_play_date'],
            'start_play_time' => $validated['new_start_play_time'],
            'end_play_time' => $validated['new_end_play_time'],
        ];
        if ($this->hasSlotConflict($detail->booking->fk_field_id, $newSlot, $detail->id)) {
            return redirect()->back()->with('error', 'Slot yang dipilih sudah dibooking oleh orang lain.');
        }

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

        if (! $request->has('confirmed')) {
            return view('tenant.booking.reschedule.review', compact(
                'detail', 'validated', 'newPrice', 'oldPrice', 'priceDiff'
            ));
        }

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
                BookingCancelled::create([
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
                'isPast' => $current->isPast() && !$current->isToday(),
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
