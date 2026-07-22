# Extending Laravel Idempotency

Laravel Idempotency is built around contracts so individual components can be replaced without modifying package internals.

---

# Custom Request Fingerprinter

```php
use App\Support\CustomFingerprinter;
use AdilAzhari\LaravelIdempotency\Contracts\RequestFingerprinter;

$this->app->bind(
    RequestFingerprinter::class,
    CustomFingerprinter::class,
);
```

Possible use cases:

- Ignore selected fields
- Include custom headers
- Different hashing algorithm

---

# Custom Storage

```php
use App\Storage\DatabaseStore;
use AdilAzhari\LaravelIdempotency\Contracts\IdempotencyStore;

$this->app->bind(
    IdempotencyStore::class,
    DatabaseStore::class,
);
```

Possible storage implementations:

- Redis
- Database
- DynamoDB
- Cloud storage

---

# Custom Locking

```php
use App\Locks\RedisLock;
use AdilAzhari\LaravelIdempotency\Contracts\IdempotencyLock;

$this->app->bind(
    IdempotencyLock::class,
    RedisLock::class,
);
```

Possible implementations:

- Redis Locks
- Database Locks
- Distributed Lock Services

---

# Writing Custom Components

Every extension point follows the same principles.

- Keep implementations deterministic.
- Avoid introducing side effects.
- Preserve idempotency guarantees.
- Ensure implementations are thread-safe when appropriate.

---

# Best Practices

- Use a shared cache in distributed environments.
- Choose lock durations longer than expected request execution.
- Keep request fingerprinting stable.
- Store responses only after successful execution.
