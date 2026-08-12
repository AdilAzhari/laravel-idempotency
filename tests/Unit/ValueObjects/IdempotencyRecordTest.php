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

it('detects active records', function (): void {
    $record = new IdempotencyRecord(
        key: 'active-key',
        fingerprint: 'fingerprint',
        status: 200,
        headers: [],
        body: '{}',
        createdAt: new DateTimeImmutable,
        expiresAt: new DateTimeImmutable('+1 day'),
    );

    expect($record->isExpired())
        ->toBeFalse();
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

it('rejects an idempotency key exceeding the maximum length', function (): void {
    $key = str_repeat('a', 256);

    expect(fn (): IdempotencyKey => new IdempotencyKey($key))
        ->toThrow(InvalidArgumentException::class);
});

it('accepts a custom maximum length', function (): void {
    $key = str_repeat('a', 10);

    expect((string) new IdempotencyKey($key, maxLength: 10))
        ->toBe($key)
        ->and(fn (): IdempotencyKey => new IdempotencyKey($key, maxLength: 9))->toThrow(InvalidArgumentException::class);
});

it(/**
 * @throws DateMalformedStringException
 */ 'can serialize and deserialize an idempotency record', function (): void {

    $record = new IdempotencyRecord(
        key: 'payment-request-123',
        fingerprint: 'fingerprint-value',
        status: 201,
        headers: [
            'Content-Type' => [
                'application/json',
            ],
            'X-Test' => [
                'value',
                null,
            ],
        ],
        body: '{"message":"success"}',
        createdAt: new DateTimeImmutable('2026-07-24T10:00:00+00:00'),
        expiresAt: new DateTimeImmutable('2026-07-25T10:00:00+00:00'),
    );

    $array = $record->toArray();

    $restored = IdempotencyRecord::fromArray($array);

    expect($restored)
        ->toEqual($record);
});
