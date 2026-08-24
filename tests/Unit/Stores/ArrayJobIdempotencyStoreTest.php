<?php

declare(strict_types=1);

use AdilAzhari\LaravelIdempotency\Stores\ArrayJobIdempotencyStore;
use AdilAzhari\LaravelIdempotency\Tests\Support\JobRecordFactory;
use AdilAzhari\LaravelIdempotency\ValueObjects\JobIdempotencyRecord;

it('stores and retrieves a job idempotency record', function (): void {

    $store = new ArrayJobIdempotencyStore;

    $record = JobRecordFactory::make();

    $store->store($record);

    $stored = $store->find($record->key);

    expect($stored)
        ->not->toBeNull();

    assert($stored instanceof JobIdempotencyRecord);

    expect($stored->toArray())
        ->toEqual($record->toArray());
});

it('returns null when record does not exist', function (): void {

    $store = new ArrayJobIdempotencyStore;

    expect($store->find('missing-key'))
        ->toBeNull();
});

it('forgets an existing record', function (): void {

    $store = new ArrayJobIdempotencyStore;

    $record = JobRecordFactory::make();

    $store->store($record);

    $store->forget('test-key');

    expect($store->find('test-key'))
        ->toBeNull();
});

it('does not return expired records', function (): void {

    $store = new ArrayJobIdempotencyStore;

    $record = new JobIdempotencyRecord(
        key: 'expired-key',
        fingerprint: 'fingerprint',
        jobClass: 'App\\Jobs\\ChargeCustomer',
        createdAt: new DateTimeImmutable('-2 days'),
        expiresAt: new DateTimeImmutable('-1 day'),
    );

    $store->store($record);

    expect($store->find('expired-key'))
        ->toBeNull();
});
