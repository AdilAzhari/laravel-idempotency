<?php

declare(strict_types=1);

namespace AdilAzhari\LaravelIdempotency\Http\Middleware;

use AdilAzhari\LaravelIdempotency\IdempotencyManager;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class IdempotencyMiddleware
{
    public function __construct(
        private IdempotencyManager $manager
    ) {}

    public function handle(
        Request $request,
        Closure $next
    ): Response {
        return $this->manager->handle(
            $request,
            $next
        );
    }
}
