<?php

declare(strict_types=1);

namespace AdilAzhari\LaravelIdempotency\Support;

use AdilAzhari\LaravelIdempotency\Contracts\IdempotencyScopeResolver;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;

/**
 * Scopes idempotency keys to the authenticated user.
 *
 * The user's class is part of the scope, so different authenticatable models that
 * share an id (a User and an Admin both with id 1) never share keys. Unauthenticated
 * requests stay unscoped.
 */
final readonly class AuthenticatedUserScopeResolver implements IdempotencyScopeResolver
{
    public function resolve(Request $request): ?string
    {
        $user = $request->user();

        if (! $user instanceof Authenticatable) {
            return null;
        }

        $identifier = $user->getAuthIdentifier();

        if (! is_scalar($identifier)) {
            return null;
        }

        return $user::class.':'.$identifier;
    }
}
