<?php

namespace App\Http\Controllers\Tenant\Booking;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Payment;
use App\Models\Booking;

class HistoryController extends Controller
{
    public function index(Request $request)
    {
        $userId = Auth::id();

        $availableStatuses = Payment::select('status')->whereNotNull('status')->distinct()->pluck('status');

        $query = Booking::with('payments')->where('fk_user_id', $userId);

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

        return view('tenant.booking.history.index', compact('transactions', 'availableStatuses'));
    }

    public function show(int $id)
    {
        $userId = Auth::id();

        $booking = Booking::with([
            'field',
            'details.payment',
            'payments'
        ])
        ->where('fk_user_id', $userId)
        ->findOrFail($id);

        return view('tenant.booking.history.show', compact('booking'));
    }
}
