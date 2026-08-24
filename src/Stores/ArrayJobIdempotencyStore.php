<?php

declare(strict_types=1);

namespace AdilAzhari\LaravelIdempotency\Stores;

use AdilAzhari\LaravelIdempotency\Contracts\JobIdempotencyStore;
use AdilAzhari\LaravelIdempotency\ValueObjects\JobIdempotencyRecord;

final class ArrayJobIdempotencyStore implements JobIdempotencyStore
{
    /**
     * @var array<string, JobIdempotencyRecord>
     */
    private array $records = [];

    public function find(string $key): ?JobIdempotencyRecord
    {
        $record = $this->records[$key] ?? null;

        if ($record === null) {
            return null;
        }

        if ($record->isExpired()) {
            unset($this->records[$key]);

            return null;
        }

        return $record;
    }

    public function store(
        JobIdempotencyRecord $record
    ): void {
        $this->records[$record->key] = $record;
    }

    public function forget(string $key): void
    {
        unset($this->records[$key]);
    }
}
