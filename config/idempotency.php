<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Idempotency Header
    |--------------------------------------------------------------------------
    |
    | The HTTP header used to identify idempotent requests.
    |
    */

    'header' => 'Idempotency-Key',

    /*
    |--------------------------------------------------------------------------
    | Lock Configuration
    |--------------------------------------------------------------------------
    |
    | How long a request owns the idempotency lock.
    |
    */

    'lock' => [

        'seconds' => 10,

    ],

    /*
    |--------------------------------------------------------------------------
    | Record Expiration
    |--------------------------------------------------------------------------
    |
    | How long stored responses remain valid.
    |
    */

    'expiration' => 86400,

    'redis' => [
        'connection' => 'default',
        'prefix' => 'laravel-idempotency:',
    ],
];
