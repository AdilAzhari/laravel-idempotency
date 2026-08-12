<?php

declare(strict_types=1);

namespace AdilAzhari\LaravelIdempotency;

use AdilAzhari\LaravelIdempotency\Commands\PruneIdempotencyRecordsCommand;
use AdilAzhari\LaravelIdempotency\Contracts\IdempotencyLock;
use AdilAzhari\LaravelIdempotency\Contracts\IdempotencyStore;
use AdilAzhari\LaravelIdempotency\Contracts\RequestFingerprinter;
use AdilAzhari\LaravelIdempotency\Http\Middleware\IdempotencyMiddleware;
use AdilAzhari\LaravelIdempotency\Locks\CacheIdempotencyLock;
use AdilAzhari\LaravelIdempotency\Stores\ArrayIdempotencyStore;
use AdilAzhari\LaravelIdempotency\Stores\CacheIdempotencyStore;
use AdilAzhari\LaravelIdempotency\Stores\DatabaseIdempotencyStore;
use AdilAzhari\LaravelIdempotency\Stores\RedisIdempotencyStore;
use AdilAzhari\LaravelIdempotency\Support\Sha256RequestFingerprinter;
use AdilAzhari\LaravelIdempotency\ValueObjects\IdempotencyKey;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Redis\Factory as RedisFactory;
use Illuminate\Routing\Router;
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

        $this->app->bind(
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

                $methods = $config->get(
                    'idempotency.methods',
                    ['POST', 'PUT', 'PATCH', 'DELETE'],
                );

                $maxKeyLength = $config->get(
                    'idempotency.key_max_length',
                    IdempotencyKey::DEFAULT_MAX_LENGTH,
                );

                $replayHeader = $config->get(
                    'idempotency.replay_header',
                    'Idempotency-Replayed',
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
                    methods: is_array($methods)
                        ? array_values(array_map(
                            mb_strtoupper(...),
                            array_filter($methods, is_string(...)),
                        ))
                        : [],
                    maxKeyLength: is_int($maxKeyLength)
                        ? $maxKeyLength
                        : IdempotencyKey::DEFAULT_MAX_LENGTH,
                    replayHeader: is_string($replayHeader) && $replayHeader !== ''
                        ? $replayHeader
                        : null,
                );
            },
        );
    }

    /**
     * @throws BindingResolutionException
     */
    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/idempotency.php' => config_path('idempotency.php'),
        ], 'idempotency-config');

        $this->publishesMigrations([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'idempotency-migrations');

        if ($this->app->runningInConsole()) {
            $this->commands([
                PruneIdempotencyRecordsCommand::class,
            ]);
        }

        $this->app->make(Router::class)
            ->aliasMiddleware(
                'idempotency',
                IdempotencyMiddleware::class
            );
    }
}
