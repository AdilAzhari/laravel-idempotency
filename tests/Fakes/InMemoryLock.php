<?php

declare(strict_types=1);

namespace AdilAzhari\LaravelIdempotency\Tests\Fakes;

use Illuminate\Contracts\Cache\Lock;

final class InMemoryLock implements Lock
{
    /**
     * @param  array<string, bool>  $locks
     */
    public function __construct(
        private readonly string $name,
        private array &$locks,
    ) {}

    public function get($callback = null)
    {
        if (isset($this->locks[$this->name])) {
            return false;
        }

        $this->locks[$this->name] = true;

        if ($callback !== null) {
            try {
                return $callback();
            } finally {
                $this->release();
            }
        }

        return true;
    }

    public function release(): bool
    {
        unset($this->locks[$this->name]);

        return true;
    }

    public function forceRelease(): void
    {
        $this->release();
    }

    public function owner(): ?string
    {
        return null;
    }

    public function block($seconds, $callback = null)
    {
        return $this->get($callback);
    }
}
