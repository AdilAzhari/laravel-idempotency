<?php

declare(strict_types=1);

use AdilAzhari\LaravelIdempotency\Contracts\JobIdempotencyStore;
use AdilAzhari\LaravelIdempotency\Stores\ArrayJobIdempotencyStore;
use AdilAzhari\LaravelIdempotency\Stores\DatabaseJobIdempotencyStore;
use AdilAzhari\LaravelIdempotency\Stores\RedisJobIdempotencyStore;
use Illuminate\Foundation\Application;

it('resolves the array job storage driver', function (): void {
    config()->set('idempotency.jobs.driver', 'array');
    /** @var Application $app */
    $app = $this->app;

    $app->forgetInstance(JobIdempotencyStore::class);

    expect(app(JobIdempotencyStore::class))
        ->toBeInstanceOf(ArrayJobIdempotencyStore::class);
});

it('resolves the database job storage driver', function (): void {
    config()->set('idempotency.jobs.driver', 'database');
    /** @var Application $app */
    $app = $this->app;

    $app->forgetInstance(JobIdempotencyStore::class);

    expect(app(JobIdempotencyStore::class))
        ->toBeInstanceOf(DatabaseJobIdempotencyStore::class);
});

it('resolves the redis job storage driver', function (): void {
    config()->set('idempotency.jobs.driver', 'redis');
    /** @var Application $app */
    $app = $this->app;

    $app->forgetInstance(JobIdempotencyStore::class);

    expect(app(JobIdempotencyStore::class))
        ->toBeInstanceOf(RedisJobIdempotencyStore::class);
});
