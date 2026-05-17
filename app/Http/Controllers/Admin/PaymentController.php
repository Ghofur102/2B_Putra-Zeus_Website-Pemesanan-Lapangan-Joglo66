<?php

namespace App\Http\Controllers\Admin;

use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Controller;

class PaymentController extends Controller
{
    // POST: /api/admin/payment-booking
    public function processPayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'booking_id' => ['required', 'integer', 'exists:bookings,id'],
            'booking_detail_id' => ['nullable', 'integer', 'exists:booking_details,id'],
            'payment_type' => ['required', Rule::in(['down payment', 'final payment', 'reschedule fee', 'refund'])],
            'method' => ['required', Rule::in(['cash', 'transfer'])],
            'amount' => ['required', 'integer', 'min:1'],
            'reference_id' => ['nullable', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $payload = $validator->validated();

        // Ambil data booking beserta field-nya (untuk dicek nanti)
        $booking = Booking::with('details')->find($payload['booking_id']);

        if (! $booking) {
            return response()->json([
                'success' => false,
                'message' => 'Booking not found.',
            ], 400);
        }

        // ==============================================================
        // TAMBAHAN KEAMANAN: Validasi Hak Akses Worker (Data Scoping)
        // ==============================================================
        $user = $request->user();

        if ($user && $user->role === 'worker') {
            $isAuthorized = DB::table('field_admins')
                ->where('fk_user_id', $user->id)
                ->where('fk_field_id', $booking->fk_field_id) // Cek field_id milik booking ini
                ->exists();

            if (!$isAuthorized) {
                return response()->json([
                    'success' => false,
                    'message' => 'Forbidden. Anda tidak memiliki akses untuk memproses pembayaran di lapangan ini.',
                ], 403);
            }
        }
        // ==============================================================

        if ($booking->details->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Booking has no associated details.',
            ], 400);
        }

        if ($booking->details->every(fn ($detail) => $detail->status === 'cancelled')) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot process payment for a fully cancelled booking.',
            ], 400);
        }

        // ==============================================================
        // TAMBAHAN KEAMANAN: PROTEKSI DOUBLE PAYMENT & OVERPAYMENT
        // ==============================================================
        $totalPrice = $booking->details->sum('price');

        // Hitung total uang bersih yang sudah masuk (Pembayaran - Pengembalian)
        $totalPaid = Payment::where('fk_booking_id', $booking->id)
            ->where('status', 'success')
            ->whereIn('payment_type', ['down payment', 'final payment', 'reschedule fee'])
            ->sum('amount');

        $totalRefunded = Payment::where('fk_booking_id', $booking->id)
            ->where('status', 'success')
            ->where('payment_type', 'refund')
            ->sum('amount');

        $netPaid = $totalPaid - $totalRefunded;

        // 3. Jika yang dibayar adalah Tagihan Masuk (DP, Final, atau Reschedule Fee)
        if (in_array($payload['payment_type'], ['down payment', 'final payment', 'reschedule fee'])) {

            // Tolak jika sudah lunas
            if ($netPaid >= $totalPrice) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pesanan ini sudah lunas sepenuhnya. Tidak dapat memproses pembayaran lagi.',
                ], 400);
            }

            // Tolak jika nominal melebihi sisa tagihan
            if (($netPaid + $payload['amount']) > $totalPrice) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nominal pembayaran melebihi sisa tagihan. Sisa tagihan saat ini adalah: Rp ' . number_format($totalPrice - $netPaid, 0, ',', '.'),
                ], 400);
            }

            // Tolak jika DP dua kali
            $hasDownPayment = Payment::where('fk_booking_id', $booking->id)
                ->where('payment_type', 'down payment')
                ->where('status', 'success')
                ->exists();

            if ($payload['payment_type'] === 'down payment' && $hasDownPayment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Down Payment (DP) sudah dibayarkan sebelumnya. Silakan pilih Final Payment (Pelunasan).',
                ], 400);
            }
        }

        // 4. Jika memproses PENGEMBALIAN DANA (Refund)
        elseif ($payload['payment_type'] === 'refund') {
            // Tidak boleh refund uang yang melebihi jumlah yang sudah pernah dibayarkan
            if ($payload['amount'] > $netPaid) {
                 return response()->json([
                    'success' => false,
                    'message' => 'Nominal refund melebihi total uang yang telah dibayarkan (Maksimal Refund: Rp ' . number_format($netPaid, 0, ',', '.') . ').',
                ], 400);
            }
        }

        $referenceId = $payload['reference_id'] ?? Str::upper(Str::random(16));
        $paymentUrl = null;
        $paymentStatus = 'pending';

        if ($payload['method'] === 'cash') {
            $paymentStatus = 'success';
            $paymentUrl = null;
        } else {
            $paymentUrl = sprintf('https://payment-gateway.example.com/pay/%s/%s', $booking->id, $referenceId);
        }

        try {
            $payment = DB::transaction(function () use ($booking, $payload, $referenceId, $paymentUrl, $paymentStatus) {
                $payment = Payment::create([
                    'fk_booking_id' => $booking->id,
                    'fk_booking_detail_id' => $payload['booking_detail_id'] ?? null,
                    'reference_id' => $referenceId,
                    'payment_url' => $paymentUrl,
                    'payment_type' => $payload['payment_type'],
                    'method' => $payload['method'],
                    'amount' => $payload['amount'],
                    'status' => $paymentStatus,
                    'paid_at' => $paymentStatus === 'success' ? now() : null,
                ]);


                if (in_array($payload['payment_type'], ['down payment', 'final payment'])) {
                    $booking->details()->where('status', 'waiting')->update(['status' => 'active']);
                }

                return $payment;
            });
        } catch (\Exception $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to process payment.',
                'error' => $exception->getMessage(),
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Payment processed successfully.',
            'data' => [
                'payment_id' => $payment->id,
                'status' => $payment->status,
                'payment_url' => $paymentUrl,
            ],
        ], 200);
    }
}
