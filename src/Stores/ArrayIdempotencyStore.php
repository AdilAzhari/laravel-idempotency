<?php

declare(strict_types=1);

namespace AdilAzhari\LaravelIdempotency\Stores;

use AdilAzhari\LaravelIdempotency\Contracts\IdempotencyStore;
use AdilAzhari\LaravelIdempotency\ValueObjects\IdempotencyRecord;

final class ArrayIdempotencyStore implements IdempotencyStore
{
    /**
     * @var array<string, IdempotencyRecord>
     */
    private array $records = [];

    public function find(string $key): ?IdempotencyRecord
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
        IdempotencyRecord $record
    ): void {
        $this->records[$record->key] = $record;
    }

    public function forget(string $key): void
    {
        unset($this->records[$key]);
    }
}
