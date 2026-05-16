<?php

namespace App\Http\Controllers\Tenant\Booking;

use App\Http\Controllers\Controller;
use App\Models\BookingDetail;
use App\Models\BookingReschedule;
use Carbon\Carbon;

class HistoryController extends Controller
{
    public function index()
    {
        $userId = auth()->id();

        $details = BookingDetail::whereHas('booking', function ($q) use ($userId) {
            $q->where('fk_user_id', $userId);
        })
            ->with(['booking.field'])
            ->orderBy('play_date', 'desc')
            ->orderBy('start_play_time', 'desc')
            ->get();

        return view('tenant.booking.history.index', compact('details'));
    }

    public function show($detail_booking_id)
    {
        $detail = BookingDetail::with([
            'booking.field',
            'booking.payments',
            'booking.user',
        ])->findOrFail($detail_booking_id);

        if ($detail->booking->fk_user_id !== auth()->id()) {
            abort(403, 'Anda tidak memiliki akses ke booking ini.');
        }

        $playDate = Carbon::parse($detail->play_date);
        $now = Carbon::now();
        $daysUntilPlay = $now->diffInDays($playDate, false);
        $canReschedule = $daysUntilPlay >= 3;
        $canCancel = $daysUntilPlay >= 3;

        $existingReschedule = BookingReschedule::where('fk_booking_detail_id', $detail->id)->exists();
        $alreadyRescheduled = $existingReschedule;

        $start = Carbon::parse($detail->start_play_time);
        $end = Carbon::parse($detail->end_play_time);
        $duration = $start->diffInHours($end);
        $duration = $duration > 0 ? $duration : 1;

        return view('tenant.booking.history.show', compact(
            'detail', 'playDate', 'daysUntilPlay',
            'canReschedule', 'canCancel', 'alreadyRescheduled',
            'start', 'end', 'duration'
        ));
    }
}
