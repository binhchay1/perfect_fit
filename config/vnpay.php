<?php

return [
    /*
    |--------------------------------------------------------------------------
    | VNPay Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for VNPay payment gateway integration
    |
    */

    // VNPay Terminal Code (TMN Code)
    'tmn_code' => env('VNPAY_TMN_CODE', 'YOUR_TMN_CODE'),

    // VNPay Hash Secret
    'hash_secret' => env('VNPAY_HASH_SECRET', 'YOUR_HASH_SECRET'),

    // VNPay Payment URL
    'url' => env('VNPAY_URL', 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html'),

    // Return URL after payment
    'return_url' => env('VNPAY_RETURN_URL', 'http://localhost:8000/api/payment/vnpay/callback'),

    // VNPay API URL
    'api_url' => env('VNPAY_API_URL', 'https://sandbox.vnpayment.vn/merchant_webapi/api/transaction'),

    // VNPay Version
    'version' => '2.1.0',

    // Currency Code
    'currency' => 'VND',

    // Locale
    'locale' => 'vn',

    // Command
    'command' => 'pay',

    // Order Type
    'order_type' => 'other',

    // Timeout (minutes)
    'timeout' => 15,

    // Test card information for sandbox
    'test_card' => [
        'number' => '9704198526191432198',
        'cvv' => '123',
        'expire_date' => '12/25',
        'otp' => '123456',
    ],
];