<?php

declare(strict_types=1);

use AdilAzhari\LaravelIdempotency\Stores\RedisJobIdempotencyStore;
use AdilAzhari\LaravelIdempotency\Tests\Support\FakeRedisFactory;
use AdilAzhari\LaravelIdempotency\Tests\Support\JobRecordFactory;
use AdilAzhari\LaravelIdempotency\ValueObjects\JobIdempotencyRecord;

function makeRedisJobStore(): RedisJobIdempotencyStore
{
    return new RedisJobIdempotencyStore(
        redis: new FakeRedisFactory,
    );
}

/**
 * @throws DateMalformedStringException
 * @throws JsonException
 */
it('stores and retrieves a job idempotency record', function (): void {

    $store = makeRedisJobStore();

    $record = JobRecordFactory::make();

    $store->store($record);

    $stored = $store->find($record->key);

    expect($stored)
        ->not->toBeNull()
        ->and($stored?->toArray())
        ->toEqual($record->toArray());

});

it(/**
 * @throws DateMalformedStringException
 * @throws JsonException
 */ 'returns null for missing records', function (): void {

    $store = makeRedisJobStore();

    expect($store->find('missing'))
        ->toBeNull();
});

it('forgets a stored record', function (): void {

    $store = makeRedisJobStore();

    $record = JobRecordFactory::make();

    $store->store($record);

    $store->forget($record->key);

    expect($store->find($record->key))
        ->toBeNull();
});

it('does not store expired records', function (): void {

    $store = makeRedisJobStore();

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
