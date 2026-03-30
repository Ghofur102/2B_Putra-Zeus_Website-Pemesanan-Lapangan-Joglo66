<?php

namespace App\Http\Controllers;

use App\Enums\PaymentTypeEnum;
use App\Enums\StatusDetailBookingEnum;
use App\Models\Booking;
use App\Models\BookingDetail;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class PaymentsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index() {}

    /**
     * Show the form for creating a new resource.
     */
    public function create() {}

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request) {}

    public function storeCashPayment(Request $request)
    {
        $request->validate([
            'fk_booking_id' => ['required', 'exists:bookings,id'],
            'fk_booking_detail_id' => ['nullable', 'exists:detail_bookings,id'],
            'amount' => ['required', 'numeric', 'min:0'],
            'payment_type' => ['required', Rule::enum(PaymentTypeEnum::class)],
        ]);

        $booking = Booking::findOrFail($request->fk_booking_id);

        Gate::authorize('create', [Payment::class, $booking->details->pluck('fk_field_id')->toArray()]);

        // Jika payment detail spesifik, pastikan termasuk dalam booking
        if ($request->fk_booking_detail_id) {
            $detail = BookingDetail::where('id', $request->fk_booking_detail_id)
                ->where('fk_booking_id', $booking->id)
                ->first();

            if (! $detail) {
                return response()->json([
                    'status' => 'error',
                    'message_error' => 'Detail booking tidak valid untuk booking ini.',
                ], 400);
            }
        }

        DB::beginTransaction();
        try {
            $payment = Payment::create([
                'fk_booking_id' => $booking->id,
                'fk_booking_detail_id' => $request->fk_booking_detail_id,
                'reference_id' => null,
                'payment_url' => null,
                'payment_type' => $request->payment_type,
                'method' => 'cash',
                'amount' => $request->amount,
                'status' => StatusDetailBookingEnum::Success,
                'paid_at' => now(),
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'data_payment' => $payment,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message_error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id) {}

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id) {}

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id) {}

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id) {}
}
