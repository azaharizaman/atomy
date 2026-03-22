# Nexus\Outbox (Layer 1) — transactional outbox model, publish lifecycle, and EventStream bridge

**Date:** 2026-03-22  
**Area:** Nexus monorepo — `packages/Outbox` (new Layer 1 atomic package)  
**Status:** Design (brainstorm approved); **not yet implemented**  
**Related:** [ARCHITECTURE.md](../../project/ARCHITECTURE.md), [NEXUS_PACKAGES_REFERENCE.md](../../project/NEXUS_PACKAGES_REFERENCE.md), `Nexus\EventStream`, `Nexus\Idempotency`

---

## 1. Summary

Introduce **`Nexus\Outbox`**, a **framework-agnostic** Layer 1 package that defines:

- A **standard outbox message model** (metadata, payload identity, tenant scope).
- A **publishing state machine** with explicit statuses and **valid transitions** (e.g. pending → sending → sent | failed, plus controlled retry).
- **Contracts** for enqueue, query, claim/lease, and lifecycle completion—**no** database, queue, or HTTP implementations in the package.
- **Honest delivery semantics:** **exactly-once intent** at the **domain boundary** (one committed enqueue per logical integration emission within a transaction + dedup keys); **at-least-once** over the wire with **idempotent consumers** documented as a consumer responsibility.

Include a **first-class specification** for integrating with **`Nexus\EventStream`**: when a domain uses event sourcing, **append to the event store and enqueue an outbox row in the same application transaction**, with a **normative field mapping** from `EventInterface` into outbox metadata/payload so downstream integrations (Notifier, webhooks, analytics) stay consistent.

---

## 2. Problem

Today, integration-style emissions (notifications, webhooks, analytics) risk **ad-hoc plumbing**: duplicate publishes, unclear retry semantics, weak correlation with domain actions, and split-brain between **sourced events** (EventStream) and **integration fan-out**. A shared Layer 1 **vocabulary and invariants** reduces drift across modules and makes L2/L3 implementations testable against one contract.

---

## 3. Goals

1. **Layer 1 purity:** `declare(strict_types=1);`, `final readonly` services where appropriate, **no** `Illuminate\*`, **no** migrations/Eloquent; persistence only via **`Contracts/`**.
2. **Tenant isolation:** Every query and mutation is **tenant-scoped**; mismatches use **not-found or domain-specific** exceptions—**no** cross-tenant existence leaks (align with AGENTS.md multi-tenancy guidance).
3. **State machine:** Documented statuses and **illegal transition** exceptions; optional **claim token** / lease id for safe `sending → sent` (pattern parity with `Nexus\Idempotency` `AttemptToken` lessons).
4. **Metadata:** Support **correlation** and **causation** IDs (and optional aggregates: `streamId`, `eventId`, `eventType`) as **bounded strings** / value objects with shared validation approach (e.g. internal bounded-string helper like Idempotency).
5. **CQRS-style contracts:** Split **enqueue**, **query**, and **lifecycle/claim** concerns (mirror `IdempotencyQueryInterface` / `IdempotencyPersistInterface`) so L3 adapters map cleanly to SQL and workers.
6. **EventStream bridge (normative):** Specify **dual-write ordering**, **dedup key strategy**, and **mapping** from `Nexus\EventStream\Contracts\EventInterface` into outbox enqueue inputs (see §8).

---

## 4. Non-goals (v1 package)

- Message **transport** (SQS, Kafka, Redis, Laravel queue) — L3 only.
- **Running workers**, scheduling, or backoff policies — L2/L3; L1 may define **policy value objects** (max attempts, TTL) without executing them.
- Replacing **`Nexus\EventStream`** for sourcing or replay — outbox is **integration fan-out**, not an event store.
- **Guaranteed exactly-once delivery** end-to-end — explicitly out of scope; document **at-least-once** + idempotent handlers.

---

## 5. Layering and dependencies

| Layer | Responsibility |
|--------|----------------|
| **L1 `Nexus\Outbox`** | VOs, enums, domain records, transition rules, service façade, exceptions, **in-memory** store for tests. |
| **L2 orchestrator** | Coordinates **EventStream append + outbox enqueue** in one transactional boundary **when both are used**; may host a small **mapper** from `EventInterface` → outbox enqueue DTO (recommended default to avoid unnecessary L1↔L1 coupling). |
| **L3 adapter** | DB schema, unique indexes, transactional `enqueue` with business commit, worker claim loop, publisher to external bus. |

**Dependency recommendation (v1):** Core `nexus/outbox` **composer `require`** only `php` (^8.3) (and dev test tools), matching `nexus/idempotency`. The **EventStream bridge** is **specified** in §8; the **reference mapper implementation** may live in **L2** or, if the repo later wants a reusable L1 helper, a **separate** `nexus/outbox-event-stream-bridge` package **or** an explicit `require` on `nexus/event-stream`—decision deferred to the implementation plan to avoid pulling `symfony/uid` / `psr/log` into all Outbox consumers unnecessarily.

---

## 6. Core domain concepts

| Concept | Meaning |
|--------|---------|
| **Outbox message** | A durable intent to deliver an **integration payload** to external subscribers, stored atomically with business state. |
| **Status** | e.g. `Pending`, `Sending` (optional but recommended for claim visibility), `Sent`, `Failed` — exact enum names in implementation. |
| **Dedup / identity** | Tenant-scoped key (and optional **payload fingerprint**) so replays and duplicate appends do not double-publish effects (align mentally with Idempotency; different lifecycle). |
| **Claim / lease** | Worker obtains exclusive right to publish a `Pending` row, moving it to `Sending` with an opaque **claim token** and **expiry**. |
| **Completion** | `Sending` → `Sent` or `Failed` **only** with valid token; failures record **reason** (bounded) for ops and replay analysis. |

### 6.1 Clock and timestamps

Use an **`OutboxClockInterface`** (UTC `DateTimeImmutable`) for “now” in domain logic, mirroring `IdempotencyClockInterface`, so tests are deterministic.

---

## 7. Contracts (illustrative names — finalize in implementation plan)

- **`OutboxEnqueueInterface`:** append a new message (or no-op / conflict per dedup policy).
- **`OutboxQueryInterface`:** list by status, fetch by id (tenant-scoped), peek for workers.
- **`OutboxLifecycleInterface`:** `claimPending`, `markSent`, `markFailed`, `scheduleRetry` (if retry is v1), with **token** validation where applicable.

**Composite:** `OutboxStoreInterface` extends split interfaces **or** is implemented by adapters as a single class—follow Idempotency’s established pattern.

---

## 8. EventStream bridge (normative)

### 8.1 When to use

Use **both** EventStream and Outbox when:

- The **system of record** for a critical aggregate is the **append-only stream** (per EventStream README), **and**
- You must **notify** or **replicate** to external systems that **do not** read the stream directly.

### 8.2 Transactional ordering

Within **one** database transaction (L3):

1. **`EventStoreInterface::append` (or `appendBatch`)** succeeds for the domain event(s).
2. **`OutboxEnqueueInterface::enqueue`** persists the integration message(s) **in the same transaction**.

If either fails, **rollback both**. Do **not** publish to external buses inside this transaction.

**Rationale:** EventStream’s `EventPublisherInterface` is for **in-process** publish after store; the **outbox** is the **durable handoff** for **async integration**. L2/L3 must ensure the outbox row exists whenever the sourced event is committed.

### 8.3 Mapping: `EventInterface` → outbox enqueue input

Use the following **minimum** mapping (extend with `metadata` as needed, bounded):

| Outbox / integration field | Source on `EventInterface` |
|----------------------------|----------------------------|
| Tenant | `getTenantId()` |
| Correlation | `getCorrelationId()` (nullable → omit or generate per orchestrator policy; document tenant rules) |
| Causation | `getCausationId()` |
| Stable integration dedup key | **`getEventId()`** (ULID) — **preferred** primary key for “this stream event → one integration fan-out row” |
| Logical type | `getEventType()` (FQCN or versioned type string—document canonical choice) |
| Payload for integrators | `getPayload()` **plus** routing context: `getAggregateId()`, `getVersion()`, `getOccurredAt()`, `getUserId()` as structured envelope (L2/L3 serializer) |
| Stream context (optional metadata) | `getMetadata()` merged **without** overwriting reserved outbox keys |

### 8.4 Idempotency and duplicates

- **Uniqueness (L3):** enforce **tenant + dedup key** (e.g. `event_id`) **unique** on outbox table so duplicate append attempts in the same or retried transaction do not create second rows.
- **Consumer side:** handlers should tolerate **at-least-once** delivery; prefer **`Nexus\Idempotency`** at **ingress** APIs where the integration is command-shaped.

### 8.5 Non-leakage

Outbox **query APIs** remain **tenant-scoped**; failed lookups for wrong tenant behave like **not found** (consistent with other Nexus packages).

---

## 9. Testing strategy (L1)

- Unit tests: transition matrix, token mismatch, tenant mismatch, clock injection.
- `InMemoryOutboxStore` (or equivalent) covering enqueue/claim/complete races at the PHP level (logical concurrency).

---

## 10. Documentation deliverables (when implemented)

- `packages/Outbox/README.md` — purpose, non-goals, delivery semantics, example enqueue/claim flow.
- `packages/Outbox/IMPLEMENTATION_SUMMARY.md` — decisions, invariants, relation to Idempotency/EventStream.
- Update **`docs/project/NEXUS_PACKAGES_REFERENCE.md`** and **ARCHITECTURE.md §2 catalog** with `Nexus\Outbox`.
- Optional: `packages/Outbox/docs/event-stream-bridge.md` — copy of §8 for package-local discovery.

---

## 11. Open decisions (for implementation plan)

1. Whether **`Sending`** is v1 or v1 uses claim on `Pending` only (simpler, weaker observability).
2. Exact **retry** model: `Failed` → `Pending` with attempt counter vs dead-letter status in v1.
3. Whether **mapper code** ships in L2 only vs optional **`nexus/outbox-event-stream-bridge`** package.

---

## 12. Approval

- **EventStream bridge:** **Yes** — dual-write and `EventInterface` mapping are **normative** in this spec (§8).
- **Next step:** Invoke **writing-plans** to produce `docs/superpowers/plans/2026-03-22-nexus-outbox-implementation-plan.md` (or dated successor) with file-level tasks and composer boundaries.
