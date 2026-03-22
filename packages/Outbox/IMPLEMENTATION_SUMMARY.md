# Nexus\Outbox — Implementation Summary

## Scope

Layer 1 package: **value objects**, **domain record** with guarded transitions, **split store contracts** (`OutboxQueryInterface`, `OutboxPersistInterface`, composite `OutboxStoreInterface`), **`OutboxService`**, **`InMemoryOutboxStore`** for tests, **`SystemClock`** (UTC).

No framework, HTTP, queue, or SQL.

## State machine

- `Pending` → `Sending` via `claimNextPending` (opaque `ClaimToken` + `claimExpiresAt`).
- `Sending` → `Sent` via `markSent` (valid token + lease not expired).
- `Sending` → `Failed` via `markFailed`.
- `Failed` → `Pending` via `scheduleRetry` (increments `attemptCount`).

## Deduplication

Enqueue is **idempotent per (tenant, dedup key)**: second enqueue with the same pair returns the existing row (`EnqueueResult::wasInserted === false`).

## Multi-tenancy

`findById` is tenant-scoped: a message belonging to another tenant is invisible (`null`), avoiding cross-tenant existence leaks.

## Relation to other packages

| Package | Relationship |
|---------|----------------|
| `Nexus\EventStream` | Optional **dual-write** in L3: append then enqueue; dedup key often `EventInterface::getEventId()` — see `docs/event-stream-bridge.md`. |
| `Nexus\Idempotency` | Complementary: idempotency at **ingress**; outbox at **egress** integration. |

## Testing

Run from package directory:

```bash
../../vendor/bin/phpunit
```
