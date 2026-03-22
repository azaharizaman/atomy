# EventStream ↔ Outbox bridge

Normative integration pattern (see design spec `docs/superpowers/specs/2026-03-22-nexus-outbox-layer1-design.md` §8).

## Transactional ordering (Layer 3)

Within **one** database transaction:

1. `EventStoreInterface::append` (or `appendBatch`) succeeds.
2. `OutboxPersistInterface::enqueue` persists the integration message with dedup key = `EventInterface::getEventId()`.

Rollback both on failure. Do **not** call external brokers inside this transaction.

## Field mapping

| Outbox / integration field | `EventInterface` source |
|----------------------------|-------------------------|
| Tenant | `getTenantId()` |
| Correlation | `getCorrelationId()` |
| Causation | `getCausationId()` |
| Dedup key | **`getEventId()`** (preferred) |
| Logical type | `getEventType()` |
| Payload envelope | `getPayload()` plus routing: `getAggregateId()`, `getVersion()`, `getOccurredAt()`, `getUserId()` |
| Extra metadata | `getMetadata()` (merge without clobbering reserved outbox keys) |

## Consumers

Downstream delivery is **at-least-once**. Use idempotent handlers or `Nexus\Idempotency` on command-shaped ingress where applicable.
