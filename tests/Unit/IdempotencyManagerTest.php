<?php

declare(strict_types=1);
use AdilAzhari\LaravelIdempotency\IdempotencyManager;
use AdilAzhari\LaravelIdempotency\Support\Sha256RequestFingerprinter;
use AdilAzhari\LaravelIdempotency\Tests\Fakes\InMemoryIdempotencyStore;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

it('passes request when idempotency key is missing', function (): void {
    $manager = new IdempotencyManager(
        new InMemoryIdempotencyStore,
        new Sha256RequestFingerprinter,
    );

    $request = Request::create('/payments', 'POST');

    $called = false;

    $response = $manager->handle(
        $request,
        function () use (&$called): Response {
            $called = true;

            return new Response('created', 201);
        }
    );

    expect($called)->toBeTrue()
        ->and($response->getStatusCode())
        ->toBe(201);
});
