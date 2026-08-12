<?php

declare(strict_types=1);

namespace AdilAzhari\LaravelIdempotency;

use AdilAzhari\LaravelIdempotency\Contracts\IdempotencyLock;
use AdilAzhari\LaravelIdempotency\Contracts\IdempotencyStore;
use AdilAzhari\LaravelIdempotency\Contracts\RequestFingerprinter;
use AdilAzhari\LaravelIdempotency\Exceptions\IdempotencyConflictException;
use AdilAzhari\LaravelIdempotency\Exceptions\IdempotencyLockConflictException;
use AdilAzhari\LaravelIdempotency\ValueObjects\IdempotencyKey;
use AdilAzhari\LaravelIdempotency\ValueObjects\IdempotencyRecord;
use Closure;
use DateInterval;
use DateTimeImmutable;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class IdempotencyManager
{
    /**
     * @param  list<string>  $methods  HTTP methods the middleware applies to. Empty means all methods.
     */
    public function __construct(
        private IdempotencyStore $store,
        private RequestFingerprinter $fingerprinter,
        private IdempotencyLock $lock,
        private string $header = 'Idempotency-Key',
        private int $expiration = 86400,
        private array $methods = [],
        private int $maxKeyLength = IdempotencyKey::DEFAULT_MAX_LENGTH,
        private ?string $replayHeader = 'Idempotency-Replayed',
    ) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(
        Request $request,
        Closure $next
    ): Response {
        $key = $request->header($this->header);

        if (! is_string($key) || $key === '') {
            return $next($request);
        }

        if ($this->methods !== [] && ! in_array($request->method(), $this->methods, true)) {
            return $next($request);
        }

        $idempotencyKey = new IdempotencyKey($key, $this->maxKeyLength);

        $fingerprint = $this->fingerprinter->fingerprint($request);

        if (! $this->lock->acquire($idempotencyKey->value)) {
            throw IdempotencyLockConflictException::forKey($idempotencyKey->value);
        }

        try {
            /**
             * Check again after acquiring lock.
             *
             * Another request might have completed while
             * this request was waiting.
             */
            $stored = $this->store->find($idempotencyKey->value);

            if ($stored?->isExpired()) {
                $this->store->forget($idempotencyKey->value);
                $stored = null;
            }

            if ($stored instanceof IdempotencyRecord) {

                if ($stored->fingerprint !== $fingerprint) {
                    throw IdempotencyConflictException::forKey($idempotencyKey->value);
                }

                $replayed = new Response(
                    $stored->body,
                    $stored->status,
                    $stored->headers
                );

                if ($this->replayHeader !== null) {
                    $replayed->headers->set($this->replayHeader, 'true');
                }

                return $replayed;
            }

            $response = $next($request);

            if ($this->replayHeader !== null) {
                $response->headers->set($this->replayHeader, 'false');
            }

            $createdAt = new DateTimeImmutable;

            $record = new IdempotencyRecord(
                key: $idempotencyKey->value,
                fingerprint: $fingerprint,
                status: $response->getStatusCode(),
                headers: $response->headers->all(),
                body: $response->getContent() ?: '',
                createdAt: $createdAt,
                expiresAt: $createdAt->add(
                    DateInterval::createFromDateString($this->expiration.' seconds')
                ),
            );

            $this->store->store(
                record: $record,
            );

            return $response;

        } finally {

            $this->lock->release($idempotencyKey->value);

        }
    }
}
