<?php

declare(strict_types=1);

use AdilAzhari\LaravelIdempotency\ValueObjects\IdempotencyKey;
use AdilAzhari\LaravelIdempotency\ValueObjects\IdempotencyRecord;

it('can create an idempotency record', function (): void {
    $response = new IdempotencyRecord(
        key: 'test-key',
        fingerprint: 'fingerprint',
        status: 200,
        headers: [],
        body: '{}',
        createdAt: new DateTimeImmutable,
        expiresAt: new DateTimeImmutable('+1 day'),
    );

    expect($response->status)
        ->toBe(200);
});

it('determines if response is expired', function (): void {

    $response = new IdempotencyRecord(
        key: 'payment-123',
        fingerprint: 'abc123',
        status: 200,
        headers: [],
        body: '{}',
        createdAt: new DateTimeImmutable('-2 days'),
        expiresAt: new DateTimeImmutable('-1 day'),
    );

    expect($response->isExpired())
        ->toBeTrue();

});

it('represents a non-empty idempotency key as a string', function (): void {
    $key = new IdempotencyKey('payment-123');

    expect((string) $key)
        ->toBe('payment-123');
});

it('rejects an empty idempotency key', function (): void {
    expect(fn (): IdempotencyKey => new IdempotencyKey(''))
        ->toThrow(InvalidArgumentException::class);
});
