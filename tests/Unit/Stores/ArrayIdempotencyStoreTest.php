<?php

declare(strict_types=1);

use AdilAzhari\LaravelIdempotency\Stores\ArrayIdempotencyStore;
use AdilAzhari\LaravelIdempotency\ValueObjects\IdempotencyRecord;
use DateTimeImmutable;


function makeRecord(
    string $key = 'test-key'
): IdempotencyRecord {
    return new IdempotencyRecord(
        key: $key,
        fingerprint: 'fingerprint',
        status: 200,
        headers: [
            'Content-Type' => [
                'application/json',
            ],
        ],
        body: '{"success":true}',
        createdAt: new DateTimeImmutable(),
        expiresAt: new DateTimeImmutable('+1 day'),
    );
}


it('stores and retrieves an idempotency record', function (): void {

    $store = new ArrayIdempotencyStore();

    $record = makeRecord();


    $store->store($record);


    expect($store->find('test-key'))
        ->toEqual($record);
});


it('returns null when record does not exist', function (): void {

    $store = new ArrayIdempotencyStore();


    expect($store->find('missing-key'))
        ->toBeNull();
});


it('forgets an existing record', function (): void {

    $store = new ArrayIdempotencyStore();

    $record = makeRecord();


    $store->store($record);

    $store->forget('test-key');


    expect($store->find('test-key'))
        ->toBeNull();
});


it('does not return expired records', function (): void {

    $store = new ArrayIdempotencyStore();

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
