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
        'secret' => env('STRIPE_SECRET')
    ],

    'zapier' => [
        'user_webhook' => env('ZAPIER_USER_WEBHOOK_URL'),
    ],
    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
    ],
    'facebook' => [
        'client_id' => env('FACEBOOK_CLIENT_ID'),
        'client_secret' => env('FACEBOOK_CLIENT_SECRET'),
        'redirect' => env('FACEBOOK_REDIRECT_URI'),
    ],
    'apple' => [
        'client_id' => env('APPLE_CLIENT_ID'),
        'client_secret' => env('APPLE_CLIENT_SECRET'),
        'redirect' => env('APPLE_REDIRECT_URI'),
        'team_id' => env('APPLE_TEAM_ID'),
        'key_id' => env('APPLE_KEY_ID'),
        'private_key' => env('APPLE_PRIVATE_KEY_PATH'),
        'frontend_redirect_url' => env('APPLE_FRONTEND_REDIRECT_URL', env('APP_FRONTEND_URL')),
        'android_package_id' => env('APPLE_ANDROID_PACKAGE_ID'),
        'android_scheme' => env('APPLE_ANDROID_SCHEME'),
    ],
    'twilio' => [
        'sid' => env('TWILIO_SID'),
        'token' => env('TWILIO_AUTH_TOKEN'),
        'phone_number' => env('TWILIO_PHONE_NUMBER')
    ],
    'chat' => [
        'host' => env('CHAT_APP_HOST'),
        'verify_ssl' => env('CHAT_VERIFY_SSL', true),
    ],
    'hockey_listing' => [
        'fee_sku' => env('HOCKEY_LISTING_FEE_SKU'),
    ],
    'event' => [
        'fee_sku' => env('EVENT_PLATFORM_FEE_SKU', 'event_platform_fee'),
        'fee_amount_cents' => (int) env('EVENT_PLATFORM_FEE_AMOUNT_CENTS', 0),
    ],
    'share_link' => [
        'base_url' => env('SHARE_LINK_BASE_URL', 'https://link.drafthouselabs.com'),
    ],
];
