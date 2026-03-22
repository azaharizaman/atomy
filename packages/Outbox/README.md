# Nexus\Outbox

**Layer 1 — transactional outbox domain model and publish lifecycle**

This package defines **contracts and invariants** for the [transactional outbox pattern](https://microservices.io/patterns/data/transactional-outbox.html): enqueue integration messages **in the same unit of work** as business state, then **publish asynchronously** with explicit **pending → sending → sent | failed** semantics.

It is **not** a message broker, queue driver, or database layer. Persistence and workers live in **Layer 3** adapters. `InMemoryOutboxStore` is for tests and single-process use only (not a cross-process concurrency model; L3 should use row locking and unique constraints).

## When to use

- You need **reliable fan-out** to Notifier, webhooks, analytics, or external systems **after** a successful commit.
- You pair with **`Nexus\EventStream`** when the system of record is an append-only stream but integrators do not consume the stream directly — see [`docs/event-stream-bridge.md`](docs/event-stream-bridge.md).

## When not to use

- **Event sourcing / replay** of aggregate history → `Nexus\EventStream`.
- **HTTP command idempotency** (replay-safe API results) → `Nexus\Idempotency`.
- **Exactly-once end-to-end delivery** — not guaranteed; consumers must be **idempotent** or deduplicate (this package provides **exactly-once intent** at enqueue for a given dedup key within tenant scope).

## Core types

| Type | Role |
|------|------|
| `OutboxServiceInterface` | Enqueue, claim next pending, mark sent/failed, schedule retry |
| `OutboxStoreInterface` | Composite query + persist (split interfaces available) |
| `OutboxClockInterface` | UTC `now()` for transitions and lease checks |
| `InMemoryOutboxStore` | Tests and local simulations |

## Example (sketch)

```php
use Nexus\Outbox\Domain\OutboxEnqueueCommand;
use Nexus\Outbox\Services\InMemoryOutboxStore;
use Nexus\Outbox\Services\OutboxService;
use Nexus\Outbox\Services\SystemClock;
use Nexus\Outbox\ValueObjects\DedupKey;
use Nexus\Outbox\ValueObjects\EventTypeRef;
use Nexus\Outbox\ValueObjects\TenantId;

$tenantId = new TenantId('tenant-1');
$store = new InMemoryOutboxStore();
$clock = new SystemClock();
$outbox = new OutboxService($store, $store, $clock);

$now = $clock->now();
$leaseExpiresAt = $now->modify('+5 minutes');

$result = $outbox->enqueue(new OutboxEnqueueCommand(
    $tenantId,
    new DedupKey($streamEventId),
    new EventTypeRef($eventTypeFqcn),
    $payload,
    $metadata,
    $correlationId,
    $causationId,
    $now,
));

$claimed = $outbox->claimNextPending($tenantId, $leaseExpiresAt);
if ($claimed !== null) {
    // publish to external bus, then:
    $outbox->markSent($tenantId, $claimed->id, $claimed->claimToken);
}
```

## Requirements

- PHP 8.3+

## Related documentation

- [EventStream bridge](docs/event-stream-bridge.md)
- [IMPLEMENTATION_SUMMARY.md](IMPLEMENTATION_SUMMARY.md)
