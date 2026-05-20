<?php

namespace App\Http\Controllers\Tenant\Booking;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Payment;
use App\Models\Booking;
use Carbon\Carbon;

class HistoryController extends Controller
{
    public function index(Request $request)
    {
        $userId = Auth::id();

        $availableStatuses = Payment::select('status')->whereNotNull('status')->distinct()->pluck('status');

        // Pastikan details dan attributes di-load untuk menghindari N+1 Query Problem
        $query = Booking::with(['payments', 'details', 'attributes'])->where('fk_user_id', $userId);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('team_name', 'like', "%{$search}%")
                  ->orWhereHas('payments', function($pq) use ($search) {
                      $pq->where('reference_id', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('date')) {
            $query->whereDate('booking_date', $request->date);
        }

        if ($request->filled('status')) {
            $status = $request->status;
            $query->whereHas('payments', function($q) use ($status) {
                $q->where('status', $status);
            });
        }

        $transactions = $query->latest('created_at')->paginate(10)->withQueryString();

        // LOGIKA KEUANGAN & STATUS DIPINDAH KE SINI
        $transactions->getCollection()->transform(function ($trx) {
            $totalDetails = $trx->details->count();
            $cancelledDetails = $trx->details->where('status', 'cancelled')->count();

            $trx->mainPayment = $trx->payments->where('payment_type', '!=', 'refund')->sortByDesc('created_at')->first();

            if ($totalDetails > 0 && $totalDetails === $cancelledDetails) {
                $trx->overallStatus = 'cancelled';
            } else {
                $trx->overallStatus = strtolower($trx->mainPayment->status ?? 'unknown');
            }

            $trx->tagihanAktif = $trx->details->where('status', '!=', 'cancelled')->sum('price') + $trx->attributes->sum('total');
            $uangMasuk = $trx->payments->where('status', 'success')->where('payment_type', '!=', 'refund')->sum('amount');
            $trx->uangRefund = $trx->payments->where('status', 'success')->where('payment_type', 'refund')->sum('amount');

            $uangNet = $uangMasuk - $trx->uangRefund;
            $trx->sisaTagihan = max(0, $trx->tagihanAktif - $uangNet);

            $trx->badgeClass = $this->getBadgeClass($trx->overallStatus);

            return $trx;
        });

        return view('tenant.booking.history.index', compact('transactions', 'availableStatuses'));
    }

    public function show(int $id)
    {
        $userId = Auth::id();

        // Pastikan attributes di-load
        $booking = Booking::with([
            'field',
            'details.payment',
            'payments',
            'attributes'
        ])
        ->where('fk_user_id', $userId)
        ->findOrFail($id);

        // LOGIKA KEUANGAN & STATUS (LEVEL BOOKING UTAMA)
        $totalDetails = $booking->details->count();
        $cancelledDetails = $booking->details->where('status', 'cancelled')->count();
        $booking->mainPayment = $booking->payments->where('payment_type', '!=', 'refund')->sortByDesc('created_at')->first();

        if ($totalDetails > 0 && $totalDetails === $cancelledDetails) {
            $booking->overallStatus = 'cancelled';
        } else {
            $booking->overallStatus = strtolower($booking->mainPayment->status ?? 'unknown');
        }

        $booking->tagihanAktif = $booking->details->where('status', '!=', 'cancelled')->sum('price') + $booking->attributes->sum('total');
        $booking->uangMasuk = $booking->payments->where('status', 'success')->where('payment_type', '!=', 'refund')->sum('amount');
        $booking->uangRefund = $booking->payments->where('status', 'success')->where('payment_type', 'refund')->sum('amount');

        $uangNet = $booking->uangMasuk - $booking->uangRefund;
        $booking->sisaTagihan = max(0, $booking->tagihanAktif - $uangNet);
        $booking->badgeClass = $this->getBadgeClass($booking->overallStatus);

        // LOGIKA KEUANGAN & STATUS (LEVEL DETAIL/JADWAL)
        $booking->details->transform(function ($detail) {
            $detailPayment = $detail->payment->sortByDesc('created_at')->first();
            $detail->detailStatus = strtolower($detail->status ?? $detailPayment->status ?? 'pending');
            $detail->detailBadge = $this->getBadgeClass($detail->detailStatus);

            $playDate = Carbon::parse($detail->play_date)->startOfDay();
            $daysUntilPlay = now()->startOfDay()->diffInDays($playDate, false);

            $detail->canReschedule = $daysUntilPlay >= 3;
            $detail->canCancel = $daysUntilPlay >= 3;
            $detail->alreadyRescheduled = ($detail->detailStatus === 'reschedule');

            return $detail;
        });

        return view('tenant.booking.history.show', compact('booking'));
    }

    // Fungsi Pembantu Warna Badge
    private function getBadgeClass(string $status): string
    {
        $statusColors = [
            'success' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            'pending' => 'bg-amber-50 text-amber-700 border-amber-200',
            'failed'  => 'bg-rose-50 text-rose-700 border-rose-200',
            'expired' => 'bg-rose-50 text-rose-700 border-rose-200',
            'booked'  => 'bg-blue-50 text-blue-700 border-blue-200',
            'active'  => 'bg-blue-50 text-blue-700 border-blue-200',
            'reschedule' => 'bg-amber-50 text-amber-700 border-amber-200',
            'cancelled' => 'bg-red-50 text-red-700 border-red-200',
        ];

        return $statusColors[$status] ?? 'bg-gray-50 text-gray-700 border-gray-200';
    }
}
