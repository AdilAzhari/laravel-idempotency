<?php

declare(strict_types=1);

namespace AdilAzhari\LaravelIdempotency\Contracts;

interface JobFingerprinter
{
    public function fingerprint(object $job): string;
}
