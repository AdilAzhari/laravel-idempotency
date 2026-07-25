<?php

declare(strict_types=1);

namespace AdilAzhari\LaravelIdempotency\Stores;

use AdilAzhari\LaravelIdempotency\Contracts\IdempotencyStore;
use AdilAzhari\LaravelIdempotency\Models\IdempotencyRecord as IdempotencyRecordModel;
use AdilAzhari\LaravelIdempotency\ValueObjects\IdempotencyRecord;
use Carbon\CarbonImmutable;

final class DatabaseIdempotencyStore implements IdempotencyStore
{
    public function find(string $key): ?IdempotencyRecord
    {
        $record = IdempotencyRecordModel::query()
            ->where('key', $key)
            ->where('expires_at', '>', now())
            ->first();

        if ($record === null) {
            return null;
        }

        return new IdempotencyRecord(
            key: $record->key,
            fingerprint: $record->fingerprint,
            status: $record->status,
            headers: $record->headers,
            body: $record->body,
            createdAt: $record->created_at,
            expiresAt: $record->expires_at,
        );
    }

    public function store(IdempotencyRecord $record): void
    {
        IdempotencyRecordModel::query()
            ->updateOrCreate(
                [
                    'key' => $record->key,
                ],
                [
                    'fingerprint' => $record->fingerprint,
                    'status' => $record->status,
                    'headers' => $record->headers,
                    'body' => $record->body,
                    'expires_at' => CarbonImmutable::instance(
                        $record->expiresAt
                    ),
                ]
            );
    }

    public function forget(string $key): void
    {
        IdempotencyRecordModel::query()
            ->where('key', $key)
            ->delete();
    }

    public function pruneExpired(): int
    {
        return IdempotencyRecordModel::query()
            ->where('expires_at', '<=', now())
            ->delete();
    }
}
