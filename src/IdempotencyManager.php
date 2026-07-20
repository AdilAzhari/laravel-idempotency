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

    public function handle(
        Request $request,
        Closure $next
    ): Response {

        $key = $request->header('Idempotency-Key');

        if ($key === null) {
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
                $response->status(),
                $response->headers->all(),
                $response->getContent()
            )
        );

        return $response;
    }
}
