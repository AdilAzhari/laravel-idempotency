<?php

declare(strict_types=1);

namespace AdilAzhari\LaravelIdempotency\Stores;

use AdilAzhari\LaravelIdempotency\Contracts\JobIdempotencyStore;
use AdilAzhari\LaravelIdempotency\Models\JobIdempotencyRecord as JobIdempotencyRecordModel;
use AdilAzhari\LaravelIdempotency\ValueObjects\JobIdempotencyRecord;
use Carbon\CarbonImmutable;

final class DatabaseJobIdempotencyStore implements JobIdempotencyStore
{
    public function find(string $key): ?JobIdempotencyRecord
    {
        $record = JobIdempotencyRecordModel::query()
            ->where('key', $key)
            ->where('expires_at', '>', now())
            ->first();

        if ($record === null) {
            return null;
        }

        return new JobIdempotencyRecord(
            key: $record->key,
            fingerprint: $record->fingerprint,
            jobClass: $record->job_class,
            createdAt: $record->created_at,
            expiresAt: $record->expires_at,
        );
    }

    public function store(JobIdempotencyRecord $record): void
    {
        JobIdempotencyRecordModel::query()
            ->updateOrCreate(
                [
                    'key' => $record->key,
                ],
                [
                    'fingerprint' => $record->fingerprint,
                    'job_class' => $record->jobClass,
                    'expires_at' => CarbonImmutable::instance(
                        $record->expiresAt
                    ),
                ]
            );
    }

    public function forget(string $key): void
    {
        JobIdempotencyRecordModel::query()
            ->where('key', $key)
            ->delete();
    }

    public function pruneExpired(): int
    {
        /** @var int $deleted */
        $deleted = JobIdempotencyRecordModel::query()
            ->where('expires_at', '<=', now())
            ->delete();

        return $deleted;
    }
}
