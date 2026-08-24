<?php

declare(strict_types=1);

namespace AdilAzhari\LaravelIdempotency\Support;

final class IdempotencyContext
{
    private ?string $key = null;

    public function set(?string $key): void
    {
        $this->key = $key;
    }

    public function get(): ?string
    {
        return $this->key;
    }

    public static function current(): ?string
    {
        return app(self::class)->get();
    }
}
