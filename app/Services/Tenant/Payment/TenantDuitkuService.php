<?php

namespace App\Services\Tenant\Payment;

use App\Models\Payment;
use App\Models\BookingDetail;
use App\Enums\PaymentStatus;
use App\Enums\BookingDetailStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use UnexpectedValueException;

class TenantDuitkuService
{
    private const CODE_DUITKU_SUCCESS = '00';

    public function processWebhookCallback(array $payload): void
    {
        Log::info('--- DUITKU CALLBACK Masuk! ---');
        Log::info('Payload dari Duitku: ', $payload);

        $merchantCode = config('services.duitku.merchant_code') ?? env('DUITKU_MERCHANT_CODE');
        $apiKey = config('services.duitku.merchant_key') ?? env('DUITKU_MERCHANT_KEY');

        $this->verifyDuitkuSignature($payload, (string) $merchantCode, (string) $apiKey);

        $payment = Payment::query()->where('reference_id', $payload['reference'])->first();

        if (!$payment) {
            Log::error("GAGAL: Payment dengan reference {$payload['reference']} tidak ditemukan di database!");
            throw new UnexpectedValueException('Not Found', 404);
        }

        Log::info("Data Payment Ditemukan. Status saat ini: {$payment->status}");

        $this->updatePaymentAndBookingStates($payment, $payload);
    }

    private function verifyDuitkuSignature(array $payload, string $merchantCode, string $apiKey): void
    {
        $payloadStr = $merchantCode . $payload['amount'] . $payload['merchantOrderId'] . $apiKey;
        $calcSignatureMD5 = md5($payloadStr);
        $calcSignatureSHA = hash('sha256', $payloadStr);

        if ($payload['signature'] !== $calcSignatureMD5 && $payload['signature'] !== $calcSignatureSHA) {
            Log::error("Bad Signature!");
            Log::error("Signature dari Duitku: {$payload['signature']}");
            Log::error("Hasil MD5 Kita: {$calcSignatureMD5}");
            Log::error("Hasil SHA Kita: {$calcSignatureSHA}");
            throw new InvalidArgumentException('Bad Signature', 400);
        }

        Log::info("Signature Cocok! Memeriksa database untuk Reference: " . $payload['reference']);
    }

    private function updatePaymentAndBookingStates(Payment $payment, array $payload): void
    {
        if ($payment->status !== PaymentStatus::PENDING->value) {
            Log::info("Abaikan: Payment ini sudah diproses sebelumnya (Status: {$payment->status}).");
            return;
        }

        $bookingId = explode('-', $payload['merchantOrderId'])[0];
        $paymentCode = $payload['paymentCode'] ?? 'unknown';

        DB::transaction(function () use ($payment, $payload, $paymentCode, $bookingId) {
            if ($payload['resultCode'] === self::CODE_DUITKU_SUCCESS) {
                $payment->update([
                    'status'  => PaymentStatus::SUCCESS->value,
                    'method'  => $paymentCode,
                    'paid_at' => now()
                ]);

                BookingDetail::query()->where('fk_booking_id', $bookingId)->update([
                    'status' => BookingDetailStatus::ACTIVE->value
                ]);

                Log::info("SUKSES! Database berhasil diupdate ke active & success.");
            } else {
                $payment->update([
                    'status' => PaymentStatus::FAILED->value
                ]);

                BookingDetail::query()->where('fk_booking_id', $bookingId)->update([
                    'status' => BookingDetailStatus::CANCELLED->value
                ]);

                Log::info("DIBATALKAN! Database diupdate ke failed & cancelled karena transaksi gagal.");
            }
        });
    }
}
