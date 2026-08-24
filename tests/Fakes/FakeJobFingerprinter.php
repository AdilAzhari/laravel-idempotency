<?php

declare(strict_types=1);

namespace AdilAzhari\LaravelIdempotency\Tests\Fakes;

use AdilAzhari\LaravelIdempotency\Contracts\JobFingerprinter;

final readonly class FakeJobFingerprinter implements JobFingerprinter
{
    public function __construct(
        private string $fingerprint,
    ) {}

    public function fingerprint(object $job): string
    {
        return $this->fingerprint;
    }
}
