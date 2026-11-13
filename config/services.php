<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
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

    'cas' => [
        'host' => env('CAS_HOST', 'sso.uoa.gr'),
        'port' => (int) env('CAS_PORT', 443),
        'ca_cert' => env('CAS_CA_CERT', '/etc/ssl/certs/ca-certificates.crt'),
    ],

    'atlas' => [
        'url' => env('ATLAS_URL', 'https://submit-atlas.grnet.gr/Api/Offices/v1'),
        'username' => env('ATLAS_USERNAME'),
        'password' => env('ATLAS_PASSWORD'),
    ],

];
