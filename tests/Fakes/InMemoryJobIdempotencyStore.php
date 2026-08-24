<?php

declare(strict_types=1);

namespace AdilAzhari\LaravelIdempotency\Tests\Fakes;

use AdilAzhari\LaravelIdempotency\Contracts\JobIdempotencyStore;
use AdilAzhari\LaravelIdempotency\ValueObjects\JobIdempotencyRecord;

final class InMemoryJobIdempotencyStore implements JobIdempotencyStore
{
    /**
     * @var array<string, JobIdempotencyRecord>
     */
    private array $records = [];

    public function find(string $key): ?JobIdempotencyRecord
    {
        return $this->records[$key] ?? null;
    }

    public function store(
        JobIdempotencyRecord $record,
    ): void {
        $this->records[$record->key] = $record;
    }

    public function forget(string $key): void
    {
        unset($this->records[$key]);
    }
}
