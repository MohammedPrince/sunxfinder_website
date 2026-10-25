<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Mailer
    |--------------------------------------------------------------------------
    |
    | This option controls the default mailer that is used to send all email
    | messages unless another mailer is explicitly specified when sending
    | the message. All additional mailers can be configured within the
    | "mailers" array. Examples of each type of mailer are provided.
    |
    */

    'default' => env('MAIL_MAILER', 'log'),

    /*
    |--------------------------------------------------------------------------
    | Mailer Configurations
    |--------------------------------------------------------------------------
    |
    | Here you may configure all of the mailers used by your application plus
    | their respective settings. Several examples have been configured for
    | you and you are free to add your own as your application requires.
    |
    | Laravel supports a variety of mail "transport" drivers that can be used
    | when delivering an email. You may specify which one you're using for
    | your mailers below. You may also add additional mailers if needed.
    |
    | Supported: "smtp", "sendmail", "mailgun", "ses", "ses-v2",
    |            "postmark", "resend", "log", "array",
    |            "failover", "roundrobin"
    |
    */

    'mailers' => [

        'smtp' => [
            'transport' => 'smtp',
            'scheme' => env('MAIL_SCHEME'),
            'url' => env('MAIL_URL'),
            'host' => env('MAIL_HOST', '127.0.0.1'),
            'port' => env('MAIL_PORT', 2525),
            'username' => env('MAIL_USERNAME'),
            'password' => env('MAIL_PASSWORD'),
            'timeout' => null,
            'local_domain' => env('MAIL_EHLO_DOMAIN', parse_url((string) env('APP_URL', 'http://localhost'), PHP_URL_HOST)),
        ],

        // Password reset OTP emails, sent from forget@sunxfinder.com.
        // Falls back to the default SMTP account when the MAIL_RESET_* values are not set.
        'password_reset' => [
            'transport' => 'smtp',
            'scheme' => env('MAIL_RESET_SCHEME', env('MAIL_SCHEME')),
            'host' => env('MAIL_RESET_HOST', env('MAIL_HOST', '127.0.0.1')),
            'port' => env('MAIL_RESET_PORT', env('MAIL_PORT', 2525)),
            'username' => env('MAIL_RESET_USERNAME', env('MAIL_USERNAME')),
            'password' => env('MAIL_RESET_PASSWORD', env('MAIL_PASSWORD')),
            'timeout' => 15,
            'local_domain' => env('MAIL_EHLO_DOMAIN', parse_url((string) env('APP_URL', 'http://localhost'), PHP_URL_HOST)),
        ],

        // Offer accepted/rejected emails to companies, sent from offer@sunxfinder.com (no-reply).
        // Falls back to the default SMTP account when the MAIL_OFFER_* values are not set.
        'offer' => [
            'transport' => 'smtp',
            'scheme' => env('MAIL_OFFER_SCHEME', env('MAIL_SCHEME')),
            'host' => env('MAIL_OFFER_HOST', env('MAIL_HOST', '127.0.0.1')),
            'port' => env('MAIL_OFFER_PORT', env('MAIL_PORT', 2525)),
            'username' => env('MAIL_OFFER_USERNAME', env('MAIL_USERNAME')),
            'password' => env('MAIL_OFFER_PASSWORD', env('MAIL_PASSWORD')),
            'timeout' => 15,
            'local_domain' => env('MAIL_EHLO_DOMAIN', parse_url((string) env('APP_URL', 'http://localhost'), PHP_URL_HOST)),
        ],

        'ses' => [
            'transport' => 'ses',
        ],

        'postmark' => [
            'transport' => 'postmark',
            // 'message_stream_id' => env('POSTMARK_MESSAGE_STREAM_ID'),
            // 'client' => [
            //     'timeout' => 5,
            // ],
        ],

        'resend' => [
            'transport' => 'resend',
        ],

        'sendmail' => [
            'transport' => 'sendmail',
            'path' => env('MAIL_SENDMAIL_PATH', '/usr/sbin/sendmail -bs -i'),
        ],

        'log' => [
            'transport' => 'log',
            'channel' => env('MAIL_LOG_CHANNEL'),
        ],

        'array' => [
            'transport' => 'array',
        ],

        'failover' => [
            'transport' => 'failover',
            'mailers' => [
                'smtp',
                'log',
            ],
            'retry_after' => 60,
        ],

        'roundrobin' => [
            'transport' => 'roundrobin',
            'mailers' => [
                'ses',
                'postmark',
            ],
            'retry_after' => 60,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Global "From" Address
    |--------------------------------------------------------------------------
    |
    | You may wish for all emails sent by your application to be sent from
    | the same address. Here you may specify a name and address that is
    | used globally for all emails that are sent by your application.
    |
    */

    'from' => [
        'address' => env('MAIL_FROM_ADDRESS', 'hello@example.com'),
        'name' => env('MAIL_FROM_NAME', env('APP_NAME', 'Laravel')),
    ],

    // Sender for password reset OTP emails (see the "password_reset" mailer).
    'password_reset_from' => [
        'address' => env('MAIL_RESET_FROM_ADDRESS', 'forget@sunxfinder.com'),
        'name' => env('MAIL_RESET_FROM_NAME', env('MAIL_FROM_NAME', 'SunXFinder')),
    ],

    // Inbox that receives the website "Contact us" form.
    'contact_address' => env('MAIL_CONTACT_ADDRESS', 'info@sunxfinder.com'),

    // No-reply sender for offer decision emails (see the "offer" mailer).
    'offer_from' => [
        'address' => env('MAIL_OFFER_FROM_ADDRESS', 'offer@sunxfinder.com'),
        'name' => env('MAIL_OFFER_FROM_NAME', 'SunXFinder (No-Reply)'),
    ],

    // Admin who is told about new company sign-ups.
    'admin_notify_address' => env('MAIL_ADMIN_NOTIFY_ADDRESS', 'mohammed.beng@gmail.com'),

];
