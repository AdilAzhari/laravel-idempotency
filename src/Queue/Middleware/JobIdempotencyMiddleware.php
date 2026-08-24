<?php

declare(strict_types=1);

namespace AdilAzhari\LaravelIdempotency\Queue\Middleware;

use AdilAzhari\LaravelIdempotency\JobIdempotencyManager;
use Closure;

final readonly class JobIdempotencyMiddleware
{
    public function __construct(
        private string $key,
    ) {}

    public function handle(
        object $job,
        Closure $next
    ): void {
        app(JobIdempotencyManager::class)->handle(
            $job,
            $this->key,
            $next,
        );
    }
}
