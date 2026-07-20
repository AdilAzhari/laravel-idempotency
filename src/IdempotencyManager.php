<?php

declare(strict_types=1);

namespace AdilAzhari\LaravelIdempotency;

use AdilAzhari\LaravelIdempotency\Contracts\IdempotencyStore;
use AdilAzhari\LaravelIdempotency\ValueObjects\StoredResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class IdempotencyManager
{
    public function __construct(
        private IdempotencyStore $store,
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

        $stored = $this->store->get($key);

        if ($stored instanceof StoredResponse) {
            return response(
                $stored->body,
                $stored->status,
                $stored->headers
            );
        }

        $response = $next($request);

        $this->store->put(
            $key,
            new StoredResponse(
                $response->getStatusCode(),
                $response->headers->all(),
                $response->getContent() ?: ''
            )
        );

        return $response;
    }
}
