<p align="center">
    <a href="https://github.com/AdilAzhari/laravel-idempotency/actions">
        <img src="https://github.com/AdilAzhari/laravel-idempotency/actions/workflows/tests.yml/badge.svg">
    </a>
</p>

# Laravel Idempotency

Laravel Idempotency prevents duplicate execution of requests caused by retries. It is designed for write operations where executing the same request multiple times can create unwanted side effects, such as payments, orders, account updates, and external API calls.

When a client sends an `Idempotency-Key`, the package associates that key with the request details and stores the resulting HTTP response. If the same request is repeated with the same key, the original response is returned without executing the request pipeline again.

If the same key is reused with different request details, the package throws an `IdempotencyConflictException`.

## Requirements

* PHP 8.5 or later
* Laravel 13
* A cache driver that supports atomic locks

For production environments with multiple application instances, use a shared cache backend such as Redis or database cache.

## Supported Laravel Versions

| Laravel Version | Package Version |
| --------------- | --------------- |
| 13.x            | 0.x             |

Laravel Idempotency 0.x currently supports Laravel 13.

Support for additional Laravel versions may be added in future releases.

## Installation

Install the package using Composer:

```bash
composer require adilazhari/laravel-idempotency
```

Laravel automatically discovers the service provider.

To customize the package configuration, publish the configuration file:

```bash
php artisan vendor:publish --tag=idempotency-config
```

This will create:

```text
config/idempotency.php
```

## Usage

Apply the idempotency middleware to any route that requires protection.

Example:

```php
use AdilAzhari\LaravelIdempotency\Http\Middleware\IdempotencyMiddleware;
use Illuminate\Support\Facades\Route;

Route::post('/payments', CreatePaymentController::class)
    ->middleware(IdempotencyMiddleware::class);
```

Clients should provide a unique idempotency key with the request:

```http
POST /payments HTTP/1.1
Content-Type: application/json
Idempotency-Key: payment-7f1f7b2e

{
    "amount": 1000,
    "currency": "MYR"
}
```

The first request is processed normally. After the response is generated, the package stores the response details.

A retry with:

* the same idempotency key
* the same HTTP method
* the same path
* the same query parameters
* the same request input

will receive the stored response without executing the endpoint again.

Example:

```text
Request 1:
POST /payments
Idempotency-Key: payment-123

Controller executed
Response stored


Request 2:
POST /payments
Idempotency-Key: payment-123

Stored response returned
Controller not executed
```

Requests without an idempotency key continue normally.

## Configuration

The default configuration:

```php
return [

    /*
    |--------------------------------------------------------------------------
    | Idempotency Header
    |--------------------------------------------------------------------------
    |
    | The request header used to identify idempotency requests.
    |
    */

    'header' => 'Idempotency-Key',

    /*
    |--------------------------------------------------------------------------
    | Lock Configuration
    |--------------------------------------------------------------------------
    |
    | Controls how long a request execution lock is held.
    |
    */

    'lock' => [
        'seconds' => 10,
    ],

    /*
    |--------------------------------------------------------------------------
    | Response Expiration
    |--------------------------------------------------------------------------
    |
    | The amount of time a stored response can be replayed.
    |
    */

    'expiration' => 86400,

];
```

### Configuration Options

| Option         | Description                                                     |
| -------------- | --------------------------------------------------------------- |
| `header`       | The HTTP header containing the idempotency key                  |
| `lock.seconds` | Maximum time an in-progress request can hold the execution lock |
| `expiration`   | How long stored responses remain available                      |

The lock duration should be longer than the expected execution time of protected endpoints.

If the lock expires while the original request is still processing, another request may execute concurrently.

## How Request Matching Works

Laravel Idempotency uses a request fingerprint to determine whether a retry represents the same operation.

The default `Sha256RequestFingerprinter` creates a fingerprint based on:

* HTTP method
* Request path
* Query parameters
* Parsed request input

Example:

```text
POST /payments

{
    "amount": 1000
}
```

and:

```text
POST /payments

{
    "amount": 2000
}
```

are considered different requests even when they use the same idempotency key.

## Extending the Package

The package uses contracts so its core behaviour can be replaced.

Available extension points:

### Request Fingerprinting

Replace the default fingerprint implementation:

```php
RequestFingerprinter
```

Example use cases:

* Include custom headers
* Ignore specific fields
* Use another hashing strategy

---

### Storage

Replace the response storage implementation:

```php
IdempotencyStore
```

Possible implementations:

* Redis
* Database
* DynamoDB
* Custom storage service

---

### Locking

Replace the execution lock implementation:

```php
IdempotencyLock
```

Possible implementations:

* Redis locks
* Database locks
* Distributed lock services

## Architecture Overview

The request lifecycle:

```text
Client
  |
  | Idempotency-Key
  |
Middleware
  |
  |-- Generate request fingerprint
  |
  |-- Acquire lock
  |
  |-- Check stored response
  |
  |-- Execute request if missing
  |
  |-- Store response
  |
  |-- Release lock
  |
Response
```

The package ensures that only one request with a specific idempotency key executes at a time.

## Testing

Run the complete test suite:

```bash
composer test
```

The test suite includes:

* Response replay behaviour
* Idempotency conflicts
* Expired records
* Lock handling
* Service provider bindings
* Middleware integration

## License

Laravel Idempotency is open-source software licensed under the [MIT License](LICENSE.md).
