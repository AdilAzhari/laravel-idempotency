<?php

declare(strict_types=1);

namespace AdilAzhari\LaravelIdempotency\Tests\Fakes;

final class FakeJob
{
    public function __construct(
        public int $amount = 100,
    ) {}
}
