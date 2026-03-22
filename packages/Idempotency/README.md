# Nexus\Idempotency

## Overview

`Nexus\Idempotency` is a **Layer 1** package for **command-level idempotency**: deduplicate mutating operations using a tenant-scoped composite key `(tenantId, operationRef, clientKey)` plus a **request fingerprint**, and **replay** a stored opaque result for safe retries.

It does **not** implement HTTP, databases, outbox, event streams, or audit trails—see [REQUIREMENTS.md](REQUIREMENTS.md) for boundaries.

## Architecture

- Layer 1: pure PHP 8.3+, framework-agnostic
- Explicit lifecycle: `begin()` → domain work → `complete()` or `fail()`
- Persistence via `IdempotencyStoreInterface` (composite of `IdempotencyQueryInterface` + `IdempotencyPersistInterface`; e.g. `InMemoryIdempotencyStore` for tests — uses **JSON-encoded tuple keys** `json_encode([tenantId, operationRef, clientKey])` so segments cannot collide across delimiter characters)
- Time via `IdempotencyClockInterface` (`SystemClock` returns UTC `DateTimeImmutable`, or test doubles)

## Key interfaces

- `Nexus\Idempotency\Contracts\IdempotencyServiceInterface`
- `Nexus\Idempotency\Contracts\IdempotencyStoreInterface` (extends query + persist ports)
- `Nexus\Idempotency\Contracts\IdempotencyClockInterface`

## Installation

From monorepo root:

```bash
composer dump-autoload
vendor/bin/phpunit -c packages/Idempotency/phpunit.xml
```

## Usage (conceptual)

```php
$service = new IdempotencyService($store, $store, $clock, IdempotencyPolicy::default());

$decision = $service->begin($tenantId, $operationRef, $clientKey, $fingerprint);

if ($decision->outcome === BeginOutcome::Replay) {
    return $decision->replayResult; // replay cached outcome
}

if ($decision->outcome === BeginOutcome::InProgress) {
    // Another in-flight execution for the same key + fingerprint; surface 409 + Retry-After in Layer 3.
    return;
}

// BeginOutcome::FirstExecution — run domain command, then complete using the attempt bound to this reservation:
$service->complete(
    $tenantId,
    $operationRef,
    $clientKey,
    $fingerprint,
    $decision->record->attemptToken,
    new ResultEnvelope($json),
);
```

`complete()` / `fail()` require the **`AttemptToken`** from the `FirstExecution` record for that attempt so completions cannot attach to a superseded reservation after TTL expiry/replace.

## License

MIT
