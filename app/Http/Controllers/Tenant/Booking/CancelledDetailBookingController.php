<?php

namespace App\Http\Controllers\Tenant\Booking;

use App\Http\Controllers\Controller;
use App\Models\BookingDetail;
use App\Models\BookingCancelled;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class CancelledDetailBookingController extends Controller
{
    const PAYMENT_DP = 'down payment';
    const PAYMENT_FINAL = 'final payment';
    const PAYMENT_RESCHEDULE_FEE = 'reschedule fee';
    const PAYMENT_REFUND = 'refund';
    const STATUS_REFUND_REFUNDABLE = 'refundable';
    const STATUS_REFUND_NON_REFUNDABLE = 'non-refundable';

    public function formInput($detail_booking_id)
    {
        $detail = BookingDetail::with('booking.field')->findOrFail($detail_booking_id);
        $this->authorizeAccess($detail);

        // PERBAIKAN: Gunakan startOfDay()
        $playDate = Carbon::parse($detail->play_date)->startOfDay();
        $daysUntilPlay = Carbon::now()->startOfDay()->diffInDays($playDate, false);
        $netPaid = $this->getPaymentTotals($detail);

        $isRefundable = $daysUntilPlay >= 3;
        $refundAmount = $isRefundable ? $netPaid : 0;

        return view('tenant.booking.cancel.index', compact(
            'detail', 'playDate', 'daysUntilPlay', 'netPaid', 'isRefundable', 'refundAmount'
        ));
    }

    public function confirmation(Request $request)
    {
        $validated = $request->validate([
            'detail_booking_id' => 'required|exists:mysql_joglo66_app.booking_details,id',
            'reason' => 'required|string|max:500',
        ]);

        $detail = BookingDetail::with('booking')->findOrFail($validated['detail_booking_id']);
        $this->authorizeAccess($detail);

        // PERBAIKAN: Gunakan startOfDay()
        $playDate = Carbon::parse($detail->play_date)->startOfDay();
        $daysUntilPlay = Carbon::now()->startOfDay()->diffInDays($playDate, false);
        $netPaid = $this->getPaymentTotals($detail);

        $isRefundable = $daysUntilPlay >= 3;
        $refundAmount = $isRefundable ? $netPaid : 0;

        return view('tenant.booking.cancel.review', compact(
            'detail', 'validated', 'isRefundable', 'refundAmount', 'netPaid', 'daysUntilPlay'
        ));
    }

    public function process(Request $request)
    {
        $validated = $request->validate([
            'detail_booking_id' => 'required|exists:mysql_joglo66_app.booking_details,id',
            'reason' => 'required|string|max:500',
        ]);

        try {
            $detail = BookingDetail::with('booking')->findOrFail($validated['detail_booking_id']);
            $this->authorizeAccess($detail);

            if ($detail->status === 'cancelled') {
                throw new \DomainException('Booking ini sudah dibatalkan sebelumnya.');
            }

            // PERBAIKAN: Gunakan startOfDay() untuk menghitung status Refund
            $playDate = Carbon::parse($detail->play_date)->startOfDay();
            $daysUntilPlay = Carbon::now()->startOfDay()->diffInDays($playDate, false);

            $isRefundable = $daysUntilPlay >= 3;
            $refundAmount = $isRefundable ? $this->getPaymentTotals($detail) : 0;

            DB::connection('mysql_joglo66_app')->transaction(function () use ($detail, $validated, $isRefundable, $refundAmount) {
                BookingCancelled::create([
                    'fk_booking_detail_id' => $detail->id,
                    'cancle_date' => now()->toDateString(),
                    'reason' => $validated['reason'],
                    'status_refund' => $isRefundable ? self::STATUS_REFUND_REFUNDABLE : self::STATUS_REFUND_NON_REFUNDABLE,
                ]);

                $detail->update(['status' => 'cancelled']);

                if ($isRefundable && $refundAmount > 0) {
                    Payment::create([
                        'fk_booking_id' => $detail->fk_booking_id,
                        'fk_booking_detail_id' => $detail->id,
                        'reference_id' => 'CNL-REF-'.strtoupper(Str::random(10)),
                        'payment_type' => self::PAYMENT_REFUND,
                        'method' => 'cash',
                        'amount' => $refundAmount,
                        'status' => 'success',
                        'paid_at' => now(),
                    ]);
                }
            });

            return redirect()->route('tenant.booking.history.show', $detail->fk_booking_id)
                ->with('success', 'Booking berhasil dibatalkan.');

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal membatalkan booking: '.$e->getMessage());
        }
    }

    private function authorizeAccess($detail)
    {
        if ($detail->booking->fk_user_id !== Auth::id()) {
            abort(403, 'Anda tidak memiliki akses ke booking ini.');
        }
    }

    private function getPaymentTotals(BookingDetail $detail): int
    {
        $paid = Payment::where('fk_booking_id', $detail->fk_booking_id)
            ->where('status', 'success')
            ->whereIn('payment_type', [self::PAYMENT_DP, self::PAYMENT_FINAL, self::PAYMENT_RESCHEDULE_FEE])
            ->sum('amount');

        $refunded = Payment::where('fk_booking_id', $detail->fk_booking_id)
            ->where('status', 'success')
            ->where('payment_type', self::PAYMENT_REFUND)
            ->sum('amount');

        return $paid - $refunded;
    }
}
