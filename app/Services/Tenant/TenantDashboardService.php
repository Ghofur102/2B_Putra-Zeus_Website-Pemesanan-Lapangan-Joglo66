<?php

namespace App\Services\Tenant;

use App\Models\Booking;
use App\Models\BookingDetail;
use App\Models\Field;
use App\Enums\BookingDetailStatus;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class TenantDashboardService
{
    private const FORMAT_DAY_MONTH = 'd M';
    private const FORMAT_YEAR = 'Y';
    private const FORMAT_TIME = 'H:i';

    public function getCachedFields(): Collection
    {
        return Cache::remember('tenant_fields_list', 86400, function () {
            return Field::all();
        });
    }

    public function getNearestBookings(int $fieldId, Carbon $now): Collection
    {
        $today = $now->copy()->startOfDay();

        return BookingDetail::query()
            ->with(['booking.user'])
            ->whereHas('booking', function ($q) use ($fieldId) {
                /** @var \Illuminate\Database\Eloquent\Builder $q */
                $q->where('fk_field_id', $fieldId);
            })
            ->whereDate('play_date', '>=', $today)
            ->where('status', '!=', BookingDetailStatus::CANCELLED->value)
            ->orderBy('play_date')
            ->orderBy('start_play_time')
            ->limit(5)
            ->get()
            ->map(function ($detail) use ($now) {
                /** @var BookingDetail $detail */
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

    public function getUserBookings(int $userId, int $fieldId): Collection
    {
        return Booking::query()
            ->with(['details'])
            ->where('fk_user_id', $userId)
            ->where('fk_field_id', $fieldId)
            ->orderBy('booking_date', 'desc')
            ->get()
            ->map(function ($booking) {
                /** @var Booking $booking */
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

    private function formatTimeDifference(Carbon $now, Carbon $target): string
    {
        $timeDifferenceText = '';
        $diffHours = (int) $now->diffInHours($target);

        if ($now->greaterThanOrEqualTo($target)) {
            $timeDifferenceText = "Sedang bermain / Lewat";
        } elseif ($diffHours < 1) {
            $timeDifferenceText = "Mulai segera (< 1 jam)";
        } elseif ($diffHours < 24) {
            $timeDifferenceText = "Mulai dalam {$diffHours} jam lagi";
        } else {
            $diffDays = (int) $now->diffInDays($target);
            $timeDifferenceText = $this->resolveDaysDifference($diffDays);
        }

        return $timeDifferenceText;
    }

    private function resolveDaysDifference(int $diffDays): string
    {
        $daysDifferenceText = '';

        if ($diffDays < 7) {
            $daysDifferenceText = "Mulai dalam {$diffDays} hari lagi";
        } elseif ($diffDays < 30) {
            $weeks = intdiv($diffDays, 7);
            $days = $diffDays % 7;
            $formattedText = "{$weeks} minggu";

            if ($days > 0) {
                $formattedText .= " {$days} hari";
            }

            $daysDifferenceText = "Mulai dalam {$formattedText} lagi";
        } elseif ($diffDays < 365) {
            $months = intdiv($diffDays, 30);
            $daysDifferenceText = "Mulai dalam {$months} bulan++ lagi";
        } else {
            $years = intdiv($diffDays, 365);
            $daysDifferenceText = "Mulai dalam {$years} tahun lagi";
        }

        return $daysDifferenceText;
    }
}
