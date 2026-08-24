<?php

declare(strict_types=1);

namespace AdilAzhari\LaravelIdempotency\Tests\Support;

use AdilAzhari\LaravelIdempotency\ValueObjects\JobIdempotencyRecord;
use DateTimeImmutable;

final class JobRecordFactory
{
    public static function make(
        string $key = 'test-key'
    ): JobIdempotencyRecord {
        return new JobIdempotencyRecord(
            key: $key,
            fingerprint: 'fingerprint',
            jobClass: 'App\\Jobs\\ChargeCustomer',
            createdAt: new DateTimeImmutable,
            expiresAt: new DateTimeImmutable('+1 day'),
        );
    }
}
