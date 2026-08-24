<?php

declare(strict_types=1);

namespace AdilAzhari\LaravelIdempotency;

use AdilAzhari\LaravelIdempotency\Contracts\IdempotencyLock;
use AdilAzhari\LaravelIdempotency\Contracts\JobFingerprinter;
use AdilAzhari\LaravelIdempotency\Contracts\JobIdempotencyStore;
use AdilAzhari\LaravelIdempotency\Exceptions\JobIdempotencyConflictException;
use AdilAzhari\LaravelIdempotency\Exceptions\JobIdempotencyLockConflictException;
use AdilAzhari\LaravelIdempotency\ValueObjects\IdempotencyKey;
use AdilAzhari\LaravelIdempotency\ValueObjects\JobIdempotencyRecord;
use Closure;
use DateInterval;
use DateTimeImmutable;

final readonly class JobIdempotencyManager
{
    public function __construct(
        private JobIdempotencyStore $store,
        private JobFingerprinter $fingerprinter,
        private IdempotencyLock $lock,
        private int $expiration = 86400,
        private int $maxKeyLength = IdempotencyKey::DEFAULT_MAX_LENGTH,
    ) {}

    /**
     * @param  Closure(object): void  $next
     */
    public function handle(
        object $job,
        string $key,
        Closure $next
    ): void {
        $idempotencyKey = new IdempotencyKey($key, $this->maxKeyLength);

        $lockKey = 'job:'.$idempotencyKey->value;

        $fingerprint = $this->fingerprinter->fingerprint($job);

        if (! $this->lock->acquire($lockKey)) {
            throw JobIdempotencyLockConflictException::forKey($idempotencyKey->value);
        }

        try {
            /**
             * Check again after acquiring lock.
             *
             * Another worker might have completed this job while
             * this one was waiting.
             */
            $stored = $this->store->find($idempotencyKey->value);

            if ($stored?->isExpired()) {
                $this->store->forget($idempotencyKey->value);
                $stored = null;
            }

            if ($stored instanceof JobIdempotencyRecord) {

                if ($stored->fingerprint !== $fingerprint) {
                    throw JobIdempotencyConflictException::forKey($idempotencyKey->value);
                }

                return;
            }

            $next($job);

            $createdAt = new DateTimeImmutable;

            $record = new JobIdempotencyRecord(
                key: $idempotencyKey->value,
                fingerprint: $fingerprint,
                jobClass: $job::class,
                createdAt: $createdAt,
                expiresAt: $createdAt->add(
                    DateInterval::createFromDateString($this->expiration.' seconds')
                ),
            );

            $this->store->store(
                record: $record,
            );

        } finally {

            $this->lock->release($lockKey);

        }
    }
}
