<?php

declare(strict_types=1);

namespace AdilAzhari\LaravelIdempotency\Support;

use AdilAzhari\LaravelIdempotency\Contracts\RequestFingerprinter;
use Illuminate\Http\Request;
use JsonException;

final readonly class Sha256RequestFingerprinter implements RequestFingerprinter
{
    /**
     * @throws JsonException
     */
    public function fingerprint(Request $request): string
    {
        return hash(
            'sha256',
            json_encode([
                'method' => $request->method(),
                'uri' => $request->path(),
                'query' => $request->query(),
                'body' => $request->all(),
            ], JSON_THROW_ON_ERROR)
        );
    }
}
