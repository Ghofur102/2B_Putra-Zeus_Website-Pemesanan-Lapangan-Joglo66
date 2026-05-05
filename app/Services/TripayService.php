<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class TripayService
{
    public function getApiBaseUrl(): string
    {
        $env = config('tripay.env', 'sandbox');
        $apiUrl = config('tripay.api_url');

        if ($env === 'sandbox') {
            return rtrim($apiUrl, '/') ?: 'https://tripay.co.id/api-sandbox';
        }

        return rtrim($apiUrl, '/') ?: 'https://tripay.co.id/api';
    }

    public function createTransaction(array $payload): array
    {
        $dummyMode = config('tripay.dummy_mode', false);

        if ($dummyMode) {
            return $this->createDummyTransaction($payload);
        }

        $merchantCode = config('tripay.merchant_code');
        $apiKey = config('tripay.api_key');
        $privateKey = config('tripay.private_key');
        $callbackUrl = config('tripay.callback_url');
        $returnUrl = config('tripay.return_url');

        if (! $merchantCode || ! $apiKey || ! $privateKey || ! $callbackUrl || ! $returnUrl) {
            return [
                'success' => false,
                'message' => 'Tripay belum dikonfigurasikan dengan benar. Periksa env TRIPAY_...',
            ];
        }

        $merchantRef = $payload['merchant_ref'];
        $amount = $payload['amount'];
        $method = $payload['method'];

        $signature = hash_hmac('sha256', $merchantCode . $merchantRef . $amount, $privateKey);

        $body = [
            'method' => $method,
            'merchant_ref' => $merchantRef,
            'amount' => $amount,
            'customer_name' => $payload['customer_name'],
            'customer_email' => $payload['customer_email'],
            'customer_phone' => $payload['customer_phone'] ?? '',
            'order_items' => $payload['order_items'],
            'return_url' => $returnUrl,
            'callback_url' => $callbackUrl,
            'expired_time' => $payload['expired_time'] ?? now()->addHours(6)->timestamp,
            'signature' => $signature,
            'description' => $payload['description'] ?? 'Pembayaran booking lapangan',
        ];

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Accept' => 'application/json',
        ])->timeout(config('tripay.timeout', 10))
          ->post($this->getApiBaseUrl() . '/transaction/create', $body);

        if (! $response->successful()) {
            return [
                'success' => false,
                'message' => 'Gagal koneksi ke Tripay: ' . $response->body(),
            ];
        }

        $data = $response->json();

        if (! isset($data['success']) || ! $data['success']) {
            return [
                'success' => false,
                'message' => $data['message'] ?? 'Respon Tripay gagal',
                'payload' => $data,
            ];
        }

        return [
            'success' => true,
            'data' => $data['data'] ?? [],
        ];
    }

    public function createDummyTransaction(array $payload): array
    {
        $merchantRef = $payload['merchant_ref'];
        $amount = $payload['amount'];
        $method = $payload['method'];
        $returnUrl = config('tripay.return_url');

        $dummyCheckoutUrl = route('payment.dummy.checkout', [
            'reference' => $merchantRef,
            'amount' => $amount,
            'method' => $method,
        ]);

        return [
            'success' => true,
            'data' => [
                'reference' => $merchantRef,
                'merchant_ref' => $merchantRef,
                'checkout_url' => $dummyCheckoutUrl,
                'payment_url' => $dummyCheckoutUrl,
                'amount' => $amount,
                'method' => $method,
                'status' => 'pending',
                'created_at' => now()->toIso8601String(),
                'expired_time' => now()->addHours(6)->timestamp,
            ],
        ];
    }

    public static function validateCallback(array $payload, string|null $signature): bool
    {
        if (! isset($payload['merchant_ref'], $payload['reference'], $payload['status'], $payload['amount'])) {
            return false;
        }

        $privateKey = config('tripay.private_key');
        $expected = hash_hmac('sha256', $payload['merchant_ref'] . $payload['reference'] . $payload['status'] . $payload['amount'], $privateKey);

        return hash_equals($expected, $signature ?? ($payload['signature'] ?? ''));
    }
}
