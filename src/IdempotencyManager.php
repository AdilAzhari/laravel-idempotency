<?php

declare(strict_types=1);

namespace AdilAzhari\LaravelIdempotency;

use AdilAzhari\LaravelIdempotency\Contracts\IdempotencyStore;
use AdilAzhari\LaravelIdempotency\Contracts\RequestFingerprinter;
use AdilAzhari\LaravelIdempotency\ValueObjects\IdempotencyRecord;
use Closure;
use DateTimeImmutable;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class IdempotencyManager
{
    public function __construct(
        private IdempotencyStore $store,
        private RequestFingerprinter $fingerprinter,
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

        $stored = $this->store->find($key);

        if ($stored instanceof IdempotencyRecord) {
            return response(
                $stored->body,
                $stored->status,
                $stored->headers
            );
        }

        $fingerprint = $this->fingerprinter->fingerprint($request);

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
    }
}
