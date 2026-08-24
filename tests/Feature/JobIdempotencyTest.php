<?php

declare(strict_types=1);

use AdilAzhari\LaravelIdempotency\Exceptions\JobIdempotencyConflictException;
use AdilAzhari\LaravelIdempotency\Tests\Fakes\IdempotentTestJob;
use Illuminate\Support\Facades\Bus;

beforeEach(function (): void {
    IdempotentTestJob::$handled = [];
});

it('only executes a job once for repeated dispatches with the same key and payload', function (): void {
    Bus::dispatchSync(new IdempotentTestJob(amount: 100, key: 'order-1'));
    Bus::dispatchSync(new IdempotentTestJob(amount: 100, key: 'order-1'));

    expect(IdempotentTestJob::$handled)->toBe([100]);
});

it('executes independently dispatched jobs with different keys', function (): void {
    Bus::dispatchSync(new IdempotentTestJob(amount: 100, key: 'order-1'));
    Bus::dispatchSync(new IdempotentTestJob(amount: 200, key: 'order-2'));

    expect(IdempotentTestJob::$handled)->toBe([100, 200]);
});

it('throws a conflict exception when the same key is reused with a different payload', function (): void {
    Bus::dispatchSync(new IdempotentTestJob(amount: 100, key: 'order-1'));

    expect(fn () => Bus::dispatchSync(new IdempotentTestJob(amount: 500, key: 'order-1')))
        ->toThrow(JobIdempotencyConflictException::class)
        ->and(IdempotentTestJob::$handled)->toBe([100]);
});
