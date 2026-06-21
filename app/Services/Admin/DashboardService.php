<?php

namespace App\Services\Admin;

use App\Models\Field;
use App\Models\BookingDetail;
use App\Models\FieldPrice;
use App\Enums\UserRole;
use App\Enums\BookingDetailStatus;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

class DashboardService
{
    private const TIME_FORMAT = 'H:i:s';

    public function resolveField($user, ?int $fieldId): Field
    {
        $fieldQuery = Field::query();

        if ($user && $user->role === UserRole::WORKER->value) {
            $fieldQuery->whereIn('id', function ($q) {
                /** @var \Illuminate\Database\Query\Builder $q */
                $q->select('fk_field_id')
                  ->from('field_admins')
                  ->where('fk_user_id', auth()->id());
            });
        }

        if ($fieldId) {
            $field = $fieldQuery->where('id', $fieldId)->first();
            if (!$field) {
                throw new HttpException(403, 'Anda tidak memiliki hak akses ke lapangan ini atau lapangan tidak ditemukan.');
            }
        } else {
            $field = $fieldQuery->first();
            if (!$field) {
                throw new HttpException(404, 'Anda belum ditugaskan ke lapangan manapun. Hubungi Admin.');
            }
        }

        return $field;
    }

    public function getDashboardMetrics(Field $field): array
    {
        $today = Carbon::now()->format('Y-m-d');
        $currentTime = Carbon::now()->format(self::TIME_FORMAT);
        $dayType = strtolower(Carbon::now()->englishDayOfWeek);

        $fieldPrices = FieldPrice::query()
            ->where('fk_field_id', $field->id)
            ->where('day_type', $dayType)
            ->get();

        $allSlots = $this->generateAllSlots($fieldPrices);
        $totalSlot = count($allSlots);

        $validDetails = BookingDetail::query()
            ->whereHas('booking', function ($query) use ($field) {
                /** @var \Illuminate\Database\Eloquent\Builder $query */
                $query->where('fk_field_id', $field->id);
            })
            ->whereDate('play_date', $today)
            ->whereNotIn('status', [BookingDetailStatus::CANCELLED->value, BookingDetailStatus::FIELD_CLOSURE->value])
            ->get(['start_play_time', 'end_play_time', 'fk_booking_id']);

        $bookedSlots = $this->generateBookedSlots($validDetails);
        $slotTerisi = count($bookedSlots);

        $slotKosong = $totalSlot === 0 ? 0 : $this->calculateFreeSlots($allSlots, $bookedSlots, $currentTime);
        $totalBooking = collect($validDetails)->pluck('fk_booking_id')->unique()->count();

        return [
            'name'         => $field->name ?? 'Joglo66',
            'slotTerisi'   => $slotTerisi,
            'totalSlot'    => $totalSlot,
            'slotKosong'   => $slotKosong,
            'totalBooking' => $totalBooking,
        ];
    }

    private function generateAllSlots($fieldPrices): array
    {
        $allSlots = [];
        foreach ($fieldPrices as $price) {
            /** @var FieldPrice $price */ // Menghentikan warning unknown properties di IDE
            $start = Carbon::parse($price->start_time);
            $end = Carbon::parse($price->end_time);

            while ($start < $end) {
                $allSlots[] = $start->format(self::TIME_FORMAT);
                $start->addHour();
            }
        }
        return $allSlots;
    }

    private function generateBookedSlots($validDetails): array
    {
        $bookedSlots = [];
        foreach ($validDetails as $detail) {
            /** @var BookingDetail $detail */ // Menghentikan warning unknown properties di IDE
            $start = Carbon::parse($detail->start_play_time);
            $end = Carbon::parse($detail->end_play_time);

            while ($start < $end) {
                $slotTime = $start->format(self::TIME_FORMAT);
                if (!in_array($slotTime, $bookedSlots, true)) {
                    $bookedSlots[] = $slotTime;
                }
                $start->addHour();
            }
        }
        return $bookedSlots;
    }

    private function calculateFreeSlots(array $allSlots, array $bookedSlots, string $currentTime): int
    {
        $slotKosong = 0;
        foreach ($allSlots as $slot) {
            $isBooked = in_array($slot, $bookedSlots, true);
            $isPassed = $slot < $currentTime;

            if (!$isBooked && !$isPassed) {
                $slotKosong++;
            }
        }
        return $slotKosong;
    }
}
