<?php

declare(strict_types=1);

use AdilAzhari\LaravelIdempotency\Support\Sha256JobFingerprinter;
use AdilAzhari\LaravelIdempotency\Tests\Fakes\FakeJob;

it('produces the same fingerprint for jobs with identical payloads', function (): void {
    $fingerprinter = new Sha256JobFingerprinter;

    expect($fingerprinter->fingerprint(new FakeJob(100)))
        ->toBe($fingerprinter->fingerprint(new FakeJob(100)));
});

it('produces a different fingerprint for jobs with different payloads', function (): void {
    $fingerprinter = new Sha256JobFingerprinter;

    expect($fingerprinter->fingerprint(new FakeJob(100)))
        ->not->toBe($fingerprinter->fingerprint(new FakeJob(500)));
});
