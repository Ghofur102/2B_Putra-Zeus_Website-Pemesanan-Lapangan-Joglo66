<?php

namespace App\Http\Controllers\Tenant\Booking;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Field;
use Carbon\Carbon;
use App\Models\BookingDetail;
use App\Models\Booking;
use Illuminate\View\View;
use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    // Solusi php:S1192 - Ekstraksi Seluruh String Duplikat ke Konstanta Kelas
    private const FORMAT_DAY_MONTH = 'd M';
    private const FORMAT_YEAR = 'Y';
    private const FORMAT_TIME = 'H:i';

    /**
     * Display tenant dashboard index page
     */
    public function index(Request $request): View
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

            $nearestBookings = $this->getNearestBookings((int)$selectedFieldId, $now);
            $userBookings = $this->getUserBookings((int)$userId, (int)$selectedFieldId);
        }

        return view('index', compact(
            'fields',
            'selectedField',
            'selectedFieldId',
            'nearestBookings',
            'userBookings'
        ));
    }

    /**
     * Private Helper: Mengambil data 5 jadwal terdekat lapangan tertentu (php:S3776)
     */
    private function getNearestBookings(int $fieldId, Carbon $now): \Illuminate\Support\Collection
    {
        $today = $now->copy()->startOfDay();

        return BookingDetail::with(['booking.user'])
            ->whereHas('booking', function ($q) use ($fieldId) {
                $q->where('fk_field_id', $fieldId);
            })
            ->whereDate('play_date', '>=', $today)
            ->where('status', '!=', 'cancelled')
            ->orderBy('play_date')
            ->orderBy('start_play_time')
            ->limit(5)
            ->get()
            ->map(function ($detail) use ($now) {
                $targetTime = Carbon::parse($detail->play_date . ' ' . $detail->start_play_time);
                return [
                    'day_month'   => Carbon::parse($detail->play_date)->translatedFormat(self::FORMAT_DAY_MONTH),
                    'year'        => Carbon::parse($detail->play_date)->format(self::FORMAT_YEAR),
                    'time'        => Carbon::parse($detail->start_play_time)->format(self::FORMAT_TIME) . ' - ' . Carbon::parse($detail->end_play_time)->format(self::FORMAT_TIME),
                    'team_name'   => $detail->booking->team_name,
                    'status_text' => $this->formatTimeDifference($now, $targetTime),
                ];
            });
    }

    /**
     * Private Helper: Mengambil data riwayat booking milik user aktif pada lapangan terkait (php:S3776)
     */
    private function getUserBookings(int $userId, int $fieldId): \Illuminate\Support\Collection
    {
        return Booking::with(['details'])
            ->where('fk_user_id', $userId)
            ->where('fk_field_id', $fieldId)
            ->orderBy('booking_date', 'desc')
            ->get()
            ->map(function ($booking) {
                $firstDetail = $booking->details->first();
                $bookingDate = Carbon::parse($booking->booking_date);
                $timeRange = '-';

                if ($firstDetail) {
                    $timeRange = Carbon::parse($firstDetail->start_play_time)->format(self::FORMAT_TIME) . ' - ' . Carbon::parse($firstDetail->end_play_time)->format(self::FORMAT_TIME);
                }

                return [
                    'day_month'   => $bookingDate->translatedFormat(self::FORMAT_DAY_MONTH),
                    'year'        => $bookingDate->format(self::FORMAT_YEAR),
                    'time'        => $timeRange,
                    'team_name'   => $booking->team_name,
                    'status_text' => '✓ Done',
                ];
            });
    }

    /**
     * Format text deskripsi selisih waktu berjalan dengan waktu main
     * Solusi php:S1142 & php:S121 - Menggunakan Single Exit Point & Menambahkan Kurung Kurawal
     */
    private function formatTimeDifference(Carbon $now, Carbon $target): string
    {
        $resultText = '';

        if ($now->greaterThanOrEqualTo($target)) {
            $resultText = "Sedang bermain / Lewat";
        } else {
            $diffHours = (int) $now->diffInHours($target);

            if ($diffHours < 1) {
                $resultText = "Mulai segera (< 1 jam)";
            } elseif ($diffHours < 24) {
                $resultText = "Mulai dalam {$diffHours} jam lagi";
            } else {
                $diffDays = (int) $now->diffInDays($target);
                $resultText = $this->resolveDaysDifference($diffDays);
            }
        }

        return $resultText;
    }

    /**
     * Private Helper: Mengkalkulasi selisih berbasis konversi hari ke minggu/bulan/tahun (php:S3776)
     */
    private function resolveDaysDifference(int $diffDays): string
    {
        $resultText = '';

        if ($diffDays < 7) {
            $resultText = "Mulai dalam {$diffDays} hari lagi";
        } elseif ($diffDays < 30) {
            $weeks = intdiv($diffDays, 7);
            $days = $diffDays % 7;

            $text = "{$weeks} minggu";
            if ($days > 0) {
                $text .= " {$days} hari";
            }

            $resultText = "Mulai dalam {$text} lagi";
        } elseif ($diffDays < 365) {
            $months = intdiv($diffDays, 30);
            $resultText = "Mulai dalam {$months} bulan++ lagi";
        } else {
            $years = intdiv($diffDays, 365);
            $resultText = "Mulai dalam {$years} tahun lagi";
        }

        return $resultText;
    }
}
