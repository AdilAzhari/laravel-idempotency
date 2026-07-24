<?php

declare(strict_types=1);

namespace AdilAzhari\LaravelIdempotency\Tests\Support;

final class FakeRedisConnection
{
    /**
     * @var array<string, string>
     */
    private array $records = [];

    /**
     * @var array<string, int>
     */
    private array $ttl = [];

    public function get(string $key): ?string
    {
        return $this->records[$key] ?? null;
    }

    public function setex(
        string $key,
        int $seconds,
        string $value
    ): void {
        $this->records[$key] = $value;
        $this->ttl[$key] = $seconds;
    }

    public function del(string $key): void
    {
        unset(
            $this->records[$key],
            $this->ttl[$key]
        );
    }

    public function exists(string $key): bool
    {
        return isset($this->records[$key]);
    }

    public function ttl(string $key): ?int
    {
        return $this->ttl[$key] ?? null;
    }
}
