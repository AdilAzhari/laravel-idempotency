<?php

declare(strict_types=1);

namespace AdilAzhari\LaravelIdempotency\Stores;

use AdilAzhari\LaravelIdempotency\Contracts\JobIdempotencyStore;
use AdilAzhari\LaravelIdempotency\ValueObjects\JobIdempotencyRecord;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Psr\SimpleCache\InvalidArgumentException;

final readonly class CacheJobIdempotencyStore implements JobIdempotencyStore
{
    public function __construct(
        private CacheRepository $cache
    ) {}

    /**
     * @throws InvalidArgumentException
     */
    public function find(string $key): ?JobIdempotencyRecord
    {
        $record = $this->cache->get(
            $this->key($key)
        );

        if (! $record instanceof JobIdempotencyRecord) {
            return null;
        }

        if ($record->isExpired()) {
            $this->cache->forget(
                $this->key($key)
            );

            return null;
        }

        return $record;
    }

    public function store(
        JobIdempotencyRecord $record,
    ): void {
        $seconds = max(
            1,
            $record->expiresAt->getTimestamp()
            - time()
        );

        $this->cache->put(
            $this->key($record->key),
            $record,
            $seconds
        );
    }

    public function forget(string $key): void
    {
        $this->cache->forget(
            $this->key($key)
        );
    }

    private function key(string $key): string
    {
        return 'job-idempotency:'.$key;
    }
}
