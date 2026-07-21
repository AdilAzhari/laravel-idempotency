<?php

declare(strict_types=1);

use AdilAzhari\LaravelIdempotency\Contracts\RequestFingerprinter;
use AdilAzhari\LaravelIdempotency\Support\Sha256RequestFingerprinter;

it('registers package bindings', function (): void {
    expect(app(RequestFingerprinter::class))
        ->toBeInstanceOf(Sha256RequestFingerprinter::class);
});
