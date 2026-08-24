<?php

declare(strict_types=1);

namespace AdilAzhari\LaravelIdempotency\Support;

use AdilAzhari\LaravelIdempotency\Contracts\JobFingerprinter;
use JsonException;

final readonly class Sha256JobFingerprinter implements JobFingerprinter
{
    /**
     * @throws JsonException
     */
    public function fingerprint(object $job): string
    {
        return hash(
            'sha256',
            json_encode([
                'class' => $job::class,
                'payload' => get_object_vars($job),
            ], JSON_THROW_ON_ERROR)
        );
    }
}
