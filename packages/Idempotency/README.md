# Nexus\Idempotency

## Overview

`Nexus\Idempotency` is a **Layer 1** package for **command-level idempotency**: deduplicate mutating operations using a tenant-scoped composite key `(tenantId, operationRef, clientKey)` plus a **request fingerprint**, and **replay** a stored opaque result for safe retries.

It does **not** implement HTTP, databases, outbox, event streams, or audit trails—see [REQUIREMENTS.md](REQUIREMENTS.md) for boundaries.

## Architecture

- Layer 1: pure PHP 8.3+, framework-agnostic
- Explicit lifecycle: `begin()` → domain work → `complete()` or `fail()`
- Persistence via `IdempotencyStoreInterface` (e.g. `InMemoryIdempotencyStore` for tests — uses JSON-encoded tuple keys so tenant/operation/client segments cannot collide across `|` characters)
- Time via `IdempotencyClockInterface` (`SystemClock` or test doubles)

## Key interfaces

- `Nexus\Idempotency\Contracts\IdempotencyServiceInterface`
- `Nexus\Idempotency\Contracts\IdempotencyStoreInterface`
- `Nexus\Idempotency\Contracts\IdempotencyClockInterface`

## Installation

From monorepo root:

```bash
composer dump-autoload
vendor/bin/phpunit -c packages/Idempotency/phpunit.xml
```

## Usage (conceptual)

```php
$service = new IdempotencyService($store, $clock, IdempotencyPolicy::default());

$decision = $service->begin($tenantId, $operationRef, $clientKey, $fingerprint);

if ($decision->outcome === BeginOutcome::Replay) {
    return $decision->replayResult; // replay cached outcome
}

if ($decision->outcome === BeginOutcome::InProgress) {
    // another in-flight execution; surface 409/Retry-After in Layer 3
}

// FirstExecution: run domain command, then:
$service->complete($tenantId, $operationRef, $clientKey, $fingerprint, new ResultEnvelope($json));
```

## License

MIT
