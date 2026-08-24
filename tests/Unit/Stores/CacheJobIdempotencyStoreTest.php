<?php

declare(strict_types=1);

use AdilAzhari\LaravelIdempotency\Stores\CacheJobIdempotencyStore;
use AdilAzhari\LaravelIdempotency\ValueObjects\JobIdempotencyRecord;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;
use Psr\SimpleCache\InvalidArgumentException;

it(/**
 * @throws InvalidArgumentException
 */ 'stores and retrieves job idempotency records', function (): void {

    $store = new CacheJobIdempotencyStore(
        new Repository(
            new ArrayStore
        )
    );

    $record = new JobIdempotencyRecord(
        key: 'job-123',
        fingerprint: 'hash',
        jobClass: 'App\\Jobs\\ChargeCustomer',
        createdAt: new DateTimeImmutable,
        expiresAt: new DateTimeImmutable('+1 day'),
    );

    $store->store(
        $record
    );

    expect(
        $store->find('job-123')
    )
        ->toBeInstanceOf(JobIdempotencyRecord::class);
});

it(/**
 * @throws InvalidArgumentException
 */ 'forgets a job idempotency record', function (): void {

    $record = new JobIdempotencyRecord(
        key: 'job-123',
        fingerprint: 'hash',
        jobClass: 'App\\Jobs\\ChargeCustomer',
        createdAt: new DateTimeImmutable,
        expiresAt: new DateTimeImmutable('+1 day'),
    );

    $store = new CacheJobIdempotencyStore(
        new Repository(
            new ArrayStore
        )
    );

    $store->store($record);
    $store->forget('job-123');

    expect($store->find('job-123'))
        ->toBeNull();
});
