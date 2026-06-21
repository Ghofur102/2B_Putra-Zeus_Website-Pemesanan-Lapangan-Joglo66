<?php

namespace App\Services\Admin;

use App\Models\Field;
use App\Models\Booking;
use App\Models\BookingDetail;
use App\Models\BookingCancelled;
use App\Models\BookingReschedule;
use App\Models\Payment;
use App\Enums\BookingDetailStatus;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Enums\CancelRefundStatus;
use App\Enums\RescheduleRefundStatus;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;

class BookingService
{
    private const DATE_TIME_FORMAT = 'Y-m-d H:i:s';

    public function getBookingList(array $fieldIds, array $filters): array
    {
        $today = Carbon::now()->format('Y-m-d');
        $query = Booking::query()->with(['user', 'details', 'payments']);

        if (!empty($fieldIds)) {
            $query->whereIn('fk_field_id', $fieldIds);
        }

        if (!empty($filters['field_id'])) {
            $query->where('fk_field_id', $filters['field_id']);
        }

        if (!empty($filters['search'])) {
            $query->where('team_name', 'LIKE', "%{$filters['search']}%");
        }

        $bookings = $query->get();
        $todayBookings = [];
        $upcomingBookings = [];

        foreach ($bookings as $booking) {
            /** @var Booking $booking */
            $fieldName = Field::query()->where('id', $booking->fk_field_id)->value('name') ?? 'Unknown Field';

            foreach ($booking->details as $detail) {
                /** @var BookingDetail $detail */
                if ($this->shouldSkipDetail($detail->play_date, $filters['start_date'] ?? null, $filters['end_date'] ?? null)) {
                    continue;
                }

                $bookingItem = $this->buildBookingItem($booking, $detail, $fieldName);

                if (!empty($filters['start_date']) || !empty($filters['end_date']) || !empty($filters['search'])) {
                    $todayBookings[] = $bookingItem;
                } elseif ($detail->play_date === $today) {
                    $todayBookings[] = $bookingItem;
                } elseif ($detail->play_date > $today) {
                    $upcomingBookings[] = $bookingItem;
                }
            }
        }

        usort($todayBookings, fn($a, $b) => strcmp($a['sort_datetime'], $b['sort_datetime']));
        usort($upcomingBookings, fn($a, $b) => strcmp($a['sort_datetime'], $b['sort_datetime']));

        $limit = $filters['limit'] ?? 20;
        return [
            'today'    => array_slice($todayBookings, 0, $limit),
            'upcoming' => array_slice($upcomingBookings, 0, $limit)
        ];
    }

    public function createBooking(array $payload): Booking
    {
        $this->validateAndCalculateDetails($payload['field_id'], $payload['details']);

        return DB::transaction(function () use ($payload) {
            $booking = Booking::create([
                'fk_user_id'     => $payload['user_id'],
                'fk_field_id'    => $payload['field_id'],
                'team_name'      => $payload['team_name'],
                'booking_date'   => $payload['booking_date'],
                'customer_phone' => $payload['customer_phone'] ?? null,
                'customer_email' => $payload['customer_email'] ?? null,
                'notes'          => $payload['notes'] ?? null,
            ]);

            foreach ($payload['details'] as $detail) {
                BookingDetail::create([
                    'fk_booking_id'   => $booking->id,
                    'start_play_time' => $detail['start_play_time'],
                    'end_play_time'   => $detail['end_play_time'],
                    'play_date'       => $detail['play_date'],
                    'price'           => $detail['price'],
                    'status'          => BookingDetailStatus::WAITING->value,
                ]);
            }

            return $booking;
        });
    }

    public function getBookingDetailInfo(BookingDetail $detail): array
    {
        $start = Carbon::parse($detail->start_play_time);
        $end = Carbon::parse($detail->end_play_time);
        $duration = max(1, $start->diffInHours($end));

        $allPayments = $detail->booking->payments->where('status', PaymentStatus::SUCCESS->value);
        $totalBookingPaid = $allPayments->whereIn('payment_type', [PaymentType::DOWN_PAYMENT->value, PaymentType::FINAL_PAYMENT->value, PaymentType::RESCHEDULE_FEE->value])->sum(fn($p) => $p->amount);
        $totalBookingRefund = $allPayments->where('payment_type', PaymentType::REFUND->value)->sum(fn($p) => $p->amount);

        $sessions = $detail->booking->details->map(function ($item) use ($allPayments, $detail) {
            /** @var BookingDetail $item */
            $sessionPaid = $this->calculateTotalPaidForDetail($detail->booking, $item);

            return [
                'id'                => $item->id,
                'play_date'         => Carbon::parse($item->play_date)->format('d M Y'),
                'start_time'        => Carbon::parse($item->start_play_time)->format('H:i'),
                'end_time'          => Carbon::parse($item->end_play_time)->format('H:i'),
                'price'             => (int)$item->price,
                'status'            => $item->status,
                'total_paid'        => (int)$sessionPaid,
                'remaining_payment' => (int)($item->price - $sessionPaid),
                'refund_amount'     => (int)$allPayments->where('payment_type', PaymentType::REFUND->value)->where('fk_booking_detail_id', $item->id)->sum(fn($p) => $p->amount)
            ];
        });

        $closures = DB::table('field_closures')
            ->where('fk_field_id', $detail->booking->fk_field_id)
            ->get(['field_closure_start_time', 'field_closure_end_time']);

        return [
            'booking_id' => $detail->booking->id,
            'user_info' => [
                'name'      => $detail->booking->team_name ?? 'Guest',
                'email'     => $detail->booking->customer_email ?? '-',
                'phone'     => $detail->booking->customer_phone ?? '-',
                'team_name' => $detail->booking->team_name ?? '-',
                'notes'     => $detail->booking->notes ?? '-',
            ],
            'field_info' => [
                'id'        => $detail->booking->fk_field_id,
                'name'      => $detail->booking->field->name ?? 'Unknown Field',
                'category'  => $detail->booking->field->category ?? 'Unknown Category',
                'image_url' => $detail->booking->field->image_url,
            ],
            'service_info' => [
                'duration'           => $duration,
                'price_per_hour'     => $detail->price / $duration,
                'total_price'        => (int)$detail->booking->details->sum(fn($d) => $d->price),
                'total_down_payment' => (int)($totalBookingPaid - $totalBookingRefund),
            ],
            'payment_details' => [
                'total_price'    => (int)$detail->booking->details->sum(fn($d) => $d->price),
                'total_paid'     => (int)($totalBookingPaid - $totalBookingRefund),
                'payment_method' => $detail->booking->payments->last()->method ?? '-',
            ],
            'sessions'       => $sessions,
            'field_closures' => $closures
        ];
    }

    public function executeReschedule(BookingDetail $detail, array $data): void
    {
        $this->validateRescheduleTimeline($detail);

        $startFormatted = Carbon::parse($data['new_play_date'] . ' ' . $data['new_start_time'])->format(self::DATE_TIME_FORMAT);
        $endFormatted = Carbon::parse($data['new_play_date'] . ' ' . $data['new_end_time'])->format(self::DATE_TIME_FORMAT);

        $isClosed = DB::table('field_closures')
            ->where('fk_field_id', $detail->booking->fk_field_id)
            ->where('field_closure_start_time', '<', $endFormatted)
            ->where('field_closure_end_time', '>', $startFormatted)
            ->exists();

        if ($isClosed) {
            throw new HttpException(400, 'Reschedule ditolak: Lapangan sedang ditutup operasional (Field Closure) pada slot waktu pilihan Anda.');
        }

        // Otomatisasi Perhitungan finansial Reschedule berdasarkan selisih harga baru vs lama
        $rescheduleRefundStatus = RescheduleRefundStatus::NONE->value;
        if (isset($data['new_price'])) {
            if ((int)$data['new_price'] > (int)$detail->price) {
                $rescheduleRefundStatus = RescheduleRefundStatus::DEPOSIT_REQUIRED->value;
            } elseif ((int)$data['new_price'] < (int)$detail->price) {
                $rescheduleRefundStatus = RescheduleRefundStatus::REFUND_REQUIRED->value;
            }
        }

        DB::transaction(function () use ($detail, $data, $rescheduleRefundStatus) {
            BookingReschedule::create([
                'fk_booking_detail_id' => $detail->id,
                'fk_field_closure_id'  => $data['fk_field_closure_id'] ?? null,
                'old_date'             => $detail->play_date,
                'reason'               => $data['reason'],
                'status_refund'        => $rescheduleRefundStatus, // SEKARANG DIISI SINKRON DENGAN ENUM DB
            ]);

            $isFromClosure = strtolower($detail->status) === BookingDetailStatus::FIELD_CLOSURE->value;
            $updateData = [
                'play_date'       => $data['new_play_date'],
                'start_play_time' => $data['new_start_time'],
                'end_play_time'   => $data['new_end_time'],
                'status'          => $isFromClosure ? BookingDetailStatus::CLOSED_FIELD_RESCHEDULE->value : BookingDetailStatus::RESCHEDULE->value,
            ];

            if (isset($data['new_price'])) {
                $updateData['price'] = $data['new_price'];
            }

            $detail->update($updateData);
        });
    }

    public function executeCancel(BookingDetail $detail, array $data): void
    {
        $currentStatus = strtolower($detail->status);
        if (str_contains($currentStatus, 'cancel')) {
            throw new HttpException(400, 'Booking ini sudah dibatalkan.');
        }

        $statusRefund = $this->determineRefundStatus($detail, $data['status_refund'] ?? 'None');
        $refundAmount = $this->calculateCancelRefund($detail, $statusRefund);

        DB::transaction(function () use ($detail, $data, $statusRefund, $refundAmount) {
            BookingCancelled::create([
                'fk_booking_detail_id' => $detail->id,
                'fk_field_closure_id'  => $data['fk_field_closure_id'] ?? null,
                'cancle_date'          => Carbon::now()->toDateString(),
                'reason'               => $data['reason'],
                'status_refund'        => $statusRefund, // Terkunci aman via enum validasi string
            ]);

            $isFromClosure = strtolower($detail->status) === BookingDetailStatus::FIELD_CLOSURE->value;
            $detail->update([
                'status' => $isFromClosure ? BookingDetailStatus::CLOSED_FIELD_CANCELLED->value : BookingDetailStatus::CANCELLED->value,
            ]);

            if ($refundAmount > 0) {
                Payment::create([
                    'fk_booking_id'        => $detail->fk_booking_id,
                    'fk_booking_detail_id' => $detail->id,
                    'reference_id'         => 'CNL-REF-' . strtoupper(Str::random(10)),
                    'payment_type'         => PaymentType::REFUND->value,
                    'method'               => 'cash',
                    'amount'               => $refundAmount,
                    'status'               => PaymentStatus::SUCCESS->value,
                    'paid_at'              => now(),
                ]);
            }
        });
    }

    public function executeRefundOverpayment(BookingDetail $detail): void
    {
        $totalPaid = $this->calculateTotalPaidForDetail($detail->booking, $detail);
        $overpayment = $totalPaid - $detail->price;

        if ($overpayment <= 0) {
            throw new HttpException(400, 'Tidak ada kelebihan pembayaran pada sesi ini.');
        }

        DB::transaction(function () use ($detail, $overpayment) {
            Payment::create([
                'fk_booking_id'        => $detail->fk_booking_id,
                'fk_booking_detail_id' => $detail->id,
                'reference_id'         => 'RFD-' . strtoupper(Str::random(8)),
                'payment_type'         => PaymentType::REFUND->value,
                'method'               => 'cash',
                'amount'               => $overpayment,
                'status'               => PaymentStatus::SUCCESS->value,
                'paid_at'              => now(),
            ]);
        });
    }

    public function calculateTotalPaidForDetail($booking, $detail): float
    {
        $allPayments = $booking->payments->where('status', PaymentStatus::SUCCESS->value);
        $totalBookingPaid = $allPayments->whereIn('payment_type', [PaymentType::DOWN_PAYMENT->value, PaymentType::FINAL_PAYMENT->value, PaymentType::RESCHEDULE_FEE->value])->sum(fn($p) => $p->amount);
        $totalBookingRefund = $allPayments->where('payment_type', PaymentType::REFUND->value)->sum(fn($p) => $p->amount);
        $totalDetailsCount = $booking->details->count();

        if ($totalDetailsCount == 1) {
            return $totalBookingPaid - $totalBookingRefund;
        }

        $specificPaid = $allPayments->where('fk_booking_detail_id', $detail->id)->whereIn('payment_type', [PaymentType::DOWN_PAYMENT->value, PaymentType::FINAL_PAYMENT->value, PaymentType::RESCHEDULE_FEE->value])->sum(fn($p) => $p->amount);
        $specificRefund = $allPayments->where('fk_booking_detail_id', $detail->id)->where('payment_type', PaymentType::REFUND->value)->sum(fn($p) => $p->amount);
        $genericPaid = $allPayments->where('fk_booking_detail_id', null)->whereIn('payment_type', [PaymentType::DOWN_PAYMENT->value, PaymentType::FINAL_PAYMENT->value])->sum(fn($p) => $p->amount);

        return ($specificPaid - $specificRefund) + ($genericPaid / $totalDetailsCount);
    }

    private function shouldSkipDetail(string $playDate, ?string $startDate, ?string $endDate): bool
    {
        if ($startDate && $endDate) {
            return $playDate < $startDate || $playDate > $endDate;
        }
        if ($startDate) {
            return $playDate !== $startDate;
        }
        return false;
    }

    private function buildBookingItem($booking, $detail, string $fieldName): array
    {
        $totalPaid = $this->calculateTotalPaidForDetail($booking, $detail);
        $remainingPayment = $detail->price - $totalPaid;

        $refundAmount = $booking->payments->where('status', PaymentStatus::SUCCESS->value)
            ->where('payment_type', PaymentType::REFUND->value)
            ->where('fk_booking_detail_id', $detail->id)
            ->sum(fn($p) => $p->amount);

        return [
            'id'                => $detail->id,
            'sort_datetime'     => $detail->play_date . ' ' . $detail->start_play_time,
            'date'              => Carbon::parse($detail->play_date)->format('d'),
            'month'             => Carbon::parse($detail->play_date)->format('M'),
            'year'              => Carbon::parse($detail->play_date)->format('Y'),
            'title'             => "{$booking->team_name}",
            'tenant_name'       => "{$booking->user->name}",
            'time'              => Carbon::parse($detail->start_play_time)->format('H:i') . ' - ' . Carbon::parse($detail->end_play_time)->format('H:i'),
            'description'       => $fieldName,
            'price'             => $detail->price,
            'status'            => $detail->status,
            'total_paid'        => $totalPaid,
            'remaining_payment' => $remainingPayment,
            'refund_amount'     => $refundAmount
        ];
    }

    private function validateAndCalculateDetails(int $fieldId, array $details): int
    {
        $totalPrice = 0;
        $todayDate = Carbon::now()->format('Y-m-d');
        $currentTime = Carbon::now()->format('H:i');

        foreach ($details as $index => $detail) {
            if ($detail['start_play_time'] >= $detail['end_play_time']) {
                throw new HttpException(400, "Detail #{$index} has invalid time range.");
            }
            if ($detail['play_date'] < $todayDate) {
                throw new HttpException(400, 'Tidak dapat memesan untuk tanggal yang sudah lewat.');
            }
            if ($detail['play_date'] === $todayDate && $detail['start_play_time'] <= $currentTime) {
                throw new HttpException(400, 'Waktu main sudah terlewat untuk hari ini.');
            }
            if ($this->hasFieldConflict($fieldId, $detail)) {
                throw new HttpException(400, 'Lapangan sudah dipesan atau sedang ditutup pada slot waktu tersebut.');
            }
            if (!$this->validateFieldPrice($fieldId, $detail)) {
                throw new HttpException(400, 'Validasi harga gagal untuk detail booking.');
            }

            $totalPrice += $detail['price'];
        }

        return $totalPrice;
    }

    private function hasFieldConflict(int $fieldId, array $detail): bool
    {
        $slotStart = Carbon::parse($detail['play_date'] . ' ' . $detail['start_play_time'])->format(self::DATE_TIME_FORMAT);
        $slotEnd = Carbon::parse($detail['play_date'] . ' ' . $detail['end_play_time'])->format(self::DATE_TIME_FORMAT);

        $isClosed = DB::table('field_closures')
            ->where('fk_field_id', $fieldId)
            ->where('field_closure_start_time', '<', $slotEnd)
            ->where('field_closure_end_time', '>', $slotStart)
            ->exists();

        if ($isClosed) {
            return true;
        }

        return BookingDetail::query()
            ->whereHas('booking', function ($query) use ($fieldId) {
                /** @var \Illuminate\Database\Eloquent\Builder $query */
                $query->where('fk_field_id', $fieldId);
            })
            ->where('play_date', $detail['play_date'])
            ->whereNotIn('status', [BookingDetailStatus::CANCELLED->value, BookingDetailStatus::FIELD_CLOSURE->value, BookingDetailStatus::CLOSED_FIELD_CANCELLED->value])
            ->where('start_play_time', '<', $detail['end_play_time'])
            ->where('end_play_time', '>', $detail['start_play_time'])
            ->exists();
    }

    private function validateFieldPrice(int $fieldId, array $detail): bool
    {
        $dayName = strtolower(date('l', strtotime($detail['play_date'])));

        $price = DB::table('field_prices')
            ->where('fk_field_id', $fieldId)
            ->where('day_type', $dayName)
            ->where('start_time', '<=', $detail['start_play_time'])
            ->where('end_time', '>=', $detail['end_play_time'])
            ->value('price');

        if (!is_null($price) && (int)$price !== (int)$detail['price']) {
            return false;
        }

        return true;
    }

    private function validateRescheduleTimeline($detail): void
    {
        if (strtolower($detail->status) !== BookingDetailStatus::FIELD_CLOSURE->value) {
            $playDate = Carbon::parse($detail->play_date)->startOfDay();
            $today = Carbon::now()->startOfDay();
            $diffDays = $today->diffInDays($playDate, false);

            if ($diffDays < 3) {
                throw new HttpException(400, 'Reschedule ditolak: Jadwal main kurang dari H-3.');
            }
        }
    }

    private function determineRefundStatus($detail, string $inputStatus): string
    {
        $statusRefund = ucfirst(strtolower($inputStatus));

        // Memastikan string input dicocokkan dengan nilai Enum yang valid
        if (!in_array($statusRefund, [CancelRefundStatus::NONE->value, CancelRefundStatus::FULL->value, CancelRefundStatus::PARTIAL->value])) {
            $statusRefund = CancelRefundStatus::NONE->value;
        }

        if (strtolower($detail->status) !== BookingDetailStatus::FIELD_CLOSURE->value) {
            $playDate = Carbon::parse($detail->play_date)->startOfDay();
            $diffDays = Carbon::now()->startOfDay()->diffInDays($playDate, false);
            if ($diffDays < 3) {
                return CancelRefundStatus::NONE->value;
            }
        }
        return $statusRefund;
    }

    private function calculateCancelRefund($detail, string $statusRefund): float
    {
        if ($statusRefund === CancelRefundStatus::NONE->value) {
            return 0;
        }

        $successfulPayments = Payment::where('fk_booking_id', $detail->fk_booking_id)
            ->where('status', PaymentStatus::SUCCESS->value)
            ->whereIn('payment_type', [PaymentType::DOWN_PAYMENT->value, PaymentType::FINAL_PAYMENT->value])
            ->sum(fn($p) => $p->amount);

        $refunded = Payment::where('fk_booking_detail_id', $detail->id)
            ->where('payment_type', PaymentType::REFUND->value)->sum(fn($p) => $p->amount);

        $netPaid = $successfulPayments - $refunded;
        return ($statusRefund === CancelRefundStatus::FULL->value) ? $netPaid : ($netPaid / 2);
    }
}
