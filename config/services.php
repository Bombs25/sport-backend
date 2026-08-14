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
    'typesense' => [
        'host' => env('TYPESENSE_HOST', 'https://typesenses.echoppe.store'),
        'port' => (int) env('TYPESENSE_PORT', 8080),
        'protocol' => env('TYPESENSE_PROTOCOL', 'https'),
        'api_key' => env('TYPESENSE_API_KEY', 'ri8zqlpvmkx1nvgu8kfzv808bhssyree'),
        // Désactivé automatiquement en APP_ENV=testing (voir TypesenseSyncGuard).
        'sync_enabled' => env('TYPESENSE_SYNC_ENABLED', true),
    ],

    'sendbird' => [
        // App ID public (aussi exposé à l'app mobile). Le token reste serveur uniquement.
        'app_id' => env('SENDBIRD_APP_ID'),
        'api_token' => env('SENDBIRD_API_TOKEN'),
        'base_url' => env('SENDBIRD_API_BASE_URL'),
    ],

];
