<?php

declare(strict_types=1);

namespace AdilAzhari\LaravelIdempotency\ValueObjects;

use DateTimeImmutable;

final readonly class IdempotencyRecord
{
    /**
     * @param  array<string, list<string|null>>  $headers
     */
    public function __construct(
        public string $key,
        public string $fingerprint,
        public int $status,
        /**
         * @var array<string, array<int, string|null>>
         */
        public array $headers,
        public string $body,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $expiresAt,
    ) {}

    public function isExpired(): bool
    {
        return $this->expiresAt <= new DateTimeImmutable;
    }
}
