<?php

declare(strict_types=1);

use AdilAzhari\LaravelIdempotency\Contracts\IdempotencyScopeResolver;
use AdilAzhari\LaravelIdempotency\IdempotencyManager;
use AdilAzhari\LaravelIdempotency\Support\AuthenticatedUserScopeResolver;
use AdilAzhari\LaravelIdempotency\Support\Sha256RequestFingerprinter;
use AdilAzhari\LaravelIdempotency\Tests\Fakes\InMemoryIdempotencyLock;
use AdilAzhari\LaravelIdempotency\Tests\Fakes\InMemoryIdempotencyStore;
use Illuminate\Auth\GenericUser;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Builds a POST /payments request with a fixed body and key, optionally as a signed-in user.
 */
function paymentRequest(?int $userId, string $key = 'payment-123'): Request
{
    $request = Request::create('/payments', 'POST', ['amount' => 100]);
    $request->headers->set('Idempotency-Key', $key);

    if ($userId !== null) {
        $user = new GenericUser(['id' => $userId]);
        $request->setUserResolver(static fn (): GenericUser => $user);
    }

    return $request;
}

function scopedManager(?IdempotencyScopeResolver $scope = new AuthenticatedUserScopeResolver): IdempotencyManager
{
    return new IdempotencyManager(
        new InMemoryIdempotencyStore,
        new Sha256RequestFingerprinter,
        new InMemoryIdempotencyLock,
        scope: $scope,
    );
}

it("does not replay one user's response to another user with the same key and body", function (): void {
    $manager = scopedManager();

    $first = $manager->handle(paymentRequest(1), fn (): Response => new Response('charged for user 1'));
    $second = $manager->handle(paymentRequest(2), fn (): Response => new Response('charged for user 2'));

    expect($first->getContent())->toBe('charged for user 1')
        ->and($second->getContent())->toBe('charged for user 2')
        ->and($second->headers->get('Idempotency-Replayed'))->toBe('false');
});

it('still replays a retry from the same user', function (): void {
    $manager = scopedManager();
    $executions = 0;
    $next = function () use (&$executions): Response {
        $executions++;

        return new Response('charged');
    };

    $manager->handle(paymentRequest(1), $next);
    $retry = $manager->handle(paymentRequest(1), $next);

    expect($executions)->toBe(1)
        ->and($retry->getContent())->toBe('charged')
        ->and($retry->headers->get('Idempotency-Replayed'))->toBe('true');
});

it('keeps unauthenticated requests unscoped', function (): void {
    $store = new InMemoryIdempotencyStore;
    $manager = new IdempotencyManager(
        $store,
        new Sha256RequestFingerprinter,
        new InMemoryIdempotencyLock,
        scope: new AuthenticatedUserScopeResolver,
    );

    $manager->handle(paymentRequest(null), fn (): Response => new Response('charged'));

    // Stored under the raw key, exactly as before scoping existed.
    expect($store->find('payment-123'))->not->toBeNull();

    $retry = $manager->handle(paymentRequest(null), fn (): Response => new Response('charged twice'));
    expect($retry->getContent())->toBe('charged');
});

it('stores scoped records under a fixed-length key that fits the column', function (): void {
    $store = new InMemoryIdempotencyStore;
    $manager = new IdempotencyManager(
        $store,
        new Sha256RequestFingerprinter,
        new InMemoryIdempotencyLock,
        scope: new AuthenticatedUserScopeResolver,
    );

    $longKey = str_repeat('k', 255);
    $manager->handle(paymentRequest(1, $longKey), fn (): Response => new Response('charged'));

    expect($store->find($longKey))->toBeNull();

    $keys = $store->keys();
    expect($keys)->toHaveCount(1)
        ->and($keys[0])->toMatch('/^scoped:[0-9a-f]{64}$/');
});

it('locks per user, so the same key from two users does not conflict', function (): void {
    $lock = new InMemoryIdempotencyLock;
    $manager = new IdempotencyManager(
        new InMemoryIdempotencyStore,
        new Sha256RequestFingerprinter,
        $lock,
        scope: new AuthenticatedUserScopeResolver,
    );

    // User 1's request is still running (its lock is held) when user 2 sends the same key.
    $response = $manager->handle(paymentRequest(1), fn (): Response => $manager->handle(
        paymentRequest(2),
        fn (): Response => new Response('user 2 charged'),
    ));

    expect($response->getContent())->toBe('user 2 charged');
});

it('uses a custom scope resolver', function (): void {
    $tenantScope = new class implements IdempotencyScopeResolver
    {
        public function resolve(Request $request): ?string
        {
            $tenant = $request->header('X-Tenant');

            return is_string($tenant) ? 'tenant:'.$tenant : null;
        }
    };
    $manager = scopedManager($tenantScope);

    $acme = paymentRequest(null);
    $acme->headers->set('X-Tenant', 'acme');

    $globex = paymentRequest(null);
    $globex->headers->set('X-Tenant', 'globex');

    $manager->handle($acme, fn (): Response => new Response('acme'));
    $second = $manager->handle($globex, fn (): Response => new Response('globex'));

    expect($second->getContent())->toBe('globex');
});

it('can be disabled, replaying across users like before', function (): void {
    $manager = scopedManager(null);

    $manager->handle(paymentRequest(1), fn (): Response => new Response('user 1'));

    $second = $manager->handle(paymentRequest(2), fn (): Response => new Response('user 2'));

    expect($second->getContent())->toBe('user 1');
});

it('distinguishes users of different model classes with the same id', function (): void {
    $resolver = new AuthenticatedUserScopeResolver;

    $user = new GenericUser(['id' => 1]);
    $admin = new class(['id' => 1]) extends GenericUser {};

    $asUser = paymentRequest(null);
    $asUser->setUserResolver(static fn (): GenericUser => $user);

    $asAdmin = paymentRequest(null);
    $asAdmin->setUserResolver(static fn (): GenericUser => $admin);

    expect($resolver->resolve($asUser))->not->toBe($resolver->resolve($asAdmin))
        ->and($resolver->resolve(paymentRequest(null)))->toBeNull();
});
