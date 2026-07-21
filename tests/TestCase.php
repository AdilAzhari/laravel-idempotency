<?php

declare(strict_types=1);

namespace AdilAzhari\LaravelIdempotency\Tests;

use AdilAzhari\LaravelIdempotency\LaravelIdempotencyServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            LaravelIdempotencyServiceProvider::class,
        ];
    }
}
