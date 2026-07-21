<?php

declare(strict_types=1);

namespace AdilAzhari\LaravelIdempotency\Locks;

use AdilAzhari\LaravelIdempotency\Contracts\IdempotencyLock;
use Illuminate\Contracts\Cache\Lock;
use Illuminate\Contracts\Cache\LockProvider;

final class CacheIdempotencyLock implements IdempotencyLock
{
    private ?Lock $lock = null;

    public function __construct(
        private readonly LockProvider $cache,
        private readonly int $seconds = 10,
    ) {}

    public function acquire(string $key): bool
    {
        $this->lock = $this->cache->lock(
            $this->lockKey($key),
            $this->seconds
        );

        return (bool) $this->lock->get();
    }

    public function release(string $key): void
    {
        $this->lock?->release();
    }

    private function lockKey(string $key): string
    {
        return 'idempotency:'.$key;
    }
}
