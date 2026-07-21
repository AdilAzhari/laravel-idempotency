<?php

declare(strict_types=1);

use AdilAzhari\LaravelIdempotency\Exceptions\IdempotencyConflictException;
use AdilAzhari\LaravelIdempotency\IdempotencyManager;
use AdilAzhari\LaravelIdempotency\Support\Sha256RequestFingerprinter;
use AdilAzhari\LaravelIdempotency\Tests\Fakes\InMemoryIdempotencyLock;
use AdilAzhari\LaravelIdempotency\Tests\Fakes\InMemoryIdempotencyStore;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

it('throws exception when same key is used with different request', function (): void {

    $manager = new IdempotencyManager(
        new InMemoryIdempotencyStore,
        new Sha256RequestFingerprinter,
        new InMemoryIdempotencyLock,
    );

    $firstRequest = Request::create(
        '/payments',
        'POST',
        [
            'amount' => 100,
        ]
    );

    $firstRequest->headers->set(
        'Idempotency-Key',
        'payment-123'
    );

    $manager->handle(
        $firstRequest,
        fn (): Response => new Response('success')
    );

    $secondRequest = Request::create(
        '/payments',
        'POST',
        [
            'amount' => 500,
        ]
    );

    $secondRequest->headers->set(
        'Idempotency-Key',
        'payment-123'
    );

    expect(fn (): Response => $manager->handle(
        $secondRequest,
        fn (): Response => new Response('success')
    ))
        ->toThrow(IdempotencyConflictException::class);

});
