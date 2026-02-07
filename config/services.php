<?php

$env = env('APP_ENV', 'local');

$prefixMap = [
    'local' => 'loc',
    'development' => 'dev',
    'production' => 'prod',
];

$queuePrefix = $prefixMap[$env] ?? $env;

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Stripe, Mailgun, SparkPost and others. This file provides a sane
    | default location for this type of information, allowing packages
    | to have a conventional place to find your various credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
    ],

    'mandrill' => [
        'secret' => '',
    ],

    'ses' => [
        'key' => env('SES_KEY'),
        'secret' => env('SES_SECRET'),
        'region' => 'us-east-1',
    ],

    'sparkpost' => [
        'secret' => env('SPARKPOST_SECRET'),
    ],

    'stripe' => [
        'model' => App\Models\User::class,
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
    ],

    'recaptcha' => [
        'site_key' => env('RECAPTCHA_SITE_KEY'),
        'secret_key' => env('RECAPTCHA_SECRET_KEY'),
        'url' => env('RECAPTCHA_URL', 'https://www.google.com/recaptcha/api/siteverify'),
    ],

    // config/services.php
    'aws' => [
        'region' => env('AWS_REGION', 'us-east-2'),
        'credentials' => [
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
        ],

        // SQS QUEUE NAMES
        'sqs' => [
            // previously env('AWS_SQS_*'), now derived from APP_ENV
            'batch_trigger' => "{$queuePrefix}-batch-trigger",
            'batch_update' => "{$queuePrefix}-batch-update",
            'image_trigger' => "{$queuePrefix}-image-trigger",
            'image_trigger_dlq' => "{$queuePrefix}-image-trigger-dlq",
            'export_update' => "{$queuePrefix}-export-update",
            'export_zip_trigger' => "{$queuePrefix}-export-zip-trigger",
            'reconcile_trigger' => "{$queuePrefix}-reconcile-trigger",
            'reconcile_update' => "{$queuePrefix}-reconcile-update",
            'ocr_update' => "{$queuePrefix}-ocr-update",
        ],

        'batch_idle_grace' => 1800,
        'export_idle_grace' => 300,
        'ocr_idle_grace' => 1500,
        'reconcile_idle_grace' => 1800,
        'zip_threshold' => 8000,

        'lambdas' => [
            'BiospexZipMerger' => 1,
            'BiospexImageProcess' => 100,
            'BiospexTesseractOcr' => 100,
            'BiospexLabelReconcile' => 8,
            'BiospexBatchCreator' => 1,
            'BiospexZipCreator' => 10,
            'BiospexImageFetcher' => 100, // Your new universal downloader
            'BiospexOcrProcessor' => 100, // Your new OCR engine
        ],
    ],
];
