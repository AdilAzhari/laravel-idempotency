<?php

declare(strict_types=1);

use AdilAzhari\LaravelIdempotency\Stores\DatabaseJobIdempotencyStore;
use AdilAzhari\LaravelIdempotency\Tests\Support\JobRecordFactory;
use AdilAzhari\LaravelIdempotency\ValueObjects\JobIdempotencyRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

it('stores and retrieves a job idempotency record', function (): void {

    $store = new DatabaseJobIdempotencyStore;

    $record = JobRecordFactory::make();

    $store->store($record);

    $stored = $store->find($record->key);

    expect($stored)
        ->not->toBeNull()
        ->and($stored?->key)->toEqual($record->key)
        ->and($stored?->fingerprint)->toEqual($record->fingerprint)
        ->and($stored?->jobClass)->toEqual($record->jobClass)
        ->and($stored?->expiresAt->getTimestamp())->toEqual($record->expiresAt->getTimestamp());
});

it('returns null when record does not exist', function (): void {

    $store = new DatabaseJobIdempotencyStore;

    expect($store->find('missing-key'))
        ->toBeNull();
});

it('forgets an existing record', function (): void {

    $store = new DatabaseJobIdempotencyStore;

    $record = JobRecordFactory::make();

    $store->store($record);

    $store->forget($record->key);

    expect($store->find($record->key))
        ->toBeNull();
});

it('does not return expired records', function (): void {

    $store = new DatabaseJobIdempotencyStore;

    $record = new JobIdempotencyRecord(
        key: 'expired-key',
        fingerprint: 'fingerprint',
        jobClass: 'App\\Jobs\\ChargeCustomer',
        createdAt: new DateTimeImmutable('-2 days'),
        expiresAt: new DateTimeImmutable('-1 day'),
    );

    $store->store($record);

    expect($store->find($record->key))
        ->toBeNull();
});

it('updates an existing record with the same key', function (): void {

    $store = new DatabaseJobIdempotencyStore;

    $first = JobRecordFactory::make();

    $store->store($first);

    $updated = new JobIdempotencyRecord(
        key: $first->key,
        fingerprint: 'updated-fingerprint',
        jobClass: 'App\\Jobs\\SomethingElse',
        createdAt: $first->createdAt,
        expiresAt: $first->expiresAt,
    );

    $store->store($updated);

    $stored = $store->find($first->key);

    expect($stored?->toArray())
        ->toEqual($updated->toArray());
});
