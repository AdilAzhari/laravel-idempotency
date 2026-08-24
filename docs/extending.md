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

# Custom Job Fingerprinting

```php
use App\Support\CustomJobFingerprinter;
use AdilAzhari\LaravelIdempotency\Contracts\JobFingerprinter;

$this->app->bind(
    JobFingerprinter::class,
    CustomJobFingerprinter::class,
);
```

Possible use cases:

- Ignore volatile job properties (e.g. a correlation ID)
- Fingerprint based on a subset of the job's payload
- Different hashing algorithm

---

# Custom Job Storage

```php
use App\Storage\DatabaseJobStore;
use AdilAzhari\LaravelIdempotency\Contracts\JobIdempotencyStore;

$this->app->bind(
    JobIdempotencyStore::class,
    DatabaseJobStore::class,
);
```

Job storage is a separate contract from HTTP storage — a job has no response to persist, only a record that it has already run. `IdempotencyLock` is shared between both.

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
