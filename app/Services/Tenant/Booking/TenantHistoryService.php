<?php

namespace App\Services\Tenant\Booking;

use App\Models\Booking;
use App\Models\BookingDetail;
use App\Models\Payment;
use App\Enums\BookingDetailStatus;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;

class TenantHistoryService
{
    private const COL_STATUS = 'status';
    private const COL_CREATED_AT = 'created_at';
    private const COL_PAYMENT_TYPE = 'payment_type';

    public function getAvailablePaymentStatuses(): array
    {
        return Payment::query()
            ->select(self::COL_STATUS)
            ->whereNotNull(self::COL_STATUS)
            ->distinct()
            ->pluck(self::COL_STATUS)
            ->toArray();
    }

    public function getPaginatedHistory(int $userId, array $filters): LengthAwarePaginator
    {
        $query = Booking::query()
            ->with(['payments', 'details', 'attributes'])
            ->where('fk_user_id', $userId);

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                /** @var \Illuminate\Database\Eloquent\Builder $q */
                $q->where('team_name', 'like', "%{$search}%")
                  ->orWhereHas('payments', function ($pq) use ($search) {
                      /** @var \Illuminate\Database\Eloquent\Builder $pq */
                      $pq->where('reference_id', 'like', "%{$search}%");
                  });
            });
        }

        if (!empty($filters['date'])) {
            $query->whereDate('booking_date', $filters['date']);
        }

        if (!empty($filters['status'])) {
            $status = $filters['status'];
            $query->whereHas('payments', function ($q) use ($status) {
                /** @var \Illuminate\Database\Eloquent\Builder $q */
                $q->where(self::COL_STATUS, $status);
            });
        }

        $paginator = $query->latest(self::COL_CREATED_AT)->paginate(10)->withQueryString();

        $paginator->getCollection()->transform(function ($trx) {
            /** @var Booking $trx */
            return $this->appendFinancialSummary($trx);
        });

        return $paginator;
    }

    public function getBookingDetail(int $userId, int $bookingId): Booking
    {
        $booking = Booking::query()
            ->with(['field', 'details.payment', 'payments', 'attributes'])
            ->where('fk_user_id', $userId)
            ->findOrFail($bookingId);

        $this->appendFinancialSummary($booking);

        $booking->details->transform(function ($detail) {
            /** @var BookingDetail $detail */
            $detailPayment = $detail->payment->sortByDesc(self::COL_CREATED_AT)->first();
            $detail->detailStatus = strtolower($detail->status ?? $detailPayment->status ?? PaymentStatus::PENDING->value);
            $detail->detailBadge = $this->getBadgeClass($detail->detailStatus);

            $playDate = Carbon::parse($detail->play_date)->startOfDay();
            $daysUntilPlay = now()->startOfDay()->diffInDays($playDate, false);

            $detail->canReschedule = $daysUntilPlay >= 3;
            $detail->canCancel = $daysUntilPlay >= 3;
            $detail->alreadyRescheduled = ($detail->detailStatus === BookingDetailStatus::RESCHEDULE->value);

            return $detail;
        });

        return $booking;
    }

    private function appendFinancialSummary(Booking $booking): Booking
    {
        $totalDetails = $booking->details->count();
        $cancelledDetails = $booking->details->where(self::COL_STATUS, BookingDetailStatus::CANCELLED->value)->count();

        $booking->mainPayment = $booking->payments
            ->where(self::COL_PAYMENT_TYPE, '!=', PaymentType::REFUND->value)
            ->sortByDesc(self::COL_CREATED_AT)
            ->first();

        if ($totalDetails > 0 && $totalDetails === $cancelledDetails) {
            $booking->overallStatus = BookingDetailStatus::CANCELLED->value;
        } else {
            $booking->overallStatus = strtolower($booking->mainPayment->status ?? 'unknown');
        }

        $booking->tagihanAktif = $booking->details->where(self::COL_STATUS, '!=', BookingDetailStatus::CANCELLED->value)->sum('price') + $booking->attributes->sum('total');
        $booking->uangMasuk = $booking->payments->where(self::COL_STATUS, PaymentStatus::SUCCESS->value)->where(self::COL_PAYMENT_TYPE, '!=', PaymentType::REFUND->value)->sum('amount');
        $booking->uangRefund = $booking->payments->where(self::COL_STATUS, PaymentStatus::SUCCESS->value)->where(self::COL_PAYMENT_TYPE, PaymentType::REFUND->value)->sum('amount');

        $uangNet = $booking->getAttributes()['uangMasuk'] ?? $booking->uangMasuk - $booking->uangRefund;
        $booking->sisaTagihan = max(0, $booking->tagihanAktif - $uangNet);
        $booking->badgeClass = $this->getBadgeClass($booking->overallStatus);

        return $booking;
    }

    private function getBadgeClass(string $status): string
    {
        $statusColors = [
            'success'                 => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            'pending'                 => 'bg-amber-50 text-amber-700 border-amber-200',
            'failed'                  => 'bg-rose-50 text-rose-700 border-rose-200',
            'expired'                 => 'bg-rose-50 text-rose-700 border-rose-200',
            'booked'                  => 'bg-blue-50 text-blue-700 border-blue-200',
            'active'                  => 'bg-blue-50 text-blue-700 border-blue-200',
            'reschedule'              => 'bg-amber-50 text-amber-800 border-amber-200',
            'cancelled'               => 'bg-red-50 text-red-700 border-red-200',
            'field closure'           => 'bg-red-50 text-red-800 border-red-200',
            'closed field cancelled'  => 'bg-red-50 text-red-900 border-red-200',
            'closed field reschedule' => 'bg-amber-50 text-amber-900 border-amber-200',
        ];

        return $statusColors[$status] ?? 'bg-gray-50 text-gray-700 border-gray-200';
    }
}
