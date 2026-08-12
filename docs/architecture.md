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

## Design Goals

- Predictable behaviour
- Small public API
- Testability
- Framework-native integration
- Replaceable components****
