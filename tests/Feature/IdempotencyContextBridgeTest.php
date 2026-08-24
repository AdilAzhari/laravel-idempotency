<?php

declare(strict_types=1);

use AdilAzhari\LaravelIdempotency\Http\Middleware\IdempotencyMiddleware;
use AdilAzhari\LaravelIdempotency\Support\IdempotencyContext;
use AdilAzhari\LaravelIdempotency\Tests\Fakes\IdempotentTestJob;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Route;

beforeEach(function (): void {
    IdempotentTestJob::$handled = [];
});

it('makes the HTTP idempotency key readable via IdempotencyContext::current() inside the request', function (): void {
    Route::post('/context-payments', function (): ResponseFactory|Response {
        expect(IdempotencyContext::current())->toBe('payment-context-1');

        return response('created', 201);
    })->middleware(IdempotencyMiddleware::class);

    $this->post('/context-payments', ['amount' => 100], [
        'Idempotency-Key' => 'payment-context-1',
    ])->assertCreated();
});

it('lets a job dispatched from the request adopt the request idempotency key as its own', function (): void {
    Route::post('/context-payments-job', function (): ResponseFactory|Response {
        Bus::dispatchSync(new IdempotentTestJob(amount: 100, useContextKey: true));

        return response('created', 201);
    })->middleware(IdempotencyMiddleware::class);

    $this->post('/context-payments-job', ['amount' => 100], [
        'Idempotency-Key' => 'payment-context-2',
    ])->assertCreated();

    expect(IdempotentTestJob::$handled)->toBe([100]);

    // Redispatching the same job shape while the request's key is still the
    // active context must be deduplicated exactly like any other job key.
    Bus::dispatchSync(new IdempotentTestJob(amount: 100, useContextKey: true));

    expect(IdempotentTestJob::$handled)->toBe([100]);
});
