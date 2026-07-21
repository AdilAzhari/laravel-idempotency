<?php

declare(strict_types=1);

use AdilAzhari\LaravelIdempotency\ValueObjects\IdempotencyRecord;
it('can create a stored response or an idempotency record', function (): void {
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
