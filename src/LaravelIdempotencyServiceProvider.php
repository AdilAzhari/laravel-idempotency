<?php

declare(strict_types=1);

namespace AdilAzhari\LaravelIdempotency;

use AdilAzhari\LaravelIdempotency\Commands\PruneIdempotencyRecordsCommand;
use AdilAzhari\LaravelIdempotency\Contracts\IdempotencyLock;
use AdilAzhari\LaravelIdempotency\Contracts\IdempotencyStore;
use AdilAzhari\LaravelIdempotency\Contracts\RequestFingerprinter;
use AdilAzhari\LaravelIdempotency\Locks\CacheIdempotencyLock;
use AdilAzhari\LaravelIdempotency\Stores\ArrayIdempotencyStore;
use AdilAzhari\LaravelIdempotency\Stores\CacheIdempotencyStore;
use AdilAzhari\LaravelIdempotency\Stores\DatabaseIdempotencyStore;
use AdilAzhari\LaravelIdempotency\Stores\RedisIdempotencyStore;
use AdilAzhari\LaravelIdempotency\Support\Sha256RequestFingerprinter;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Redis\Factory as RedisFactory;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;
use RuntimeException;

final class LaravelIdempotencyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/idempotency.php',
            'idempotency',
        );

        $this->app->bind(
            RequestFingerprinter::class,
            Sha256RequestFingerprinter::class,
        );

        $this->app->singleton(
            IdempotencyStore::class,
            static function (Container $app): IdempotencyStore {

                /** @var ConfigRepository $config */
                $config = $app->make(ConfigRepository::class);

                /** @var string $driver */
                $driver = $config->get(
                    'idempotency.driver',
                    'cache'
                );

                /** @var array<string, mixed> $store */
                $store = $config->get(
                    'idempotency.stores.'.$driver,
                    []
                );

                return match ($store['driver'] ?? null) {

                    'array' => new ArrayIdempotencyStore,

                    'cache' => new CacheIdempotencyStore(
                        $app->make(Repository::class),
                    ),

                    'redis' => new RedisIdempotencyStore(
                        redis: $app->make(RedisFactory::class),
                        connection: is_string($store['connection'] ?? null)
                            ? $store['connection']
                            : 'default',
                        prefix: is_string($store['prefix'] ?? null)
                            ? $store['prefix']
                            : 'idempotency:',
                    ),

                    'database' => new DatabaseIdempotencyStore,

                    default => throw new InvalidArgumentException(
                        sprintf(
                            'Unsupported idempotency driver [%s].',
                            $driver,
                        )
                    ),
                };
            }
        );

        $this->app->bind(
            IdempotencyLock::class,
            static function (Container $app): IdempotencyLock {
                /** @var Repository $cache */
                $cache = $app->make(Repository::class);

                $lockProvider = $cache->getStore();

                if (! $lockProvider instanceof LockProvider) {
                    throw new RuntimeException(
                        'The configured cache store must support atomic locks.',
                    );
                }

                /** @var ConfigRepository $config */
                $config = $app->make(ConfigRepository::class);

                $seconds = $config->get(
                    'idempotency.lock.seconds',
                    10,
                );

                return new CacheIdempotencyLock(
                    $lockProvider,
                    is_int($seconds) ? $seconds : 10,
                );
            },
        );

        $this->app->singleton(
            IdempotencyManager::class,
            static function (Container $app): IdempotencyManager {
                /** @var ConfigRepository $config */
                $config = $app->make(ConfigRepository::class);

                $header = $config->get(
                    'idempotency.header',
                    'Idempotency-Key',
                );

                $expiration = $config->get(
                    'idempotency.expiration',
                    86400,
                );

                return new IdempotencyManager(
                    store: $app->make(IdempotencyStore::class),
                    fingerprinter: $app->make(RequestFingerprinter::class),
                    lock: $app->make(IdempotencyLock::class),
                    header: is_string($header)
                        ? $header
                        : 'Idempotency-Key',
                    expiration: is_int($expiration)
                        ? $expiration
                        : 86400,
                );
            },
        );
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/idempotency.php' => config_path('idempotency.php'),
        ], 'idempotency-config');

        if ($this->app->runningInConsole()) {
            $this->commands([
                PruneIdempotencyRecordsCommand::class,
            ]);
        }
    }
}
