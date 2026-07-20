<?php

declare(strict_types=1);

namespace AdilAzhari\LaravelIdempotency\ValueObjects;

final readonly class StoredResponse
{
    /**
     * @param  array<string, list<string|null>>  $headers
     */
    public function __construct(
        public int $status,
        public array $headers,
        public string $body,
    ) {}
}
