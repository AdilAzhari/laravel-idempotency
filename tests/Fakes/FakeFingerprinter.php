<?php

declare(strict_types=1);

namespace AdilAzhari\LaravelIdempotency\Tests\Fakes;

use AdilAzhari\LaravelIdempotency\Contracts\RequestFingerprinter;
use Illuminate\Http\Request;

final readonly class FakeFingerprinter implements RequestFingerprinter
{
    public function __construct(
        private string $fingerprint,
    ) {}

    public function fingerprint(Request $request): string
    {
        return $this->fingerprint;
    }
}
