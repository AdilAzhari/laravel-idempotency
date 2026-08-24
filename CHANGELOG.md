# Changelog

All notable changes to this project will be documented in this file.

## [Unreleased]

### Added

- Job-level idempotency via `JobIdempotencyMiddleware` (`new JobIdempotencyMiddleware(key: ...)` in a job's `middleware()` method), protecting queued job side effects from duplication on retry-after-partial-success and at-least-once queue redelivery — cases Laravel's built-in `ShouldBeUnique`/`WithoutOverlapping` don't cover, since those only prevent concurrent duplicate dispatch
- `JobIdempotencyStore` contract with array, cache, redis, and database drivers, matching the existing HTTP store drivers
- `IdempotencyContext::current()` bridges an HTTP request's idempotency key into jobs it dispatches, so both layers can share one identity
- `Idempotency-Replayed` response header indicating whether a response was replayed or freshly executed (configurable via `replay_header`)
- Configurable HTTP method scope for the middleware, defaulting to `POST`, `PUT`, `PATCH`, `DELETE` (`methods` config)
- Idempotency key length validation (`key_max_length` config), rejected with a `400 Bad Request`
- `idempotency-migrations` publish group, matching the installation instructions in the README

### Fixed

- `IdempotencyManager` is no longer resolved as a singleton, preventing a shared, mutable lock instance from leaking lock state between concurrent requests handled by the same worker (e.g. under Octane)
- `CacheIdempotencyLock` now tracks each acquired lock independently instead of a single mutable slot, so releasing one key can no longer release a different key's lock
- `IdempotencyConflictException` now renders as a `409 Conflict` JSON response instead of surfacing as an uncaught exception
- Failing to acquire the idempotency lock now throws `IdempotencyLockConflictException`, rendered as `409 Conflict`, instead of a generic `RuntimeException`

### Removed

- Unused `RedisConnectionFactory` contract

## [1.0.0] - 2026-07-25

### Added

- HTTP Idempotency middleware
- Configurable storage drivers
    - Cache
    - Array
    - Redis
    - Database
- Atomic request locking
- Request fingerprinting
- Response replay
- Expiration handling
- Conflict detection
- Database migration
- Prune command
- Extensive test suite
- GitHub Actions CI
- PHPStan
- Laravel Pint
- Rector

## [0.1.0] - 2026-07-22

### Added

- Idempotency middleware for Laravel applications
- Request fingerprinting using SHA-256
- Response replay support
- Idempotency conflict detection
- Cache-based response storage
- Cache-based locking
- Configurable idempotency header and expiration
- Extension points through contracts

