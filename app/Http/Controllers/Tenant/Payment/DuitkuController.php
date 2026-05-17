<?php

namespace App\Http\Controllers\Tenant\Payment;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\BookingDetail;
use Illuminate\Support\Facades\Log;
use App\Models\Payment;

class DuitkuController extends Controller
{
    public function callback(Request $request)
    {
        $merchantCode = env('DUITKU_MERCHANT_CODE');
        $apiKey = env('DUITKU_MERCHANT_KEY');

        $amount = $request->input('amount');
        $merchantOrderId = $request->input('merchantOrderId');
        $signature = $request->input('signature');
        $resultCode = $request->input('resultCode');

        $reference = $request->input('reference');
        $paymentCode = $request->input('paymentCode');

        $calcSignature = hash('sha256', $merchantCode . $amount . $merchantOrderId . $apiKey);

        if ($signature !== $calcSignature) {
            Log::error('Duitku Callback: Bad Signature', $request->all());
            return response()->json(['status' => 'Bad Signature'], 400);
        }

        $payment = Payment::where('reference_id', $reference)->first();

        if ($resultCode === '00') {

            if ($payment && $payment->status === 'pending') {
                $payment->status = 'success';
                $payment->method = $paymentCode;
                $payment->paid_at = now();
                $payment->save();

                $bookingId = explode('-', $merchantOrderId)[0];
                BookingDetail::where('fk_booking_id', $bookingId)
                    ->update(['status' => 'active']);

                Log::info('Pembayaran Sukses. Reference: ' . $reference);
            }
        } else {

            if ($payment && $payment->status === 'pending') {
                $payment->status = 'failed';
                $payment->save();

                $bookingId = explode('-', $merchantOrderId)[0];
                BookingDetail::where('fk_booking_id', $bookingId)
                    ->update(['status' => 'cancelled']);

                Log::info('Pembayaran Gagal/Expired. Reference: ' . $reference);
            }
        }

        return response()->json(['status' => 'Success']);
    }
}
