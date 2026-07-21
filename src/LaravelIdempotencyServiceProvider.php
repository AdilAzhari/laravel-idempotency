<?php

declare(strict_types=1);

namespace AdilAzhari\LaravelIdempotency;

use AdilAzhari\LaravelIdempotency\Contracts\IdempotencyLock;
use AdilAzhari\LaravelIdempotency\Contracts\RequestFingerprinter;
use AdilAzhari\LaravelIdempotency\Locks\CacheIdempotencyLock;
use AdilAzhari\LaravelIdempotency\Support\Sha256RequestFingerprinter;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Support\ServiceProvider;

final class LaravelIdempotencyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/idempotency.php',
            'idempotency'
        );

        $this->app->bind(
            RequestFingerprinter::class,
            Sha256RequestFingerprinter::class
        );

        $this->app->bind(
            IdempotencyLock::class,
            fn ($app): IdempotencyLock => new CacheIdempotencyLock(
                $app->make(LockProvider::class),
                config('idempotency.lock.seconds')
            )
        );

        $this->app->singleton(
            IdempotencyManager::class
        );
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/idempotency.php' => config_path('idempotency.php'),
        ], 'idempotency-config');
    }
}
