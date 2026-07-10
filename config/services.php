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

    /*
    |--------------------------------------------------------------------------
    | FBR Digital Invoicing
    |--------------------------------------------------------------------------
    */
    'fbr' => [
        'enabled'          => env('FBR_ENABLED', false),
        'sandbox'          => env('FBR_SANDBOX_MODE', true),
        'token'            => env('FBR_SANDBOX_MODE', true)
                                 ? env('FBR_SANDBOX_TOKEN')
                                 : env('FBR_PROD_TOKEN'),
        'post_url'         => env('FBR_SANDBOX_MODE', true)
                                 ? env('FBR_SANDBOX_POST_URL')
                                 : env('FBR_PROD_POST_URL'),
        'validate_url'     => env('FBR_SANDBOX_MODE', true)
                                 ? env('FBR_SANDBOX_VALIDATE_URL')
                                 : env('FBR_PROD_VALIDATE_URL'),
        'default_pct_code' => env('FBR_DEFAULT_PCT_CODE', '99000000'),
    ],

];
