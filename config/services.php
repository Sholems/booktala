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

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'paystack' => [
        'secret' => env('PAYSTACK_SECRET_KEY'),
        'public' => env('PAYSTACK_PUBLIC_KEY'),
        'merchant_email' => env('PAYSTACK_MERCHANT_EMAIL'),
        'callback_url' => env('PAYSTACK_CALLBACK_URL'),
        'connect_timeout' => (int) env('PAYSTACK_CONNECT_TIMEOUT', 5),
        'timeout' => (int) env('PAYSTACK_TIMEOUT', 15),
    ],

    'termii' => [
        'key' => env('TERMII_API_KEY'),
        'sender_id' => env('TERMII_SENDER_ID', 'BookTala'),
        'connect_timeout' => (int) env('TERMII_CONNECT_TIMEOUT', 5),
        'timeout' => (int) env('TERMII_TIMEOUT', 12),
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
    ],

    'brevo' => [
        'key' => env('BREVO_API_KEY'),
        'list_id' => env('BREVO_LIST_ID'),
    ],

    'sendchamp' => [
        'public_key' => env('SENDCHAMP_PUBLIC_KEY'),
        'mode' => env('SENDCHAMP_MODE', 'live'),
        'sms_sender_id' => env('SENDCHAMP_SMS_SENDER_ID', 'BookTala'),
        'whatsapp_phone_number_id' => env('SENDCHAMP_WHATSAPP_PHONE_NUMBER_ID', env('SENDCHAMP_WHATSAPP_SENDER')),
        'route' => env('SENDCHAMP_ROUTE', 'dnd'),
    ],

    'whatsapp' => [
        'gateway' => env('WHATSAPP_GATEWAY', 'sendchamp'),
    ],

];
