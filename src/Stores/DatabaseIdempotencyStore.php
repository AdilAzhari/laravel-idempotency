<?php

namespace AdilAzhari\LaravelIdempotency\Stores;


use AdilAzhari\LaravelIdempotency\Contracts\IdempotencyStore;
use AdilAzhari\LaravelIdempotency\Models\IdempotencyRecord;

class DatabaseStore implements IdempotencyStore
{

    public function find(string $key): ?\AdilAzhari\LaravelIdempotency\ValueObjects\IdempotencyRecord
    {
        $record = IdempotencyRecord::query()
            ->where('key', $key)
            ->where('expires_at', '>', now())
            ->first();


        if (! $record) {
            return null;
        }


        return new \AdilAzhari\LaravelIdempotency\ValueObjects\IdempotencyRecord(
            key: $record->getKey(),
            fingerprint: $record->fingerprint,
            status: $record->status,
            headers: $record->headers,
            body: $record->body,
            createdAt: $record->createdAt,
            expiresAt: $record->expires_at,
        );
    }


    public function store(
        \AdilAzhari\LaravelIdempotency\ValueObjects\IdempotencyRecord $record
    ): void {

        IdempotencyRecord::query()->updateOrCreate(
            [
                'key' => $record->key,
            ],
            [
                'fingerprint' => $record->fingerprint,
                'status' => $record->status,
                'headers' => $record->headers,
                'body' => $record->body,
                'expires_at' => now()->addSeconds($record->expiresAt),
            ]
        );

    }


    public function forget(string $key): void
    {
        IdempotencyRecord::query()->where('key', $key)->delete();
    }
}
