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

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

  
'stripe' => [
    'key' => env('STRIPE_KEY'),
    'secret' => env('STRIPE_SECRET'),
    'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    'prices' => [
        // BASIC
        'basic_12' => env('STRIPE_PRICE_BASIC_12'),
        'basic_24' => env('STRIPE_PRICE_BASIC_24'),

        // PROFESSIONAL
        'professional_12' => env('STRIPE_PRICE_PRO_12'),
        'professional_24' => env('STRIPE_PRICE_PRO_24'),

        // ADVANCED
        'advanced_12' => env('STRIPE_PRICE_ADV_12'),
        'advanced_24' => env('STRIPE_PRICE_ADV_24'),
        // EXTRA BARBERS
        'professional_extra_12' => env('STRIPE_PRICE_PRO_EXTRA_12'),
        'professional_extra_24' => env('STRIPE_PRICE_PRO_EXTRA_24'),

        'advanced_extra_12' => env('STRIPE_PRICE_ADV_EXTRA_12'),
        'advanced_extra_24' => env('STRIPE_PRICE_ADV_EXTRA_24'),
    ],
    'tax_rate' => env('STRIPE_TAX_RATE_BE'),
],




    'whatsapp' => [
        'access_token' => env('WHATSAPP_ACCESS_TOKEN'),
        'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
        'verify_token' => env('WHATSAPP_VERIFY_TOKEN'),
    ],

    'telegram' => [
        'bot_token' => env('TELEGRAM_BOT_TOKEN'),
    ],

];
