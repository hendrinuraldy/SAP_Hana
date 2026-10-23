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
    'sap_b1' => [
        'base_url'       => env('SAP_B1_BASE_URL'),
        'company_db'     => env('SAP_B1_COMPANY_DB'),
        'verify_ssl'     => env('SAP_B1_VERIFY_SSL', false),
        'sync_username'  => env('SAP_B1_SYNC_USERNAME'),
        'sync_password'  => env('SAP_B1_SYNC_PASSWORD'),
        'adhoc_admin_users' => array_filter(explode(',', env('SAP_ADHOC_ADMIN_USERS', ''))),
    ],

];
