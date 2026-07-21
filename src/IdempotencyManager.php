<?php

declare(strict_types=1);

namespace AdilAzhari\LaravelIdempotency;

use AdilAzhari\LaravelIdempotency\Contracts\IdempotencyLock;
use AdilAzhari\LaravelIdempotency\Contracts\IdempotencyStore;
use AdilAzhari\LaravelIdempotency\Contracts\RequestFingerprinter;
use AdilAzhari\LaravelIdempotency\Exceptions\IdempotencyConflictException;
use AdilAzhari\LaravelIdempotency\ValueObjects\IdempotencyRecord;
use Closure;
use DateTimeImmutable;
use Illuminate\Http\Request;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

final readonly class IdempotencyManager
{
    public function __construct(
        private IdempotencyStore $store,
        private RequestFingerprinter $fingerprinter,
        private IdempotencyLock $lock,
    ) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(
        Request $request,
        Closure $next
    ): Response {
        $key = $request->header('Idempotency-Key');

        if (! is_string($key)) {
            return $next($request);
        }

        $fingerprint = $this->fingerprinter->fingerprint($request);

        if (! $this->lock->acquire($key)) {
            throw new RuntimeException(
                'Unable to acquire idempotency lock.'
            );
        }

        try {
            /**
             * Check again after acquiring lock.
             *
             * Another request might have completed while
             * this request was waiting.
             */
            $stored = $this->store->find($key);

            if ($stored instanceof IdempotencyRecord) {

                if ($stored->fingerprint !== $fingerprint) {
                    throw IdempotencyConflictException::forKey($key);
                }

                return response(
                    $stored->body,
                    $stored->status,
                    $stored->headers
                );
            }

            $response = $next($request);

            $this->store->save(
                new IdempotencyRecord(
                    key: $key,
                    fingerprint: $fingerprint,
                    status: $response->getStatusCode(),
                    headers: $response->headers->all(),
                    body: $response->getContent() ?: '',
                    createdAt: new DateTimeImmutable,
                    expiresAt: new DateTimeImmutable('+24 hours'),
                )
            );

            return $response;

        } finally {

            $this->lock->release($key);

        }
    }
}
