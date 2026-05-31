<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Models\Field;
use App\Models\BookingDetail;
use App\Models\FieldPrice;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;
use App\Http\Controllers\Controller;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class DashboardController extends Controller
{
    private const STR_WORKER = 'worker';
    private const TIME_FORMAT = 'H:i:s';
    private const STATUS_CANCELLED = 'cancelled';
    private const STATUS_FIELD_CLOSURE = 'field closure';

    public function dashboard(Request $request): JsonResponse
    {
        $status = 200;
        try {
            $fieldId = $request->field_id;
            $today = Carbon::now()->format('Y-m-d');
            $currentTime = Carbon::now()->format(self::TIME_FORMAT);
            $dayType = strtolower(Carbon::now()->englishDayOfWeek);
            $user = $request->user();

            $field = $this->resolveField($user, $fieldId);

            $fieldPrices = FieldPrice::where('fk_field_id', $field->id)
                ->where('day_type', $dayType)
                ->get();

            $allSlots = $this->generateAllSlots($fieldPrices);
            $totalSlot = count($allSlots);

            $validDetails = BookingDetail::whereHas('booking', function ($query) use ($field) {
                    $query->where('fk_field_id', $field->id);
                })
                ->whereDate('play_date', $today)
                ->whereNotIn('status', [self::STATUS_CANCELLED, self::STATUS_FIELD_CLOSURE])
                ->get(['start_play_time', 'end_play_time', 'fk_booking_id']);

            $bookedSlots = $this->generateBookedSlots($validDetails);
            $slotTerisi = count($bookedSlots);

            $slotKosong = $this->calculateFreeSlots($allSlots, $bookedSlots, $currentTime);
            $totalBooking = collect($validDetails)->pluck('fk_booking_id')->unique()->count();

            if ($totalSlot === 0) {
                $slotKosong = 0;
            }

            $data = [
                'success' => true,
                'message' => 'Dashboard data retrieved successfully',
                'data' => [
                    'name' => $field->name ?? 'Joglo66',
                    'slotTerisi' => $slotTerisi,
                    'totalSlot' => $totalSlot,
                    'slotKosong' => $slotKosong,
                    'totalBooking' => $totalBooking,
                ]
            ];
        } catch (HttpException $e) {
            $status = $e->getStatusCode();
            $data = [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => $status === 404 ? [
                    'name' => 'Belum Ada Lapangan',
                    'slotTerisi' => 0,
                    'totalSlot' => 0,
                    'slotKosong' => 0,
                    'totalBooking' => 0,
                ] : null
            ];
        } catch (Throwable $e) {
            $status = 500;
            $data = [
                'success' => false,
                'message' => 'Internal server error: ' . $e->getMessage(),
                'data' => null
            ];
        }

        return response()->json($data, $status);
    }

    private function resolveField($user, ?int $fieldId): Field
    {
        $fieldQuery = Field::query();

        if ($user && $user->role === self::STR_WORKER) {
            $fieldQuery->whereIn('id', function($q) use ($user) {
                $q->select('fk_field_id')
                  ->from('field_admins')
                  ->where('fk_user_id', $user->id);
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

    private function generateAllSlots($fieldPrices): array
    {
        $allSlots = [];
        foreach ($fieldPrices as $price) {
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
