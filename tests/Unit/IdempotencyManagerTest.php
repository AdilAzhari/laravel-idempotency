<?php

declare(strict_types=1);

use AdilAzhari\LaravelIdempotency\Exceptions\IdempotencyConflictException;
use AdilAzhari\LaravelIdempotency\Exceptions\IdempotencyLockConflictException;
use AdilAzhari\LaravelIdempotency\IdempotencyManager;
use AdilAzhari\LaravelIdempotency\Support\Sha256RequestFingerprinter;
use AdilAzhari\LaravelIdempotency\Tests\Fakes\InMemoryIdempotencyLock;
use AdilAzhari\LaravelIdempotency\Tests\Fakes\InMemoryIdempotencyStore;
use AdilAzhari\LaravelIdempotency\ValueObjects\IdempotencyRecord;
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

it('replays the original response for an identical request', function (): void {
    $manager = new IdempotencyManager(
        new InMemoryIdempotencyStore,
        new Sha256RequestFingerprinter,
        new InMemoryIdempotencyLock,
    );

    $firstRequest = idempotencyRequest('payment-123');
    $secondRequest = idempotencyRequest('payment-123');
    $handled = 0;

    $firstResponse = $manager->handle($firstRequest, function () use (&$handled): Response {
        $handled++;

        return new Response('created', 201, ['X-Payment-Id' => 'payment-1']);
    });

    $secondResponse = $manager->handle($secondRequest, function () use (&$handled): Response {
        $handled++;

        return new Response('should not be returned');
    });

    expect($handled)->toBe(1)
        ->and($firstResponse->getStatusCode())->toBe(201)
        ->and($secondResponse->getStatusCode())->toBe(201)
        ->and($secondResponse->getContent())->toBe('created')
        ->and($secondResponse->headers->get('X-Payment-Id'))->toBe('payment-1');
});

it('passes requests without an idempotency key through unchanged', function (): void {
    $manager = new IdempotencyManager(
        new InMemoryIdempotencyStore,
        new Sha256RequestFingerprinter,
        new InMemoryIdempotencyLock,
    );
    $handled = 0;

    $response = $manager->handle(Request::create('/payments', 'POST'), function () use (&$handled): Response {
        $handled++;

        return new Response('processed');
    });

    expect($handled)->toBe(1)
        ->and($response->getContent())->toBe('processed');
});

/**
 * @throws JsonException
 */
it('ignores an expired response record', function (): void {
    $store = new InMemoryIdempotencyStore;
    $fingerprinter = new Sha256RequestFingerprinter;
    $request = idempotencyRequest('payment-123');
    $store->store(new IdempotencyRecord(
        key: 'payment-123',
        fingerprint: $fingerprinter->fingerprint($request),
        status: 201,
        headers: [],
        body: 'expired response',
        createdAt: new DateTimeImmutable('-2 days'),
        expiresAt: new DateTimeImmutable('-1 day'),
    ));

    $manager = new IdempotencyManager(
        $store,
        $fingerprinter,
        new InMemoryIdempotencyLock,
    );

    $response = $manager->handle($request, fn (): Response => new Response('processed', 202));

    expect($response->getStatusCode())->toBe(202)
        ->and($response->getContent())->toBe('processed');
});

it('releases the lock when the endpoint throws', function (): void {
    $manager = new IdempotencyManager(
        new InMemoryIdempotencyStore,
        new Sha256RequestFingerprinter,
        new InMemoryIdempotencyLock,
    );
    $request = idempotencyRequest('payment-123');

    expect(fn (): Response => $manager->handle($request, fn () => throw new RuntimeException('Failed')))
        ->toThrow(RuntimeException::class, 'Failed');

    $response = $manager->handle($request, fn (): Response => new Response('processed'));

    expect($response->getContent())->toBe('processed');
});

it('marks a fresh response as not replayed and a cached response as replayed', function (): void {
    $manager = new IdempotencyManager(
        new InMemoryIdempotencyStore,
        new Sha256RequestFingerprinter,
        new InMemoryIdempotencyLock,
    );

    $firstResponse = $manager->handle(
        idempotencyRequest('payment-123'),
        fn (): Response => new Response('created', 201)
    );

    $secondResponse = $manager->handle(
        idempotencyRequest('payment-123'),
        fn (): Response => new Response('should not be returned')
    );

    expect($firstResponse->headers->get('Idempotency-Replayed'))->toBe('false')
        ->and($secondResponse->headers->get('Idempotency-Replayed'))->toBe('true');
});

it('does not add a replay header when disabled', function (): void {
    $manager = new IdempotencyManager(
        new InMemoryIdempotencyStore,
        new Sha256RequestFingerprinter,
        new InMemoryIdempotencyLock,
        replayHeader: null,
    );

    $response = $manager->handle(
        idempotencyRequest('payment-123'),
        fn (): Response => new Response('created', 201)
    );

    expect($response->headers->has('Idempotency-Replayed'))->toBeFalse();
});

it('ignores the idempotency key for methods outside the configured list', function (): void {
    $manager = new IdempotencyManager(
        new InMemoryIdempotencyStore,
        new Sha256RequestFingerprinter,
        new InMemoryIdempotencyLock,
        methods: ['POST'],
    );

    $handled = 0;

    $request = Request::create('/payments', 'GET');
    $request->headers->set('Idempotency-Key', 'payment-123');

    $manager->handle($request, function () use (&$handled): Response {
        $handled++;

        return new Response('first');
    });

    $manager->handle($request, function () use (&$handled): Response {
        $handled++;

        return new Response('second');
    });

    expect($handled)->toBe(2);
});

it('throws a lock conflict exception when the lock cannot be acquired', function (): void {
    $lock = new InMemoryIdempotencyLock;
    $lock->acquire('payment-123');

    $manager = new IdempotencyManager(
        new InMemoryIdempotencyStore,
        new Sha256RequestFingerprinter,
        $lock,
    );

    expect(fn (): Response => $manager->handle(
        idempotencyRequest('payment-123'),
        fn (): Response => new Response('created', 201)
    ))->toThrow(IdempotencyLockConflictException::class);
});

function idempotencyRequest(string $key, int $amount = 100): Request
{
    $request = Request::create('/payments', 'POST', ['amount' => $amount]);
    $request->headers->set('Idempotency-Key', $key);

    return $request;
}
