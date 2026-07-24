<?php

declare(strict_types=1);

namespace AdilAzhari\LaravelIdempotency\ValueObjects;

use DateMalformedStringException;
use DateTimeImmutable;

/**
 * @phpstan-type IdempotencyRecordData array{
 *     key: string,
 *     fingerprint: string,
 *     status: int,
 *     headers: array<string, list<string|null>>,
 *     body: string,
 *     created_at: string,
 *     expires_at: string
 * }
 */
final readonly class IdempotencyRecord
{
    /**
     * @param  array<string, list<string|null>>  $headers
     */
    public function __construct(
        public string $key,
        public string $fingerprint,
        public int $status,
        //        /**
        //         * @var array<string, array<int, string|null>>
        //         */
        /**
         * @var array<string, list<string|null>>
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

    /**
     * @return array{
     *     key: string,
     *     fingerprint: string,
     *     status: int,
     *     headers: array<string, list<string|null>>,
     *     body: string,
     *     created_at: string,
     *     expires_at: string
     * }
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'fingerprint' => $this->fingerprint,
            'status' => $this->status,
            'headers' => $this->headers,
            'body' => $this->body,
            'created_at' => $this->createdAt->format(DATE_ATOM),
            'expires_at' => $this->expiresAt->format(DATE_ATOM),
        ];
    }

    /**
     * @param  IdempotencyRecordData  $data
     *
     * @throws DateMalformedStringException
     */
    public static function fromArray(array $data): self
    {
        return new self(
            key: $data['key'],
            fingerprint: $data['fingerprint'],
            status: $data['status'],
            headers: $data['headers'],
            body: $data['body'],
            createdAt: new DateTimeImmutable(
                $data['created_at']
            ),
            expiresAt: new DateTimeImmutable(
                $data['expires_at']
            ),
        );
    }
}
