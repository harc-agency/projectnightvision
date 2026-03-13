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
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'analysis_model' => env('OPENAI_ANALYSIS_MODEL', 'gpt-4.1-mini'),
        'symbol_model' => env('OPENAI_SYMBOL_MODEL', 'gpt-4.1-mini'),
        'symbol_target_count' => (int) env('OPENAI_SYMBOL_TARGET_COUNT', 3),
        'image_model' => env('OPENAI_IMAGE_MODEL', 'gpt-image-1'),
        'symbol_image_queue' => env('OPENAI_SYMBOL_IMAGE_QUEUE', 'images'),
        'symbol_image_quality' => env('OPENAI_SYMBOL_IMAGE_QUALITY', 'low'),
        'symbol_image_size' => env('OPENAI_SYMBOL_IMAGE_SIZE', '1024x1024'),
        'symbol_image_timeout' => (int) env('OPENAI_SYMBOL_IMAGE_TIMEOUT', 240),
        'symbol_image_tries' => (int) env('OPENAI_SYMBOL_IMAGE_TRIES', 2),
        'symbol_image_backoff' => (int) env('OPENAI_SYMBOL_IMAGE_BACKOFF', 15),
        'transcription_model' => env('OPENAI_TRANSCRIPTION_MODEL', 'gpt-4o-mini-transcribe'),
    ],

    'location_predictor' => [
        'endpoint' => env('LOCATION_PREDICTOR_ENDPOINT', 'https://photon.komoot.io/api'),
        'source' => env('LOCATION_PREDICTOR_SOURCE', 'location_predictor'),
    ],

];
