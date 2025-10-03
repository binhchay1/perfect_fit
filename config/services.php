<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'perfect_fit_ai' => [
        'url' => env('PERFECT_FIT_AI_URL', 'https://ai.perfectfit.com/api'),
        'key' => env('PERFECT_FIT_AI_KEY', ''),
    ],

    // Google OAuth (for Social Login)
    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URL'),
    ],

    // Gmail API (for Email Sending via API - SMTP ports blocked)
    'gmail' => [
        'client_id' => env('GOOGLE_MAIL_CLIENT_ID'),
        'client_secret' => env('GOOGLE_MAIL_CLIENT_SECRET'),
        'refresh_token' => env('GOOGLE_MAIL_REFRESH_TOKEN'),
        'from' => [
            'address' => env('GOOGLE_MAIL_FROM', env('MAIL_FROM_ADDRESS')),
            'name' => env('GOOGLE_MAIL_FROM_NAME', env('MAIL_FROM_NAME', 'Perfect Fit')),
        ],
        'scopes' => [
            'https://www.googleapis.com/auth/gmail.send',
        ],
    ],

    'facebook' => [
        'client_id' => env('FACEBOOK_CLIENT_ID'),
        'client_secret' => env('FACEBOOK_CLIENT_SECRET'),
        'redirect' => env('FACEBOOK_REDIRECT_URL'),
    ],

    'tiktok' => [
        'client_key' => env('TIKTOK_CLIENT_KEY'),
        'client_secret' => env('TIKTOK_CLIENT_SECRET'),
        'redirect' => env('TIKTOK_REDIRECT_URL'),
    ],

    'sms' => [
        'provider' => env('SMS_PROVIDER', 'log'), // Options: log, twilio, firebase, esms, speedsms
        'url' => env('SMS_API_URL', 'https://sms-api.com'),
        'key' => env('SMS_API_KEY', ''),
        'sender' => env('SMS_SENDER', 'PerfectFit'),
    ],

    // Twilio SMS Service (International - Free trial with credits)
    'twilio' => [
        'sid' => env('TWILIO_SID', ''),
        'token' => env('TWILIO_TOKEN', ''),
        'from' => env('TWILIO_FROM', ''), // Your Twilio phone number
    ],

    // eSMS Vietnam Service
    'esms' => [
        'api_key' => env('ESMS_API_KEY', ''),
        'secret_key' => env('ESMS_SECRET_KEY', ''),
        'brandname' => env('ESMS_BRANDNAME', 'PerfectFit'),
    ],

    // SpeedSMS Vietnam Service
    'speedsms' => [
        'access_token' => env('SPEEDSMS_ACCESS_TOKEN', ''),
        'sender' => env('SPEEDSMS_SENDER', 'PerfectFit'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

];
