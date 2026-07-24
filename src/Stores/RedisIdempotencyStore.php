<?php

declare(strict_types=1);

namespace AdilAzhari\LaravelIdempotency\Stores;

use AdilAzhari\LaravelIdempotency\Contracts\IdempotencyStore;
use AdilAzhari\LaravelIdempotency\ValueObjects\IdempotencyRecord;
use Illuminate\Contracts\Redis\Factory as RedisFactory;
use JsonException;

final readonly class RedisIdempotencyStore implements IdempotencyStore
{
    public function __construct(
        private RedisFactory $redis,
        private string $connection = 'default',
        private string $prefix = 'laravel-idempotency:',
    ) {}

    /**
     * @throws JsonException|\DateMalformedStringException
     */
    public function find(string $key): ?IdempotencyRecord
    {
        $value = $this->redis
            ->connection($this->connection)
            ->get($this->getKey($key));

        if (! is_string($value)) {
            return null;
        }

        /**
         * @var array{
         *     key: string,
         *     fingerprint: string,
         *     status: int,
         *     headers: array<string, list<string|null>>,
         *     body: string,
         *     created_at: string,
         *     expires_at: string
         * } $data
         */
        $data = json_decode(
            $value,
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        return IdempotencyRecord::fromArray($data);
    }

    /**
     * @throws JsonException
     */
    public function store(IdempotencyRecord $record): void
    {
        $ttl = $record->expiresAt->getTimestamp()
            - time();

        if ($ttl <= 0) {
            return;
        }

        $this->redis
            ->connection($this->connection)
            ->setex(
                $this->getKey($record->key),
                $ttl,
                json_encode(
                    $record->toArray(),
                    JSON_THROW_ON_ERROR
                )
            );
    }

    public function forget(string $key): void
    {
        $this->redis
            ->connection($this->connection)
            ->del(
                $this->getKey($key)
            );
    }

    private function getKey(string $key): string
    {
        return $this->prefix.$key;
    }
}
