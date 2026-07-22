<?php

declare(strict_types=1);

namespace AdilAzhari\LaravelIdempotency;

use AdilAzhari\LaravelIdempotency\Contracts\IdempotencyLock;
use AdilAzhari\LaravelIdempotency\Contracts\IdempotencyStore;
use AdilAzhari\LaravelIdempotency\Contracts\RequestFingerprinter;
use AdilAzhari\LaravelIdempotency\Locks\CacheIdempotencyLock;
use AdilAzhari\LaravelIdempotency\Stores\CacheIdempotencyStore;
use AdilAzhari\LaravelIdempotency\Support\Sha256RequestFingerprinter;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\ServiceProvider;
use RuntimeException;

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
            IdempotencyStore::class,
            static function (Container $app): IdempotencyStore {
                /** @var Repository $cache */
                $cache = $app->make(Repository::class);

                return new CacheIdempotencyStore($cache);
            },
        );

        $this->app->bind(
            IdempotencyLock::class,
            static function (Container $app): IdempotencyLock {
                /** @var Repository $cache */
                $cache = $app->make(Repository::class);

                $lockProvider = $cache->getStore();

                if (! $lockProvider instanceof LockProvider) {
                    throw new RuntimeException(
                        'The configured cache store must support atomic locks.'
                    );
                }

                /** @var ConfigRepository $config */
                $config = $app->make(ConfigRepository::class);

                $lockSeconds = $config->get('idempotency.lock.seconds', 10);

                return new CacheIdempotencyLock(
                    $lockProvider,
                    is_int($lockSeconds) ? $lockSeconds : 10,
                );
            }
        );

        $this->app->singleton(IdempotencyManager::class, static function (Container $app): IdempotencyManager {
            /** @var ConfigRepository $config */
            $config = $app->make(ConfigRepository::class);

            $header = $config->get('idempotency.header', 'Idempotency-Key');
            $expiration = $config->get('idempotency.expiration', 86400);

            return new IdempotencyManager(
                store: $app->make(IdempotencyStore::class),
                fingerprinter: $app->make(RequestFingerprinter::class),
                lock: $app->make(IdempotencyLock::class),
                header: is_string($header) ? $header : 'Idempotency-Key',
                expiration: is_int($expiration) ? $expiration : 86400,
            );
        });
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/idempotency.php' => config_path('idempotency.php'),
        ], 'idempotency-config');
    }
}
