<?php

declare(strict_types=1);

use AdilAzhari\LaravelIdempotency\Contracts\IdempotencyStore;
use AdilAzhari\LaravelIdempotency\Stores\ArrayIdempotencyStore;
use AdilAzhari\LaravelIdempotency\Stores\DatabaseIdempotencyStore;
use AdilAzhari\LaravelIdempotency\Stores\RedisIdempotencyStore;

it('resolves the array storage driver', function (): void {
    config()->set('idempotency.driver', 'array');

    $this->app->forgetInstance(IdempotencyStore::class);

    expect(app(IdempotencyStore::class))
        ->toBeInstanceOf(ArrayIdempotencyStore::class);
});

it('resolves the database storage driver', function (): void {
    config()->set('idempotency.driver', 'database');

    $this->app->forgetInstance(IdempotencyStore::class);

    expect(app(IdempotencyStore::class))
        ->toBeInstanceOf(DatabaseIdempotencyStore::class);
});

it('resolves the redis storage driver', function (): void {
    config()->set('idempotency.driver', 'redis');

    $this->app->forgetInstance(IdempotencyStore::class);

    expect(app(IdempotencyStore::class))
        ->toBeInstanceOf(RedisIdempotencyStore::class);
});
