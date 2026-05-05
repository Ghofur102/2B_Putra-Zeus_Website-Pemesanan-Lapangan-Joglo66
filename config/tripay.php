<?php

return [
    'merchant_code' => env('TRIPAY_MERCHANT_CODE'),
    'api_key' => env('TRIPAY_API_KEY'),
    'private_key' => env('TRIPAY_PRIVATE_KEY'),
    'callback_url' => env('TRIPAY_CALLBACK_URL'),
    'return_url' => env('TRIPAY_RETURN_URL'),
    'timeout' => env('TRIPAY_TIMEOUT', 10),
    'env' => env('TRIPAY_ENV', 'sandbox'),
    'api_url' => env('TRIPAY_API_URL', 'https://tripay.co.id/api'),
    'dummy_mode' => env('TRIPAY_DUMMY_MODE', false),
];
