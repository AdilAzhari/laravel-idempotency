<?php

declare(strict_types=1);

namespace AdilAzhari\LaravelIdempotency\Tests\Fakes;

use AdilAzhari\LaravelIdempotency\Queue\Middleware\JobIdempotencyMiddleware;
use AdilAzhari\LaravelIdempotency\Support\IdempotencyContext;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class IdempotentTestJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * @var array<int, int>
     */
    public static array $handled = [];

    public function __construct(
        public int $amount,
        public ?string $key = null,
        public bool $useContextKey = false,
    ) {}

    /**
     * @return array<int, JobIdempotencyMiddleware>
     */
    public function middleware(): array
    {
        $key = $this->useContextKey
            ? (IdempotencyContext::current() ?? 'missing-context-key')
            : ($this->key ?? 'default-key');

        return [new JobIdempotencyMiddleware(key: $key)];
    }

    public function handle(): void
    {
        self::$handled[] = $this->amount;
    }
}
