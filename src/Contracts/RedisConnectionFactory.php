<?php

declare(strict_types=1);

namespace AdilAzhari\LaravelIdempotency\Contracts;

interface RedisConnectionFactory
{
    public function connection(
        ?string $name = null
    ): object;
}
