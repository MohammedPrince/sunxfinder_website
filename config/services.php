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

    'whatsapp' => [
        'phone_number' => env('PHONE_NUMBER'),
    ],

    // SMS for OTP codes. Keep disabled until a provider is connected:
    // the API then only offers the "email" channel.
    'sms' => [
        'enabled' => env('SMS_ENABLED', false),
        'driver' => env('SMS_DRIVER', 'log'),
    ],

    // "Open in App" deep links (website company page -> mobile app).
    // The app opens sunxfinder://company/{slug} and https://<site>/open/companies/{slug}.
    'mobile_app' => [
        'scheme' => env('APP_DEEP_LINK_SCHEME', 'sunxfinder'),
        // Android App Links (/.well-known/assetlinks.json)
        'android_package' => env('ANDROID_PACKAGE_NAME'),
        // Comma-separated SHA-256 signing certificate fingerprints (Play Console > App integrity)
        'android_sha256' => env('ANDROID_SHA256_FINGERPRINTS'),
        // iOS Universal Links (/.well-known/apple-app-site-association): "TEAMID.bundle.id"
        'ios_app_id' => env('IOS_APP_ID'),
    ],

    // Push notification sound. The file must be bundled in the mobile app:
    // Android res/raw/<sound>.wav (and a channel with that sound), iOS <sound>.wav.
    'fcm' => [
        'sound' => env('FCM_SOUND', 'sunxfinder_soft_notification'),
        // v2: the app deletes the old "sunxfinder_notifications" channel, so the old id would land nowhere.
        'android_channel_id' => env('FCM_ANDROID_CHANNEL_ID', 'sunxfinder_notifications_v2'),
    ],

];
