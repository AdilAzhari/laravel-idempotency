<?php

declare(strict_types=1);

namespace AdilAzhari\LaravelIdempotency\Locks;

use AdilAzhari\LaravelIdempotency\Contracts\IdempotencyLock;
use Illuminate\Contracts\Cache\Lock;
use Illuminate\Contracts\Cache\LockProvider;

final class CacheIdempotencyLock implements IdempotencyLock
{
    /**
     * @var array<string, Lock>
     */
    private array $locks = [];

    public function __construct(
        private readonly LockProvider $cache,
        private readonly int $seconds = 10,
    ) {}

    public function acquire(string $key): bool
    {
        $lock = $this->cache->lock(
            $this->lockKey($key),
            $this->seconds
        );

        if (! $lock->get()) {
            return false;
        }

        $this->locks[$key] = $lock;

        return true;
    }

    public function release(string $key): void
    {
        if (! isset($this->locks[$key])) {
            return;
        }

        $this->locks[$key]->release();

        unset($this->locks[$key]);
    }

    private function lockKey(string $key): string
    {
        return 'idempotency:'.$key;
    }
}
