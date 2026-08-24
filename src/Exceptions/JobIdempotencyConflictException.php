<?php

declare(strict_types=1);

namespace AdilAzhari\LaravelIdempotency\Exceptions;

use RuntimeException;

final class JobIdempotencyConflictException extends RuntimeException
{
    public static function forKey(string $key): self
    {
        return new self(
            sprintf('The idempotency key [%s] has already been used for a different job.', $key)
        );
    }
}
