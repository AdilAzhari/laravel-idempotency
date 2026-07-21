<?php

declare(strict_types=1);

namespace AdilAzhari\LaravelIdempotency\Contracts;

interface IdempotencyLock
{
    public function acquire(string $key): bool;

    public function release(string $key): void;
}
