<?php

declare(strict_types=1);

namespace AdilAzhari\LaravelIdempotency\Tests\Support;

use AdilAzhari\LaravelIdempotency\ValueObjects\IdempotencyRecord;
use DateTimeImmutable;

final class RecordFactory
{
    public static function make(
        string $key = 'test-key'
    ): IdempotencyRecord {
        return new IdempotencyRecord(
            key: $key,
            fingerprint: 'fingerprint',
            status: 200,
            headers: [
                'Content-Type' => [
                    'application/json',
                ],
            ],
            body: '{"success":true}',
            createdAt: new DateTimeImmutable,
            expiresAt: new DateTimeImmutable('+1 day'),
        );
    }
}
