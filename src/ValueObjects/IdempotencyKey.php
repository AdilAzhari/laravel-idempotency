<?php

declare(strict_types=1);

namespace AdilAzhari\LaravelIdempotency\ValueObjects;

use InvalidArgumentException;
use Stringable;

final readonly class IdempotencyKey implements Stringable
{
    public function __construct(
        public string $value,
    ) {
        if ($this->value === '') {
            throw new InvalidArgumentException(
                'Idempotency key cannot be empty.'
            );
        }
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
