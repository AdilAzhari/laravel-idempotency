<?php

declare(strict_types=1);

namespace AdilAzhari\LaravelIdempotency\Contracts;

use AdilAzhari\LaravelIdempotency\ValueObjects\IdempotencyRecord;

interface IdempotencyStore
{
    public function find(string $key): ?IdempotencyRecord;

    public function store(
        IdempotencyRecord $record
    ): void;

    public function forget(string $key): void;
}
