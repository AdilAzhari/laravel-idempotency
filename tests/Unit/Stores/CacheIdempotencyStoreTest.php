<?php

declare(strict_types=1);

use AdilAzhari\LaravelIdempotency\Stores\CacheIdempotencyStore;
use AdilAzhari\LaravelIdempotency\ValueObjects\IdempotencyRecord;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;
use Psr\SimpleCache\InvalidArgumentException;

it(
    /**
     * @throws InvalidArgumentException
     */ /**
 * @throws InvalidArgumentException
 */ 'stores and retrieves idempotency records', function (): void {

        $store = new CacheIdempotencyStore(
            new Repository(
                new ArrayStore
            )
        );

        $record = new IdempotencyRecord(
            key: 'payment-123',
            fingerprint: 'hash',
            status: 200,
            headers: [],
            body: 'response',
            createdAt: new DateTimeImmutable,
            expiresAt: new DateTimeImmutable('+1 day'),
        );

        $store->store(
            $record
        );

        expect(
            $store->find('payment-123')
        )
            ->toBeInstanceOf(IdempotencyRecord::class);
    });

it(/**
 * @throws InvalidArgumentException
 */ 'forgets an idempotency record', function (): void {

    $record = new IdempotencyRecord(
        key: 'payment-123',
        fingerprint: 'hash',
        status: 200,
        headers: [],
        body: 'response',
        createdAt: new DateTimeImmutable,
        expiresAt: new DateTimeImmutable('+1 day'),
    );

    $store = new CacheIdempotencyStore(
        new Repository(
            new ArrayStore
        )
    );

    $store->store($record);
    $store->forget('payment-123');

    expect($store->find('payment-123'))
        ->toBeNull();
});
