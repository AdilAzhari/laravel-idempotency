<?php

declare(strict_types=1);

use AdilAzhari\LaravelIdempotency\Support\IdempotencyContext;

it('returns null when no key has been set', function (): void {
    $context = new IdempotencyContext;

    expect($context->get())->toBeNull();
});

it('returns the key that was set', function (): void {
    $context = new IdempotencyContext;
    $context->set('payment-123');

    expect($context->get())->toBe('payment-123');
});

it('resolves the current key through the container via the static accessor', function (): void {
    app(IdempotencyContext::class)->set('payment-456');

    expect(IdempotencyContext::current())->toBe('payment-456');
});
