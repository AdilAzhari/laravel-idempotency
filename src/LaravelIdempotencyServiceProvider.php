<?php

declare(strict_types=1);

namespace AdilAzhari\LaravelIdempotency;

use AdilAzhari\LaravelIdempotency\Contracts\IdempotencyLock;
use AdilAzhari\LaravelIdempotency\Contracts\RequestFingerprinter;
use AdilAzhari\LaravelIdempotency\Locks\CacheIdempotencyLock;
use AdilAzhari\LaravelIdempotency\Support\Sha256RequestFingerprinter;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Container\Container;
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
            static function (Container $app): IdempotencyLock {
                /** @var LockProvider $lockProvider */
                $lockProvider = $app->make(LockProvider::class);

                /** @var ConfigRepository $config */
                $config = $app->make(ConfigRepository::class);

                /** @var int $lockSeconds */
                $lockSeconds = $config->get('idempotency.lock.seconds', 10);

                return new CacheIdempotencyLock(
                    $lockProvider,
                    $lockSeconds,
                );
            }
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
