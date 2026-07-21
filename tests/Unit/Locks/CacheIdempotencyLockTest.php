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
