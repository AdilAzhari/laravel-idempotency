<?php

declare(strict_types=1);

namespace AdilAzhari\LaravelIdempotency\Exceptions;

use RuntimeException;

final class JobIdempotencyLockConflictException extends RuntimeException
{
    public static function forKey(string $key): self
    {
        return new self(
            sprintf('A job with the idempotency key [%s] is already being processed.', $key)
        );
    }
}
