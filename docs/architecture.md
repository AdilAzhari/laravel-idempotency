# Architecture

Laravel Idempotency is intentionally small and built around a few focused abstractions.

```
HTTP Request
      │
      ▼
IdempotencyMiddleware
      │
      ▼
IdempotencyManager
      │
 ┌────┴──────────────┐
 ▼                   ▼
Request         Idempotency
Fingerprinter      Lock
 │                   │
 └──────┬────────────┘
        ▼
 IdempotencyStore
        │
        ▼
 Stored Response
```

## Components

### IdempotencyMiddleware

Entry point for protected routes.

Responsibilities:

- Extract the idempotency key
- Delegate request processing to the manager

---

### IdempotencyManager

Coordinates the entire request lifecycle.

Responsibilities:

- Generate request fingerprints
- Acquire execution locks
- Detect stored responses
- Replay cached responses
- Execute the request pipeline
- Persist successful responses

---

### RequestFingerprinter

Produces a deterministic fingerprint for a request.

Default implementation:

- HTTP Method
- Path
- Query Parameters
- Parsed Request Body

---

### IdempotencyStore

Persists responses.

Default implementation:

- Laravel Cache Repository

Alternative implementations may use:

- Redis
- Database
- DynamoDB

---

### IdempotencyLock

Ensures that only one request with the same key executes simultaneously.

Default implementation uses Laravel's atomic cache locks.

---

## Request Lifecycle

1. Receive request
2. Extract idempotency key
3. Pass through unchanged if the HTTP method is outside the configured `methods` list
4. Validate the idempotency key length
5. Generate request fingerprint
6. Acquire execution lock
7. Check for existing response
8. Replay response if available, marking it via the `Idempotency-Replayed` header
9. Execute controller
10. Persist response
11. Release lock
12. Return response

---

## Job Idempotency

The same guarantees extend to queued jobs, which have a distinct failure mode: a job's side effect can already have executed once before a retry, redelivery, or duplicate dispatch runs it again — something route-level HTTP idempotency cannot see.

```
Job Dispatch
      │
      ▼
JobIdempotencyMiddleware
      │
      ▼
JobIdempotencyManager
      │
 ┌────┴──────────────┐
 ▼                   ▼
JobFingerprinter   Idempotency
                      Lock
 │                   │
 └──────┬────────────┘
        ▼
 JobIdempotencyStore
        │
        ▼
 Stored Job Record
```

`JobIdempotencyMiddleware` takes its key as a constructor argument (mirroring Laravel's own `WithoutOverlapping($key)`/`RateLimited($key)`) rather than reading it from the job automatically — there is no equivalent of an HTTP header to read it from.

`JobIdempotencyManager` mirrors `IdempotencyManager`'s lifecycle exactly, with one difference: on a duplicate key with a matching fingerprint, there is no response to replay — the job's `handle()` method is simply never called, and the job completes as if it had succeeded.

`JobIdempotencyStore` and `JobFingerprinter` are separate contracts from their HTTP counterparts (a job has no HTTP response to persist, and its "request" is the job object itself), but job idempotency reuses the same `IdempotencyLock` contract and implementation unchanged, under a `job:`-prefixed key so HTTP and job locks never collide.

### IdempotencyContext

A small, request-scoped bridge between the two lifecycles. `IdempotencyManager` records the resolved HTTP idempotency key on `IdempotencyContext` as it processes a request; `IdempotencyContext::current()` lets a job dispatched from within that request read the same key back, so one identity can protect both the HTTP response and the asynchronous work it triggers.

---

## Design Goals

- Predictable behaviour
- Small public API
- Testability
- Framework-native integration
- Replaceable components
