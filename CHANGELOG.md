# Changelog

All notable changes to this project will be documented in this file.

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

