<?php

declare(strict_types=1);

use AdilAzhari\LaravelIdempotency\Stores\ArrayIdempotencyStore;
use AdilAzhari\LaravelIdempotency\Tests\Support\RecordFactory;
use AdilAzhari\LaravelIdempotency\ValueObjects\IdempotencyRecord;

it('stores and retrieves an idempotency record', function (): void {

    $store = new ArrayIdempotencyStore;

    $record = RecordFactory::make();

    $store->store($record);

    $stored = $store->find($record->key);

    expect($stored)
        ->not->toBeNull();

    assert($stored instanceof IdempotencyRecord);

    expect($stored->toArray())
        ->toEqual($record->toArray());
});

it('returns null when record does not exist', function (): void {

    $store = new ArrayIdempotencyStore;

    expect($store->find('missing-key'))
        ->toBeNull();
});

it('forgets an existing record', function (): void {

    $store = new ArrayIdempotencyStore;

    $record = RecordFactory::make();

    $store->store($record);

    $store->forget('test-key');

    expect($store->find('test-key'))
        ->toBeNull();
});

it('does not return expired records', function (): void {

    $store = new ArrayIdempotencyStore;

    $record = new IdempotencyRecord(
        key: 'expired-key',
        fingerprint: 'fingerprint',
        status: 200,
        headers: [],
        body: '{}',
        createdAt: new DateTimeImmutable('-2 days'),
        expiresAt: new DateTimeImmutable('-1 day'),
    );

    $store->store($record);

    expect($store->find('expired-key'))
        ->toBeNull();
});
