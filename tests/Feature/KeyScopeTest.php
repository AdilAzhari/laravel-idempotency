<?php

declare(strict_types=1);

use AdilAzhari\LaravelIdempotency\Contracts\IdempotencyScopeResolver;
use AdilAzhari\LaravelIdempotency\Http\Middleware\IdempotencyMiddleware;
use AdilAzhari\LaravelIdempotency\IdempotencyManager;
use Illuminate\Auth\GenericUser;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;

beforeEach(function (): void {
    $this->charges = 0;

    Route::post('/payments', function (Request $request): ResponseFactory|Response {
        $this->charges++;

        $id = $request->user()?->getAuthIdentifier();

        return response(is_int($id) ? 'charged '.$id : 'charged guest', 201);
    })->middleware(IdempotencyMiddleware::class);
});

it('scopes keys to the signed-in user by default', function (): void {
    $headers = ['Idempotency-Key' => 'payment-123'];

    $this->actingAs(new GenericUser(['id' => 1]))
        ->post('/payments', ['amount' => 100], $headers)
        ->assertCreated()
        ->assertContent('charged 1');

    $this->actingAs(new GenericUser(['id' => 2]))
        ->post('/payments', ['amount' => 100], $headers)
        ->assertCreated()
        ->assertContent('charged 2')
        ->assertHeader('Idempotency-Replayed', 'false');

    // And the first user's retry is still replayed.
    $this->actingAs(new GenericUser(['id' => 1]))
        ->post('/payments', ['amount' => 100], $headers)
        ->assertContent('charged 1')
        ->assertHeader('Idempotency-Replayed', 'true');

    expect($this->charges)->toBe(2);
});

it('shares keys across users when scoping is disabled', function (): void {
    config(['idempotency.scope' => null]);
    $headers = ['Idempotency-Key' => 'payment-123'];

    $this->actingAs(new GenericUser(['id' => 1]))->post('/payments', ['amount' => 100], $headers);

    $this->actingAs(new GenericUser(['id' => 2]))
        ->post('/payments', ['amount' => 100], $headers)
        ->assertContent('charged 1')
        ->assertHeader('Idempotency-Replayed', 'true');

    expect($this->charges)->toBe(1);
});

it('resolves a custom scope resolver class from config', function (): void {
    config(['idempotency.scope' => TenantHeaderScope::class]);
    $headers = ['Idempotency-Key' => 'payment-123'];

    $this->post('/payments', ['amount' => 100], $headers + ['X-Tenant' => 'acme'])->assertCreated();
    $this->post('/payments', ['amount' => 100], $headers + ['X-Tenant' => 'globex'])
        ->assertHeader('Idempotency-Replayed', 'false');

    expect($this->charges)->toBe(2);
});

it('rejects an unsupported scope value', function (): void {
    config(['idempotency.scope' => 'tenant']);

    expect(fn (): IdempotencyManager => app(IdempotencyManager::class))
        ->toThrow(InvalidArgumentException::class, 'Unsupported idempotency scope [tenant]');
});

final class TenantHeaderScope implements IdempotencyScopeResolver
{
    public function resolve(Request $request): ?string
    {
        $tenant = $request->header('X-Tenant');

        return is_string($tenant) ? 'tenant:'.$tenant : null;
    }
}
