<?php

declare(strict_types=1);

use AdilAzhari\LaravelIdempotency\Http\Middleware\IdempotencyMiddleware;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;

it('returns a 409 conflict when the same key is reused with different request data', function (): void {
    Route::post('/conflict-payments', fn (): ResponseFactory|Response => response('created', 201))
        ->middleware(IdempotencyMiddleware::class);

    $headers = ['Idempotency-Key' => 'payment-conflict'];

    $this->post('/conflict-payments', ['amount' => 100], $headers)
        ->assertCreated();

    $this->post('/conflict-payments', ['amount' => 500], $headers)
        ->assertStatus(409);
});

it('returns a 400 when the idempotency key exceeds the configured maximum length', function (): void {
    config()->set('idempotency.key_max_length', 10);

    Route::post('/long-key-payments', fn (): ResponseFactory|Response => response('created', 201))
        ->middleware(IdempotencyMiddleware::class);

    $headers = ['Idempotency-Key' => str_repeat('a', 11)];

    $this->post('/long-key-payments', ['amount' => 100], $headers)
        ->assertStatus(400);
});

it('does not apply idempotency to methods outside the configured list', function (): void {
    config()->set('idempotency.methods', ['POST']);

    $handled = 0;

    Route::get('/read-payments', function () use (&$handled): Response {
        $handled++;

        return response('read', 200);
    })->middleware(IdempotencyMiddleware::class);

    $headers = ['Idempotency-Key' => 'payment-read'];

    $this->get('/read-payments', $headers);
    $this->get('/read-payments', $headers);

    expect($handled)->toBe(2);
});
