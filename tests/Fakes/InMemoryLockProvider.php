<?php

declare(strict_types=1);

namespace AdilAzhari\LaravelIdempotency\Tests\Fakes;

use Illuminate\Contracts\Cache\Lock;
use Illuminate\Contracts\Cache\LockProvider;

final class InMemoryLockProvider implements LockProvider
{
    /**
     * @var array<string, bool>
     */
    private array $locks = [];

    public function lock($name, $seconds = 0, $owner = null): Lock
    {
        return new InMemoryLock(
            $name,
            $this->locks
        );
    }

    public function restoreLock(
        $name,
        $owner
    ): Lock {
        return new InMemoryLock(
            $name,
            $this->locks
        );
    }
}
