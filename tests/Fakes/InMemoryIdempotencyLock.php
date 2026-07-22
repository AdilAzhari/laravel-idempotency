<?php

declare(strict_types=1);

namespace AdilAzhari\LaravelIdempotency\Tests\Fakes;

use AdilAzhari\LaravelIdempotency\Contracts\IdempotencyLock;

final class InMemoryIdempotencyLock implements IdempotencyLock
{
    public bool $released = false;

    /**
     * @var array<string,bool>
     */
    private array $locks = [];

    public function acquire(string $key): bool
    {
        if (isset($this->locks[$key])) {
            return false;
        }

        $this->locks[$key] = true;

        return true;
    }

    public function release(string $key): void
    {
        $this->released = true;

        unset($this->locks[$key]);
    }
}
