<?php

declare(strict_types=1);

namespace AdilAzhari\LaravelIdempotency\Tests\Fakes;

use AdilAzhari\LaravelIdempotency\Contracts\IdempotencyStore;
use AdilAzhari\LaravelIdempotency\ValueObjects\IdempotencyRecord;

final class InMemoryIdempotencyStore implements IdempotencyStore
{
    /**
     * @var array<string, IdempotencyRecord>
     */
    private array $records = [];

    public function find(string $key): ?IdempotencyRecord
    {
        return $this->records[$key] ?? null;
    }

    public function store(
        IdempotencyRecord $record,
    ): void {
        $this->records[$record->key] = $record;
    }

    public function forget(string $key): void
    {
        unset($this->records[$key]);
    }
}
