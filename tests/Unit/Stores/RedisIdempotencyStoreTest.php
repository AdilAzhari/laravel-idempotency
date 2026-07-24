<?php

declare(strict_types=1);

use AdilAzhari\LaravelIdempotency\Stores\RedisIdempotencyStore;
use AdilAzhari\LaravelIdempotency\Tests\Support\FakeRedisFactory;
use AdilAzhari\LaravelIdempotency\Tests\Support\RecordFactory;
use AdilAzhari\LaravelIdempotency\ValueObjects\IdempotencyRecord;

function makeRedisStore(): RedisIdempotencyStore
{
    return new RedisIdempotencyStore(
        redis: new FakeRedisFactory,
    );
}

/**
 * @throws DateMalformedStringException
 * @throws JsonException
 */
it('stores and retrieves an idempotency record', function (): void {

    $store = makeRedisStore();

    $record = RecordFactory::make();

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

    $store = makeRedisStore();

    expect($store->find('missing'))
        ->toBeNull();
});

it('forgets a stored record', function (): void {

    $store = makeRedisStore();

    $record = RecordFactory::make();

    $store->store($record);

    $store->forget($record->key);

    expect($store->find($record->key))
        ->toBeNull();
});

it('does not store expired records', function (): void {

    $store = makeRedisStore();

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

    expect($store->find($record->key))
        ->toBeNull();
});
