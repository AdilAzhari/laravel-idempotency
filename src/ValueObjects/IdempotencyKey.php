<?php

declare(strict_types=1);

namespace AdilAzhari\LaravelIdempotency\ValueObjects;

use AdilAzhari\LaravelIdempotency\Exceptions\InvalidIdempotencyKeyException;
use InvalidArgumentException;
use Stringable;

final readonly class IdempotencyKey implements Stringable
{
    public const int DEFAULT_MAX_LENGTH = 255;

    public function __construct(
        public string $value,
        int $maxLength = self::DEFAULT_MAX_LENGTH,
    ) {
        if ($this->value === '') {
            throw new InvalidArgumentException(
                'Idempotency key cannot be empty.'
            );
        }

        if (mb_strlen($this->value) > $maxLength) {
            throw InvalidIdempotencyKeyException::tooLong($this->value, $maxLength);
        }
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
