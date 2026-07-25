<?php

declare(strict_types=1);

use AdilAzhari\LaravelIdempotency\Stores\DatabaseIdempotencyStore;
use AdilAzhari\LaravelIdempotency\Tests\Support\RecordFactory;
use AdilAzhari\LaravelIdempotency\ValueObjects\IdempotencyRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

it('stores and retrieves an idempotency record', function (): void {

    $store = new DatabaseIdempotencyStore;

    $record = RecordFactory::make();

    $store->store($record);

    $stored = $store->find($record->key);

    expect($stored)
        ->not->toBeNull()
        ->and($stored?->key)->toEqual($record->key)
        ->and($stored?->fingerprint)->toEqual($record->fingerprint)
        ->and($stored?->status)->toEqual($record->status)
        ->and($stored?->headers)->toEqual($record->headers)
        ->and($stored?->body)->toEqual($record->body)
        ->and($stored?->expiresAt->getTimestamp())->toEqual($record->expiresAt->getTimestamp());
});

it('returns null when record does not exist', function (): void {

    $store = new DatabaseIdempotencyStore;

    expect($store->find('missing-key'))
        ->toBeNull();
});

it('forgets an existing record', function (): void {

    $store = new DatabaseIdempotencyStore;

    $record = RecordFactory::make();

    $store->store($record);

    $store->forget($record->key);

    expect($store->find($record->key))
        ->toBeNull();
});

it('does not return expired records', function (): void {

    $store = new DatabaseIdempotencyStore;

    $record = new IdempotencyRecord(
        key: 'expired-key',
        fingerprint: 'fingerprint',
        status: 200,
        headers: [
            'Content-Type' => [
                'application/json',
            ],
        ],
        body: '{}',
        createdAt: new DateTimeImmutable('-2 days'),
        expiresAt: new DateTimeImmutable('-1 day'),
    );

    $store->store($record);

    expect($store->find($record->key))
        ->toBeNull();
});

it('updates an existing record with the same key', function (): void {

    $store = new DatabaseIdempotencyStore;

    $first = RecordFactory::make();

    $store->store($first);

    $updated = new IdempotencyRecord(
        key: $first->key,
        fingerprint: 'updated-fingerprint',
        status: 201,
        headers: [],
        body: '{"updated":true}',
        createdAt: $first->createdAt,
        expiresAt: $first->expiresAt,
    );

    $store->store($updated);

    $stored = $store->find($first->key);

    expect($stored?->toArray())
        ->toEqual($updated->toArray());
});
