<?php

declare(strict_types=1);

use AdilAzhari\LaravelIdempotency\Contracts\RequestFingerprinter;
use AdilAzhari\LaravelIdempotency\Http\Middleware\IdempotencyMiddleware;
use AdilAzhari\LaravelIdempotency\IdempotencyManager;
use AdilAzhari\LaravelIdempotency\Support\Sha256RequestFingerprinter;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;

it('registers package bindings', function (): void {
    expect(app(RequestFingerprinter::class))
        ->toBeInstanceOf(Sha256RequestFingerprinter::class);
});

it('resolves the idempotency manager with the configured cache store', function (): void {
    app(IdempotencyManager::class);

    expect(app()->resolved(IdempotencyManager::class))
        ->toBeTrue();
});

it('replays an idempotent route response', function (): void {
    $handled = 0;

    Route::post('/payments', function () use (&$handled): ResponseFactory|Response {
        $handled++;

        return response('created', 201, ['X-Payment-Id' => 'payment-1']);
    })->middleware(IdempotencyMiddleware::class);

    $headers = ['Idempotency-Key' => 'payment-123'];

    $this->post('/payments', ['amount' => 100], $headers)
        ->assertCreated()
        ->assertHeader('X-Payment-Id', 'payment-1')
        ->assertContent('created');

    $this->post('/payments', ['amount' => 100], $headers)
        ->assertCreated()
        ->assertHeader('X-Payment-Id', 'payment-1')
        ->assertContent('created');

    expect($handled)->toBe(1);
});
