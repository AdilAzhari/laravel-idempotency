<?php

declare(strict_types=1);

use AdilAzhari\LaravelIdempotency\Locks\CacheIdempotencyLock;
use AdilAzhari\LaravelIdempotency\Tests\Fakes\InMemoryLockProvider;

it('acquires and releases cache locks', function (): void {

    $cache = new InMemoryLockProvider;

    $lock = new CacheIdempotencyLock($cache);

    expect($lock->acquire('payment-123'))
        ->toBeTrue();

    $lock->release('payment-123');

    expect($lock->acquire('payment-123'))
        ->toBeTrue();

});

it('tracks concurrently held locks independently', function (): void {

    $cache = new InMemoryLockProvider;

    $lock = new CacheIdempotencyLock($cache);

    expect($lock->acquire('payment-123'))->toBeTrue()
        ->and($lock->acquire('payment-456'))->toBeTrue();

    $lock->release('payment-123');

    expect($lock->acquire('payment-123'))->toBeTrue()
        ->and($lock->acquire('payment-456'))->toBeFalse();

    $lock->release('payment-456');

    expect($lock->acquire('payment-456'))->toBeTrue();
});
