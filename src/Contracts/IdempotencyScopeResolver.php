<?php

declare(strict_types=1);

namespace AdilAzhari\LaravelIdempotency\Contracts;

use Illuminate\Http\Request;

/**
 * Decides whose namespace an idempotency key belongs to.
 *
 * Keys are chosen by clients, so two clients can pick the same one. Scoping keeps
 * one client's stored response from ever being replayed to another. Returning null
 * leaves the key unscoped.
 */
interface IdempotencyScopeResolver
{
    public function resolve(Request $request): ?string;
}
