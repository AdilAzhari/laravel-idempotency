<?php

declare(strict_types=1);

namespace AdilAzhari\LaravelIdempotency\Tests\Fakes;

use AdilAzhari\LaravelIdempotency\Contracts\IdempotencyStore;
use AdilAzhari\LaravelIdempotency\ValueObjects\StoredResponse;

final class InMemoryIdempotencyStore implements IdempotencyStore
{
    /**
     * @var array<string, StoredResponse>
     */
    public array $responses = [];

    public function get(string $key): ?StoredResponse
    {
        return $this->responses[$key] ?? null;
    }

    public function put(StoredResponse $response): void
    {
        $this->responses[$response->key] = $response;
    }

    public function delete(string $key): void
    {
        unset($this->responses[$key]);
    }
}
