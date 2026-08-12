<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Storage Driver
    |--------------------------------------------------------------------------
    |
    | Supported:
    | - array
    | - cache
    | - redis
    | - database
    |
    */

    'driver' => env('IDEMPOTENCY_STORE', 'cache'),

    /*
    |--------------------------------------------------------------------------
    | Storage Drivers
    |--------------------------------------------------------------------------
    */

    'stores' => [

        'array' => [
            'driver' => 'array',
        ],

        'cache' => [
            'driver' => 'cache',
        ],

        'redis' => [
            'driver' => 'redis',
            'connection' => env('IDEMPOTENCY_REDIS_CONNECTION', 'default'),
            'prefix' => env('IDEMPOTENCY_REDIS_PREFIX', 'idempotency:'),
        ],

        'database' => [
            'driver' => 'database',
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Idempotency Header
    |--------------------------------------------------------------------------
    */

    'header' => 'Idempotency-Key',

    /*
    |--------------------------------------------------------------------------
    | Applicable HTTP Methods
    |--------------------------------------------------------------------------
    |
    | The idempotency key is only honoured for these HTTP methods. Requests
    | using any other method are always passed through untouched, even if
    | they carry an idempotency key header. An empty array disables this
    | restriction and applies idempotency to every method.
    |
    */

    'methods' => ['POST', 'PUT', 'PATCH', 'DELETE'],

    /*
    |--------------------------------------------------------------------------
    | Idempotency Key Length
    |--------------------------------------------------------------------------
    |
    | The maximum number of characters allowed in an idempotency key. Keys
    | longer than this are rejected with a 400 response before they are
    | used to build cache, lock, or storage identifiers.
    |
    */

    'key_max_length' => 255,

    /*
    |--------------------------------------------------------------------------
    | Replay Header
    |--------------------------------------------------------------------------
    |
    | When set, this response header is added to every idempotency-protected
    | response with a value of "true" for replayed responses and "false" for
    | freshly executed ones. Set to null to disable it entirely.
    |
    */

    'replay_header' => 'Idempotency-Replayed',

    /*
    |--------------------------------------------------------------------------
    | Lock Configuration
    |--------------------------------------------------------------------------
    */

    'lock' => [

        'seconds' => 10,

    ],

    /*
    |--------------------------------------------------------------------------
    | Record Expiration
    |--------------------------------------------------------------------------
    */

    'expiration' => 86400,
];
