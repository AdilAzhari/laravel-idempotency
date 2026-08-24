<p align="center">
    <img src="assets/banner.svg" alt="Laravel Idempotency">
</p>

<p align="center">
    <img src="assets/logo.svg" width="120" alt="Laravel Idempotency Logo">
</p>

<h1 align="center">Laravel Idempotency</h1>

<p align="center">
    Framework-native HTTP idempotency for Laravel applications.
</p>

<p align="center">

[![Tests](https://github.com/AdilAzhari/laravel-idempotency/actions/workflows/tests.yml/badge.svg)](https://github.com/AdilAzhari/laravel-idempotency/actions/workflows/tests.yml)

![PHP](https://img.shields.io/badge/PHP-8.5+-777BB4)

![Laravel](https://img.shields.io/badge/Laravel-13.x-FF2D20)

![License](https://img.shields.io/github/license/AdilAzhari/laravel-idempotency)

</p>

---

# Why Another Idempotency Package?

Laravel applications commonly implement idempotency in an ad-hoc manner, often coupling request replay, persistence, and locking into application code.

Laravel Idempotency provides a reusable, framework-native solution built around explicit contracts and interchangeable components, allowing storage, fingerprinting, and locking strategies to evolve independently.

# Laravel Idempotency

Laravel Idempotency provides a framework-native solution for ensuring that identical HTTP requests are executed exactly once.

Using an **Idempotency-Key**, the package stores the original HTTP response and automatically replays it for subsequent identical requests, eliminating accidental duplicate operations caused by retries or network failures.

It is particularly useful for:

- Payment processing
- Subscription management
- Order creation
- Inventory updates
- Account changes
- External API integrations
- Webhook processing

---

# Why Idempotency?

Distributed systems are unreliable.

Clients retry requests.

Browsers retry requests.

Mobile applications automatically retry after network interruptions.

Load balancers and reverse proxies may resend requests.

Without idempotency, a single retry can unintentionally create duplicate:

- Payments
- Orders
- Invoices
- Subscriptions
- Shipments
- Customer accounts

Laravel Idempotency guarantees that the same logical operation executes only once while returning the original response for every identical retry.

---

# Features

- HTTP idempotency middleware
- Job idempotency middleware, protecting queued job side effects from retries and redelivery
- HTTP-to-job idempotency key propagation via `IdempotencyContext`
- Atomic request locking
- Automatic response replay
- SHA-256 request fingerprinting
- Configurable response expiration
- Configurable HTTP method scope (skips safe methods like `GET` by default)
- `Idempotency-Replayed` response header on every protected response
- Idempotency key length validation
- Multiple storage drivers
    - Array
    - Cache
    - Redis
    - Database
- Database pruning command
- Custom fingerprint strategies
- Custom storage implementations
- Laravel-native dependency injection
- Fully tested
- Production-ready

---

# Requirements

- PHP 8.5+
- Laravel 13+

For production deployments, use a shared storage backend such as Redis or the Database driver.

---

# Installation

Install the package using Composer.

```bash
composer require adilazhari/laravel-idempotency
```

Laravel automatically discovers the package.

Publish the configuration file:

```bash
php artisan vendor:publish --tag=idempotency-config
```

If you intend to use the database storage driver, also publish the migration:

```bash
php artisan vendor:publish --tag=idempotency-migrations

php artisan migrate
```

---

# Quick Start

Protect any write endpoint using the middleware.

```php
use AdilAzhari\LaravelIdempotency\Http\Middleware\IdempotencyMiddleware;
use Illuminate\Support\Facades\Route;

Route::post('/payments', CreatePaymentController::class)
    ->middleware(IdempotencyMiddleware::class);
```

Clients simply provide an idempotency key.

```http
POST /payments
Idempotency-Key: payment-123

{
    "amount": 1000,
    "currency": "MYR"
}
```

The first request executes normally.

Subsequent identical requests return the previously stored response without executing the controller again.

---

# How It Works

```
                 Client
                    │
                    ▼
       Idempotency Middleware
                    │
                    ▼
     Generate Request Fingerprint
                    │
                    ▼
         Acquire Execution Lock
                    │
        ┌───────────┴───────────┐
        │                       │
        ▼                       ▼
 Existing Response?          No Response
        │                       │
      Yes                       ▼
        │               Execute Controller
        │                       │
        ▼                       ▼
 Replay Stored Response   Store HTTP Response
        │                       │
        └───────────┬───────────┘
                    ▼
             Release Lock
                    │
                    ▼
             Return Response
```

---

# Request Matching

Two requests are considered identical when all of the following match:

- Idempotency key
- HTTP method
- Request path
- Query parameters
- Parsed request body

The default implementation uses the built-in `Sha256RequestFingerprinter`.

For example:

```http
POST /payments

{
    "amount":1000
}
```

and

```http
POST /payments

{
    "amount":2000
}
```

are treated as different requests, even if they share the same idempotency key.

If the same key is reused for different request data, an `IdempotencyConflictException` is thrown and rendered as a `409 Conflict` JSON response.

---

# Error Responses

| Situation | Exception | Status |
|-----------|-----------|--------|
| Same key reused with different request data | `IdempotencyConflictException` | `409 Conflict` |
| Another request with the same key is still in flight | `IdempotencyLockConflictException` | `409 Conflict` |
| Idempotency key exceeds `key_max_length` | `InvalidIdempotencyKeyException` | `400 Bad Request` |

All three exceptions are self-rendering, so no custom exception handler registration is required.

`JobIdempotencyMiddleware` throws the equivalent plain exceptions for jobs — `JobIdempotencyConflictException` and `JobIdempotencyLockConflictException` — which are not self-rendering, since a queue worker has no HTTP response to render.

---

# Jobs

Idempotency isn't only an HTTP concern. Laravel's own `ShouldBeUnique`/`WithoutOverlapping` prevent *concurrent duplicate dispatch*, but they don't protect against a job's side effects running twice after a retry-following-partial-success, an at-least-once queue redelivery, or two separate dispatches of the same logical action (e.g. a webhook retried by its sender).

`JobIdempotencyMiddleware` protects a job's side effects the same way `IdempotencyMiddleware` protects HTTP responses: a stable key, atomic locking, and a persisted record so a duplicate execution is skipped rather than re-run.

```php
use AdilAzhari\LaravelIdempotency\Queue\Middleware\JobIdempotencyMiddleware;

final class ChargeCustomer implements ShouldQueue
{
    public function __construct(
        private readonly Order $order,
    ) {}

    public function middleware(): array
    {
        return [new JobIdempotencyMiddleware(key: $this->order->id)];
    }

    public function handle(): void
    {
        // Charge the customer. If this job is retried or redelivered with
        // the same key and the same payload, handle() will not run again.
    }
}
```

If the same key is later reused for a job with a different payload, a `JobIdempotencyConflictException` is thrown — the job-side equivalent of `IdempotencyConflictException`.

---

## Connecting an HTTP Request to the Job It Dispatches

`IdempotencyContext` lets a job adopt the idempotency key of the HTTP request that triggered it, so one key protects both layers.

```php
use AdilAzhari\LaravelIdempotency\Support\IdempotencyContext;

public function middleware(): array
{
    return [new JobIdempotencyMiddleware(
        key: IdempotencyContext::current() ?? $this->order->id
    )];
}
```

`IdempotencyContext::current()` returns the key from the current HTTP request when one was processed by `IdempotencyMiddleware`, or `null` outside of a request — falling back to your own key is always recommended.

---

# Storage Drivers

Laravel Idempotency supports multiple persistence backends.

| Driver | Recommended Usage |
|---------|-------------------|
| Array | Testing |
| Cache | Default |
| Redis | Distributed applications |
| Database | Durable persistence |

Select the active driver through configuration.

```env
IDEMPOTENCY_STORE=cache
```

Switching drivers requires no application code changes.

---

# Configuration

```php
return [

    'driver' => env('IDEMPOTENCY_STORE', 'cache'),

    'stores' => [

        'cache' => [
            'driver' => 'cache',
        ],

        'array' => [
            'driver' => 'array',
        ],

        'redis' => [
            'driver' => 'redis',
            'connection' => 'default',
            'prefix' => 'idempotency:',
        ],

        'database' => [
            'driver' => 'database',
        ],

    ],

    'header' => 'Idempotency-Key',

    'methods' => ['POST', 'PUT', 'PATCH', 'DELETE'],

    'key_max_length' => 255,

    'replay_header' => 'Idempotency-Replayed',

    'lock' => [
        'seconds' => 10,
    ],

    'expiration' => 86400,

    'jobs' => [
        'driver' => env('IDEMPOTENCY_JOB_STORE', env('IDEMPOTENCY_STORE', 'cache')),
        'expiration' => env('IDEMPOTENCY_JOB_EXPIRATION', 86400),
    ],

];
```

| Option | Description |
|----------|-------------|
| `driver` | Active storage driver |
| `header` | HTTP header containing the idempotency key |
| `methods` | HTTP methods the middleware applies to. An empty array applies it to every method. |
| `key_max_length` | Maximum allowed length of an idempotency key before a `400` is returned |
| `replay_header` | Response header set to `true`/`false` indicating a replayed vs. fresh response. Set to `null` to disable |
| `lock.seconds` | Maximum lock duration |
| `expiration` | Lifetime of stored responses |
| `jobs.driver` | Storage driver used by `JobIdempotencyMiddleware`. Defaults to the same driver as `driver` |
| `jobs.expiration` | Lifetime of stored job records |

The lock duration should comfortably exceed the execution time of your protected endpoints.
---

# Extending

Laravel Idempotency is built around a small set of contracts, allowing individual components to be replaced without modifying the package.

## Custom Request Fingerprinting

Bind your own implementation of the `RequestFingerprinter` contract.

```php
use AdilAzhari\LaravelIdempotency\Contracts\RequestFingerprinter;

$this->app->bind(
    RequestFingerprinter::class,
    CustomRequestFingerprinter::class,
);
```

Possible use cases include:

- Ignoring selected request fields
- Including additional HTTP headers
- Supporting custom hashing algorithms
- Multi-tenant request isolation

---

## Custom Storage

Implement the `IdempotencyStore` contract to persist responses anywhere.

```php
use AdilAzhari\LaravelIdempotency\Contracts\IdempotencyStore;

$this->app->bind(
    IdempotencyStore::class,
    CustomIdempotencyStore::class,
);
```

Possible implementations include:

- Redis
- Database
- DynamoDB
- MongoDB
- Amazon S3
- External persistence services

---

## Custom Locking

Locking is also replaceable.

```php
use AdilAzhari\LaravelIdempotency\Contracts\IdempotencyLock;

$this->app->bind(
    IdempotencyLock::class,
    CustomIdempotencyLock::class,
);
```

This allows integration with:

- Redis locks
- Database locks
- Distributed lock services
- Cloud-native coordination systems

---

# Storage Drivers

## Cache Driver

The Cache driver is the default storage implementation.

```env
IDEMPOTENCY_STORE=cache
```

It stores responses using Laravel's configured cache store and is suitable for most applications.

---

## Array Driver

The Array driver stores responses in memory.

```env
IDEMPOTENCY_STORE=array
```

This driver is intended for:

- Unit tests
- Local development
- Temporary storage

Because data exists only in memory, it should not be used in production.

---

## Redis Driver

The Redis driver stores responses directly in Redis.

```env
IDEMPOTENCY_STORE=redis
```

Configuration:

```php
'redis' => [

    'driver' => 'redis',

    'connection' => 'default',

    'prefix' => 'idempotency:',

],
```

This driver is recommended for distributed deployments where multiple application instances must share idempotency records.

---

## Database Driver

The Database driver persists responses using Eloquent.

```env
IDEMPOTENCY_STORE=database
```

Before using it, publish the migration:

```bash
php artisan vendor:publish --tag=idempotency-migrations

php artisan migrate
```

The database driver provides durable storage that survives cache flushes and application restarts.

---

# Pruning Expired Records

Expired database records can be removed using the built-in Artisan command.

```bash
php artisan idempotency:prune
```

Scheduling the command is recommended.

```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('idempotency:prune')
    ->daily();
```

---

# Design Principles

Laravel Idempotency is intentionally built around a small set of principles.

- Framework-native integration
- Explicit contracts
- Predictable behaviour
- Simple public API
- Testability
- Extensibility

---

# Testing

Run the complete test suite.

```bash
composer test
```

The package includes comprehensive unit and feature tests covering:

- Request replay
- Conflict detection
- Request fingerprinting
- Atomic locking
- Cache storage
- Redis storage
- Database storage
- Array storage
- Middleware integration
- Service provider registration
- Configuration
- Console commands

---

# Documentation

Additional documentation is available in the `docs` directory.

- Architecture
- Storage Drivers
- Custom Fingerprinting
- Custom Storage Drivers
- Custom Locking
- Contributing Guide

---

# Roadmap

- ✅ Laravel 13 support
- ✅ HTTP idempotency middleware
- ✅ Atomic request locking
- ✅ SHA-256 request fingerprinting
- ✅ Cache storage driver
- ✅ Array storage driver
- ✅ Redis storage driver
- ✅ Database storage driver
- ✅ Configurable storage drivers
- ✅ Database pruning command
- ✅ Job idempotency middleware
- ✅ HTTP-to-job idempotency key propagation
- 🚧 Additional storage adapters
- 🚧 Step-level checkpointing within a job (resumable partial execution)

---

# Contributing

Contributions are welcome.

If you discover a bug or would like to propose a new feature, please open an issue before submitting a pull request so the implementation can be discussed first.

When contributing:

- Follow the existing coding style.
- Include tests for new functionality.
- Ensure `composer test` passes before opening a pull request.

---

# License

Laravel Idempotency is open-source software licensed under the MIT License.
