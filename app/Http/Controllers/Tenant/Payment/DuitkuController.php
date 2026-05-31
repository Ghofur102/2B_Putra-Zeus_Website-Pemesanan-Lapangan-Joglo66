<?php

namespace App\Http\Controllers\Tenant\Payment;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\BookingDetail;
use App\Models\Payment;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use UnexpectedValueException;
use Throwable;

class DuitkuController extends Controller
{
    // Solusi php:S1192 - Ekstraksi Seluruh String Duplikat ke Konstanta Kelas
    private const STATUS_PENDING = 'pending';
    private const STATUS_SUCCESS = 'success';
    private const STATUS_FAILED = 'failed';
    private const STATUS_ACTIVE = 'active';
    private const STATUS_CANCELLED = 'cancelled';
    private const CODE_DUITKU_SUCCESS = '00';

    /**
     * Handle payment notification callback from Duitku Payment Gateway
     * Solusi php:S1142 - Menggunakan Tepat 1 Gerbang Keluar Return di Akhir Fungsi
     */
    public function callback(Request $request): JsonResponse
    {
        Log::info('--- DUITKU CALLBACK Masuk! ---');
        Log::info('Payload dari Duitku: ', $request->all() ?? []);

        $statusCode = 200;
        $data = ['status' => 'Success'];

        try {
            // Best Practice Laravel: Membaca kredensial dari config() daripada env() secara langsung
            $merchantCode = config('services.duitku.merchant_code') ?? env('DUITKU_MERCHANT_CODE');
            $apiKey = config('services.duitku.merchant_key') ?? env('DUITKU_MERCHANT_KEY');

            $this->validateCallbackParameters($request);
            $this->verifyDuitkuSignature($request, (string) $merchantCode, (string) $apiKey);

            $reference = $request->input('reference');
            $payment = Payment::where('reference_id', $reference)->first();

            if (!$payment) {
                Log::error("GAGAL: Payment dengan reference {$reference} tidak ditemukan di database!");
                throw new UnexpectedValueException('Not Found', 404);
            }

            Log::info("Data Payment Ditemukan. Status saat ini: {$payment->status}");

            $this->updatePaymentAndBookingStates($payment, $request);

        } catch (InvalidArgumentException $e) {
            $statusCode = $e->getCode() ?: 400;
            $data = ['status' => $e->getMessage()];
        } catch (UnexpectedValueException $e) {
            $statusCode = $e->getCode() ?: 404;
            $data = ['status' => $e->getMessage()];
        } catch (Throwable $e) {
            Log::error('Ada Error di Controller Callback: ' . $e->getMessage());
            $statusCode = 500;
            $data = ['status' => 'Internal Server Error'];
        }

        return response()->json($data, $statusCode);
    }

    /**
     * Private Helper: Validasi kelengkapan parameter request (php:S112)
     */
    private function validateCallbackParameters(Request $request): void
    {
        if (!$request->has(['merchantOrderId', 'reference', 'signature'])) {
            Log::error('Callback gagal: Parameter dari Duitku tidak lengkap.');
            throw new InvalidArgumentException('Bad Request', 400);
        }
    }

    /**
     * Private Helper: Validasi keabsahan signature MD5 dan SHA256 (php:S3776)
     */
    private function verifyDuitkuSignature(Request $request, string $merchantCode, string $apiKey): void
    {
        $amount = $request->input('amount');
        $merchantOrderId = $request->input('merchantOrderId');
        $signature = $request->input('signature');

        $payloadStr = $merchantCode . $amount . $merchantOrderId . $apiKey;
        $calcSignatureMD5 = md5($payloadStr);
        $calcSignatureSHA = hash('sha256', $payloadStr);

        if ($signature !== $calcSignatureMD5 && $signature !== $calcSignatureSHA) {
            Log::error("Bad Signature!");
            Log::error("Signature dari Duitku: {$signature}");
            Log::error("Hasil MD5 Kita: {$calcSignatureMD5}");
            Log::error("Hasil SHA Kita: {$calcSignatureSHA}");
            throw new InvalidArgumentException('Bad Signature', 400);
        }

        Log::info("Signature Cocok! Memeriksa database untuk Reference: " . $request->input('reference'));
    }

    /**
     * Private Helper: Memproses perubahan status pembayaran dan pemesanan secara atomik (php:S3776)
     */
    private function updatePaymentAndBookingStates(Payment $payment, Request $request): void
    {
        if ($payment->status === self::STATUS_PENDING) {
            $resultCode = $request->input('resultCode');
            $merchantOrderId = $request->input('merchantOrderId');
            $paymentCode = $request->input('paymentCode', 'unknown');
            $bookingId = explode('-', $merchantOrderId)[0];

            // Menggunakan DB Transaction untuk memastikan kedua kueri sukses/gagal bersamaan
            DB::transaction(function () use ($payment, $resultCode, $paymentCode, $bookingId) {
                if ($resultCode === self::CODE_DUITKU_SUCCESS) {
                    $payment->update([
                        'status' => self::STATUS_SUCCESS,
                        'method' => $paymentCode,
                        'paid_at' => now()
                    ]);

                    BookingDetail::where('fk_booking_id', $bookingId)->update(['status' => self::STATUS_ACTIVE]);
                    Log::info("SUKSES! Database berhasil diupdate ke active & success.");
                } else {
                    $payment->update(['status' => self::STATUS_FAILED]);
                    BookingDetail::where('fk_booking_id', $bookingId)->update(['status' => self::STATUS_CANCELLED]);
                    Log::info("DIBATALKAN! Database diupdate ke failed & cancelled karena transaksi gagal.");
                }
            });
        } else {
            Log::info("Abaikan: Payment ini sudah diproses sebelumnya (Status: {$payment->status}).");
        }
    }
}
