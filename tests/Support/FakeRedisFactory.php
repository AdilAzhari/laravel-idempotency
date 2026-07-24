<?php

declare(strict_types=1);

namespace AdilAzhari\LaravelIdempotency\Tests\Support;

use Illuminate\Contracts\Redis\Factory;

final readonly class FakeRedisFactory implements Factory
{
    private FakeRedisConnection $connection;

    public function __construct()
    {
        $this->connection = new FakeRedisConnection;
    }

    /**
     * @phpstan-ignore-next-line
     */
    public function connection($name = null): FakeRedisConnection
    {
        return $this->connection;
    }

    /**
     * @param  array<int, mixed>  $parameters
     */
    public function __call(
        string $method,
        array $parameters
    ): mixed {
        return $this->connection->{$method}(...$parameters);
    }
}
