<?php

namespace App\Http\Controllers\Tenant\Payment;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\BookingDetail;
use App\Models\Payment;
use Illuminate\Support\Facades\Log;

class DuitkuController extends Controller
{
    public function callback(Request $request)
    {
        // 1. RADAR PERTAMA: Catat semua yang dikirim Duitku ke Log
        Log::info('--- DUITKU CALLBACK Masuk! ---');
        Log::info('Payload dari Duitku: ', $request->all() ?? []);

        try {
            $merchantCode = env('DUITKU_MERCHANT_CODE');
            $apiKey = env('DUITKU_MERCHANT_KEY');

            $amount = $request->input('amount');
            $merchantOrderId = $request->input('merchantOrderId');
            $signature = $request->input('signature');
            $resultCode = $request->input('resultCode');
            $reference = $request->input('reference');
            $paymentCode = $request->input('paymentCode', 'unknown');

            if (!$merchantOrderId || !$reference || !$signature) {
                 Log::error('Callback gagal: Parameter dari Duitku tidak lengkap.');
                 return response()->json(['status' => 'Bad Request'], 400);
            }

            // 2. CEK SIGNATURE (Mendukung MD5 maupun SHA256 sekaligus)
            $calcSignatureMD5 = md5($merchantCode . $amount . $merchantOrderId . $apiKey);
            $calcSignatureSHA = hash('sha256', $merchantCode . $amount . $merchantOrderId . $apiKey);

            if ($signature !== $calcSignatureMD5 && $signature !== $calcSignatureSHA) {
                Log::error("Bad Signature!");
                Log::error("Signature dari Duitku: $signature");
                Log::error("Hasil MD5 Kita: $calcSignatureMD5");
                Log::error("Hasil SHA Kita: $calcSignatureSHA");
                return response()->json(['status' => 'Bad Signature'], 400);
            }

            Log::info("Signature Cocok! Memeriksa database untuk Reference: $reference");

            // 3. CARI DATA DI DATABASE
            $payment = Payment::where('reference_id', $reference)->first();

            if (!$payment) {
                Log::error("GAGAL: Payment dengan reference $reference tidak ditemukan di database!");
                return response()->json(['status' => 'Not Found'], 404);
            }

            Log::info("Data Payment Ditemukan. Status saat ini: {$payment->status}");

            // 4. PROSES UPDATE DATABASE
            if ($resultCode === '00') {
                if ($payment->status === 'pending') {
                    $payment->update([
                        'status' => 'success',
                        'method' => $paymentCode,
                        'paid_at' => now()
                    ]);

                    $bookingId = explode('-', $merchantOrderId)[0];
                    BookingDetail::where('fk_booking_id', $bookingId)->update(['status' => 'active']);

                    Log::info("SUKSES! Database berhasil diupdate ke active & success.");
                } else {
                    Log::info("Abaikan: Payment ini sudah sukses sebelumnya.");
                }
            } else {
                if ($payment->status === 'pending') {
                    $payment->update(['status' => 'failed']);

                    $bookingId = explode('-', $merchantOrderId)[0];
                    BookingDetail::where('fk_booking_id', $bookingId)->update(['status' => 'cancelled']);

                    Log::info("DIBATALKAN! Database diupdate ke failed & cancelled karena transaksi gagal.");
                }
            }

            return response()->json(['status' => 'Success']);
        } catch (\Exception $e) {
            Log::error('Ada Error di Controller Callback: ' . $e->getMessage());
            return response()->json(['status' => 'Internal Server Error'], 500);
        }
    }
}
