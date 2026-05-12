<?php

namespace App\Http\Controllers\Tenant\Booking;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Field;
use Carbon\Carbon;
use App\Models\BookingDetail;
use App\Models\Booking;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $userId = Auth::id();
        $fields = Field::all();
        $selectedFieldId = $request->query('field_id');

        $selectedField = null;
        $nearestBookings = collect();
        $userBookings = collect();

        if ($selectedFieldId) {
            $selectedField = Field::findOrFail($selectedFieldId);
            $now = Carbon::now();
            $today = $now->copy()->startOfDay();

            $nearestBookings = BookingDetail::with(['booking.user'])
                ->whereHas('booking', fn($q) => $q->where('fk_field_id', $selectedFieldId))
                ->whereDate('play_date', '>=', $today)
                ->where('status', '!=', 'cancelled')
                ->orderBy('play_date')
                ->orderBy('start_play_time')
                ->limit(5)
                ->get()
                ->map(fn($detail) => [
                    'day_month' => Carbon::parse($detail->play_date)->translatedFormat('d M'),
                    'year' => Carbon::parse($detail->play_date)->format('Y'),
                    'time' => Carbon::parse($detail->start_play_time)->format('H:i') . ' - ' . Carbon::parse($detail->end_play_time)->format('H:i'),
                    'team_name' => $detail->booking->team_name,
                    'status_text' => $this->formatTimeDifference($now, Carbon::parse($detail->play_date . ' ' . $detail->start_play_time)),
                ]);

            $userBookings = Booking::with(['details'])
                ->where('fk_user_id', $userId)
                ->where('fk_field_id', $selectedFieldId)
                ->orderBy('booking_date', 'desc')
                ->get()
                ->map(function ($booking) {
                    $firstDetail = $booking->details->first();
                    $bookingDate = Carbon::parse($booking->booking_date);

                    return [
                        'day_month' => $bookingDate->translatedFormat('d M'),
                        'year' => $bookingDate->format('Y'),
                        'time' => $firstDetail ? Carbon::parse($firstDetail->start_play_time)->format('H:i') . ' - ' . Carbon::parse($firstDetail->end_play_time)->format('H:i') : '-',
                        'team_name' => $booking->team_name,
                        'status_text' => '✓ Done',
                    ];
                });
        }

        return view('index', compact('fields', 'selectedField', 'selectedFieldId', 'nearestBookings', 'userBookings'));
    }

    private function formatTimeDifference(Carbon $now, Carbon $target): string
    {
        if ($now->greaterThanOrEqualTo($target)) {
            return "Sedang bermain / Lewat";
        }

        $diffHours = (int) $now->diffInHours($target);

        if ($diffHours < 1) return "Mulai segera (< 1 jam)";
        if ($diffHours < 24) return "Mulai dalam {$diffHours} jam lagi";

        $diffDays = (int) $now->diffInDays($target);

        if ($diffDays < 7) return "Mulai dalam {$diffDays} hari lagi";

        if ($diffDays < 30) {
            $weeks = intdiv($diffDays, 7);
            $days = $diffDays % 7;

            $text = "{$weeks} minggu";
            if ($days > 0) $text .= " {$days} hari";

            return "Mulai dalam {$text} lagi";
        }

        if ($diffDays < 365) {
            $months = intdiv($diffDays, 30);
            return "Mulai dalam {$months} bulan++ lagi";
        }

        $years = intdiv($diffDays, 365);
        return "Mulai dalam {$years} tahun lagi";
    }
}
