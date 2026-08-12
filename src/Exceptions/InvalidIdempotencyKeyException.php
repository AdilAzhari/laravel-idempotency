<?php

declare(strict_types=1);

namespace AdilAzhari\LaravelIdempotency\Exceptions;

use Illuminate\Http\Request;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;

final class InvalidIdempotencyKeyException extends InvalidArgumentException
{
    public static function tooLong(string $key, int $maxLength): self
    {
        return new self(
            sprintf(
                'Idempotency key must not exceed %d characters, [%d] given.',
                $maxLength,
                mb_strlen($key),
            )
        );
    }

    public function render(Request $request): Response
    {
        return response()->json([
            'message' => $this->getMessage(),
        ], Response::HTTP_BAD_REQUEST);
    }
}
