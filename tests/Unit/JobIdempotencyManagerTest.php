<?php

declare(strict_types=1);

use AdilAzhari\LaravelIdempotency\Exceptions\JobIdempotencyConflictException;
use AdilAzhari\LaravelIdempotency\Exceptions\JobIdempotencyLockConflictException;
use AdilAzhari\LaravelIdempotency\JobIdempotencyManager;
use AdilAzhari\LaravelIdempotency\Support\Sha256JobFingerprinter;
use AdilAzhari\LaravelIdempotency\Tests\Fakes\FakeJob;
use AdilAzhari\LaravelIdempotency\Tests\Fakes\InMemoryIdempotencyLock;
use AdilAzhari\LaravelIdempotency\Tests\Fakes\InMemoryJobIdempotencyStore;
use AdilAzhari\LaravelIdempotency\ValueObjects\JobIdempotencyRecord;

it('executes and stores a record on first run', function (): void {
    $manager = new JobIdempotencyManager(
        new InMemoryJobIdempotencyStore,
        new Sha256JobFingerprinter,
        new InMemoryIdempotencyLock,
    );

    $handled = 0;

    $manager->handle(new FakeJob(100), 'job-123', function () use (&$handled): void {
        $handled++;
    });

    expect($handled)->toBe(1);
});

it('skips re-executing the job on a duplicate key with an identical payload', function (): void {
    $manager = new JobIdempotencyManager(
        new InMemoryJobIdempotencyStore,
        new Sha256JobFingerprinter,
        new InMemoryIdempotencyLock,
    );

    $handled = 0;
    $next = function () use (&$handled): void {
        $handled++;
    };

    $manager->handle(new FakeJob(100), 'job-123', $next);
    $manager->handle(new FakeJob(100), 'job-123', $next);

    expect($handled)->toBe(1);
});

it('throws a conflict exception when the same key is reused with a different payload', function (): void {
    $manager = new JobIdempotencyManager(
        new InMemoryJobIdempotencyStore,
        new Sha256JobFingerprinter,
        new InMemoryIdempotencyLock,
    );

    $manager->handle(new FakeJob(100), 'job-123', function (): void {});

    expect(fn () => $manager->handle(new FakeJob(500), 'job-123', function (): void {}))
        ->toThrow(JobIdempotencyConflictException::class);
});

it('ignores an expired record and re-executes the job', function (): void {
    $store = new InMemoryJobIdempotencyStore;
    $fingerprinter = new Sha256JobFingerprinter;
    $job = new FakeJob(100);

    $store->store(new JobIdempotencyRecord(
        key: 'job-123',
        fingerprint: $fingerprinter->fingerprint($job),
        jobClass: FakeJob::class,
        createdAt: new DateTimeImmutable('-2 days'),
        expiresAt: new DateTimeImmutable('-1 day'),
    ));

    $manager = new JobIdempotencyManager(
        $store,
        $fingerprinter,
        new InMemoryIdempotencyLock,
    );

    $handled = 0;

    $manager->handle($job, 'job-123', function () use (&$handled): void {
        $handled++;
    });

    expect($handled)->toBe(1);
});

it('releases the lock when the job throws', function (): void {
    $manager = new JobIdempotencyManager(
        new InMemoryJobIdempotencyStore,
        new Sha256JobFingerprinter,
        new InMemoryIdempotencyLock,
    );

    $job = new FakeJob(100);

    expect(fn () => $manager->handle($job, 'job-123', fn () => throw new RuntimeException('Failed')))
        ->toThrow(RuntimeException::class, 'Failed');

    $handled = 0;

    $manager->handle($job, 'job-123', function () use (&$handled): void {
        $handled++;
    });

    expect($handled)->toBe(1);
});

it('throws a lock conflict exception when the lock cannot be acquired', function (): void {
    $lock = new InMemoryIdempotencyLock;
    $lock->acquire('job:job-123');

    $manager = new JobIdempotencyManager(
        new InMemoryJobIdempotencyStore,
        new Sha256JobFingerprinter,
        $lock,
    );

    expect(fn () => $manager->handle(new FakeJob(100), 'job-123', function (): void {}))
        ->toThrow(JobIdempotencyLockConflictException::class);
});
