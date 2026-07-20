<?php

declare(strict_types=1);

namespace AdilAzhari\LaravelIdempotency\Contracts;

use AdilAzhari\LaravelIdempotency\ValueObjects\StoredResponse;

interface IdempotencyStore
{
    public function get(string $key): ?StoredResponse;

    public function put(
        StoredResponse $response
    ): void;

    public function delete(string $key): void;
}
