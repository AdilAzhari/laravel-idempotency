<?php

declare(strict_types=1);

namespace AdilAzhari\LaravelIdempotency\ValueObjects;

use DateMalformedStringException;
use DateTimeImmutable;

/**
 * @phpstan-type JobIdempotencyRecordData array{
 *     key: string,
 *     fingerprint: string,
 *     job_class: string,
 *     created_at: string,
 *     expires_at: string
 * }
 */
final readonly class JobIdempotencyRecord
{
    public function __construct(
        public string $key,
        public string $fingerprint,
        public string $jobClass,
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
     *     job_class: string,
     *     created_at: string,
     *     expires_at: string
     * }
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'fingerprint' => $this->fingerprint,
            'job_class' => $this->jobClass,
            'created_at' => $this->createdAt->format(DATE_ATOM),
            'expires_at' => $this->expiresAt->format(DATE_ATOM),
        ];
    }

    /**
     * @param  JobIdempotencyRecordData  $data
     *
     * @throws DateMalformedStringException
     */
    public static function fromArray(array $data): self
    {
        return new self(
            key: $data['key'],
            fingerprint: $data['fingerprint'],
            jobClass: $data['job_class'],
            createdAt: new DateTimeImmutable(
                $data['created_at']
            ),
            expiresAt: new DateTimeImmutable(
                $data['expires_at']
            ),
        );
    }
}
