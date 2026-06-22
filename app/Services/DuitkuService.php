<?php

namespace App\Services;

use Duitku\Config;
use Duitku\Pop;

class DuitkuService
{
    protected $duitkuConfig;

    public function __construct()
    {
        $this->duitkuConfig = new Config(env('DUITKU_MERCHANT_KEY'), env('DUITKU_MERCHANT_CODE'));
        $this->duitkuConfig->setSandboxMode(env('DUITKU_SANDBOX_MODE', true));
        $this->duitkuConfig->setSanitizedMode(true);
        $this->duitkuConfig->setDuitkuLogs(false);
    }

    public function createInvoice($booking, $amount)
    {
        $params = [
            'paymentAmount'     => (int) $amount,
            'merchantOrderId'   => $booking->id . '-' . time(),
            'productDetails'    => 'Penyewaan Lapangan - ' . $booking->team_name,
            'email'             => $booking->customer_email !== '-' ? $booking->customer_email : 'customer@joglo66.com',
            'phoneNumber'       => $booking->customer_phone,
            'customerVaName'    => $booking->team_name,
            'callbackUrl'       => env('APP_URL') . '/api/duitku/callback',
            'returnUrl'         => route('tenant.booking.success', ['booking_id' => $booking->id]),
            'expiryPeriod'      => 60
        ];

        try {
            $response = Pop::createInvoice($params, $this->duitkuConfig);
            $data = json_decode($response);

            if (isset($data->statusCode) && $data->statusCode !== '00') {
                throw new \Exception($data->statusMessage ?? 'Gagal membuat tagihan Duitku.');
            }

            return $data;
        } catch (\Exception $e) {
            throw new \Exception('Duitku Error: ' . $e->getMessage());
        }
    }
}
