<?php

declare(strict_types=1);

namespace AdilAzhari\LaravelIdempotency;

use AdilAzhari\LaravelIdempotency\Contracts\IdempotencyLock;
use AdilAzhari\LaravelIdempotency\Contracts\IdempotencyScopeResolver;
use AdilAzhari\LaravelIdempotency\Contracts\IdempotencyStore;
use AdilAzhari\LaravelIdempotency\Contracts\RequestFingerprinter;
use AdilAzhari\LaravelIdempotency\Exceptions\IdempotencyConflictException;
use AdilAzhari\LaravelIdempotency\Exceptions\IdempotencyLockConflictException;
use AdilAzhari\LaravelIdempotency\Support\IdempotencyContext;
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
        private IdempotencyContext $context = new IdempotencyContext,
        private string $header = 'Idempotency-Key',
        private int $expiration = 86400,
        private array $methods = [],
        private int $maxKeyLength = IdempotencyKey::DEFAULT_MAX_LENGTH,
        private ?string $replayHeader = 'Idempotency-Replayed',
        private ?IdempotencyScopeResolver $scope = null,
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

        $this->context->set($idempotencyKey->value);

        $fingerprint = $this->fingerprinter->fingerprint($request);

        $storageKey = $this->storageKey($request, $idempotencyKey);

        if (! $this->lock->acquire($storageKey)) {
            throw IdempotencyLockConflictException::forKey($idempotencyKey->value);
        }

        try {
            /**
             * Check again after acquiring lock.
             *
             * Another request might have completed while
             * this request was waiting.
             */
            $stored = $this->store->find($storageKey);

            if ($stored?->isExpired()) {
                $this->store->forget($storageKey);
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
                key: $storageKey,
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

            $this->lock->release($storageKey);

        }
    }

    /**
     * The key used for locking and storage.
     *
     * Scoped keys are hashed together with their scope into a fixed-length key, so they
     * always fit the store's key column however long the client's key is. Unscoped keys
     * are stored as-is, exactly as before scoping existed.
     */
    private function storageKey(Request $request, IdempotencyKey $key): string
    {
        $scope = $this->scope?->resolve($request);

        if ($scope === null || $scope === '') {
            return $key->value;
        }

        return 'scoped:'.hash('sha256', $scope."\0".$key->value);
    }
}
