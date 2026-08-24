<?php

declare(strict_types=1);

namespace AdilAzhari\LaravelIdempotency\Stores;

use AdilAzhari\LaravelIdempotency\Contracts\JobIdempotencyStore;
use AdilAzhari\LaravelIdempotency\ValueObjects\JobIdempotencyRecord;
use Illuminate\Contracts\Redis\Factory as RedisFactory;
use JsonException;

final readonly class RedisJobIdempotencyStore implements JobIdempotencyStore
{
    public function __construct(
        private RedisFactory $redis,
        private string $connection = 'default',
        private string $prefix = 'laravel-job-idempotency:',
    ) {}

    /**
     * @throws JsonException|\DateMalformedStringException
     */
    public function find(string $key): ?JobIdempotencyRecord
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
         *     job_class: string,
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

        return JobIdempotencyRecord::fromArray($data);
    }

    /**
     * @throws JsonException
     */
    public function store(JobIdempotencyRecord $record): void
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
