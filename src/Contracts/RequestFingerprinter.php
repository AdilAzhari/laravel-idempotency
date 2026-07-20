<?php

declare(strict_types=1);

namespace AdilAzhari\LaravelIdempotency\Contracts;

use Illuminate\Http\Request;

interface RequestFingerprinter
{
    public function fingerprint(Request $request): string;
}
