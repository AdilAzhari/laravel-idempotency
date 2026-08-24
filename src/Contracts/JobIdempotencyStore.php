<?php

declare(strict_types=1);

namespace AdilAzhari\LaravelIdempotency\Contracts;

use AdilAzhari\LaravelIdempotency\ValueObjects\JobIdempotencyRecord;

interface JobIdempotencyStore
{
    public function find(string $key): ?JobIdempotencyRecord;

    public function store(
        JobIdempotencyRecord $record
    ): void;

    public function forget(string $key): void;
}
