<?php

declare(strict_types=1);

namespace AdilAzhari\LaravelIdempotency\Exceptions;

use Illuminate\Http\Request;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

final class IdempotencyLockConflictException extends RuntimeException
{
    public static function forKey(string $key): self
    {
        return new self(
            sprintf('A request with the idempotency key [%s] is already being processed.', $key)
        );
    }

    public function render(Request $request): Response
    {
        return response()->json([
            'message' => $this->getMessage(),
        ], Response::HTTP_CONFLICT);
    }
}
