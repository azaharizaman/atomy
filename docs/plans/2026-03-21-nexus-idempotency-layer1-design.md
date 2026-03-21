# Nexus\Idempotency — Layer 1 Design (Brainstorm → Spec)

Date: 2026-03-21  
Status: **Proposed** (awaiting approval before implementation)  
Owner: Architecture + Implementation  
Related: Future `Nexus\Outbox` (TBD), `Nexus\PolicyEngine` (pattern reference)

## 1) Objective

Introduce **`Nexus\Idempotency`** as a Layer 1 package that defines **domain-level idempotency**: safe replay of mutating commands when clients or infrastructure retry requests, with **tenant-scoped** identity, **deterministic** decisions, and **explicit** conflict semantics—aligned with modern SaaS and ERP expectations (Stripe-style idempotency keys, AWS-style client tokens, financial “exactly-once intent” patterns).

**Layer 1 only:** contracts, value objects, domain services, and in-memory/reference implementations for tests. **No** database, migrations, HTTP, or framework code in this package.

## 2) Why This Exists (ERP / SaaS Reality)

| Problem | Without idempotency | With `Nexus\Idempotency` (L1 + L3 adapters later) |
|--------|---------------------|--------------------------------------------------|
| Network timeouts / double-submit | Duplicate invoices, POs, payments, stock moves | Same key → same **logical** outcome (replay) |
| Mobile / browser retries | Duplicate workflow transitions | Replay returns stored **result envelope** |
| Webhook / worker retries | Hard to distinguish “new” vs “retry” | First execution records fingerprint + result |
| Multi-tenant SaaS | Cross-tenant key collision | Keys are **always** scoped by `tenantId` + operation |

## 3) Scope Control (Critical)

### In scope (Layer 1 v1)

1. **Canonical idempotency request** — tenant, operation name/ref, client key, request fingerprint, optional metadata (non-PII at L1).
2. **Decision outcomes** — first execution vs safe replay vs conflict vs invalid input (see §6).
3. **Contracts** — `IdempotencyStoreInterface` (persistence abstracted), `FingerprintStrategyInterface` or explicit fingerprint input from caller, `IdempotencyServiceInterface` orchestrating begin/complete/fail.
4. **Immutability** — readonly VOs, `final readonly` services where applicable per Nexus standards.
5. **Domain exceptions** — missing key, malformed key, tenant mismatch on read, fingerprint conflict, expired record (if TTL modeled).
6. **Reference implementation** — `InMemoryIdempotencyStore` for unit tests (and local dev via adapters later).
7. **Documentation** — `README.md`, `REQUIREMENTS.md`, `IMPLEMENTATION_SUMMARY.md`, `VALUATION_MATRIX.md` per package standards.
8. **Unit tests** — happy path, replay, conflict, tenant isolation, clock/TTL edge cases.

### Explicitly out of scope (Layer 1)

- DB schema, Redis, migrations, Laravel/Eloquent (**Layer 3**).
- HTTP header parsing (`Idempotency-Key`) (**Layer 3**).
- Cross-service distributed locks (**Layer 2/3** or dedicated package).
- **Outbox** — eventual companion package; idempotency may **coordinate** with outbox in L2/L3 but L1 does not implement outbox.
- Automatic encryption of stored payloads — **policy belongs in L3**; L1 may store opaque `ResultEnvelope` / hashes only.

### On hold / future (document only)

- “Weak” idempotency (time-window only, no fingerprint) as optional profile.
- Hierarchical operation scopes (parent/child keys for saga steps).
- Metrics hooks (`Psr\Log\LoggerInterface` optional injection only if needed; avoid pulling heavy telemetry into L1).

## 4) Approaches Considered (2–3 Options)

### Option A — Thin guard + callable

Single method: `execute(IdempotencyContext $ctx, callable $fn): Result` with internal store lookup.

| Pros | Cons |
|------|------|
| Minimal API surface | Hides explicit state machine (begin/complete); harder to integrate with two-phase DB transactions |
| Fast to ship | Testing requires capturing side effects in callables |

**Verdict:** Acceptable for tiny apps; **too implicit for ERP-grade two-phase commit** with adapters.

### Option B — Explicit state machine + store (recommended)

Distinct phases:

1. **`begin`** — given `(tenant, operation, clientKey, fingerprint)` → `FIRST_USE` or `REPLAY` or `CONFLICT` or `EXPIRED`.
2. **`complete`** — persist **result envelope** + terminal status after domain work succeeds.
3. **`fail`** — mark failed (optional: allow retry with same key per policy).

Store holds: status, fingerprint hash, result envelope (opaque), created/completed timestamps, optional attempt count.

| Pros | Cons |
|------|------|
| Maps to DB transactions and saga steps | Slightly more boilerplate at call sites |
| Clear testing without callables | Requires discipline in L3 to call `complete`/`fail` |

**Verdict:** **Recommended** — matches Stripe-like semantics and ERP “posting” workflows.

### Option C — Event-sourced idempotency log

Append-only event log per key.

| Pros | Cons |
|------|------|
| Full audit trail inside package | Overkill for v1; overlaps `EventStream` / outbox directionally |
| | Heavier storage model |

**Verdict:** Defer; **Option B** suffices until proven otherwise.

## 5) Recommended Design (Option B) — Concepts

### 5.1 Identity & scoping

- **`TenantId`** — mandatory; same string rules as other Nexus packages (normalization policy documented in package).
- **`OperationRef`** — stable string naming mutating operation, e.g. `rfq.create`, `invoice.post`, `payment.capture`. Prevents accidental cross-endpoint replay.
- **`ClientKey`** — client-supplied idempotency key (normalized: trim, max length enforced in L1 validation).
- **Composite primary identity (logical):** `(tenantId, operationRef, clientKey)` — **never** key by client key alone.

### 5.2 Fingerprinting (intent equality)

- Caller supplies **`RequestFingerprint`** as a **precomputed hash** (e.g. SHA-256 hex) over a **canonical representation** of the command payload **produced by the application** (L2/L3), **or** L1 accepts raw `string $canonicalPayload` + pluggable `FingerprintHasherInterface` implemented without framework deps.
- **Rule:** Same key + **different** fingerprint ⇒ **`CONFLICT`** (domain exception), not silent success.
- **PII:** L1 should document that stores should persist **hash only**, not raw bodies (L3 enforces).

### 5.3 Record lifecycle

States (example enum):

- `PENDING` — reserved, work in progress (optional; see concurrency)
- `IN_PROGRESS` — optional, for long operations
- `COMPLETED` — result envelope stored; replays return success
- `FAILED` — terminal failure; policy defines whether retry allowed
- `EXPIRED` — TTL expired; new execution may be allowed or rejected (policy flag)

**TTL:** Configured via constructor policy object (`IdempotencyPolicy`) — default max key age, optional “never expire completed” for financial postings (configurable).

### 5.4 Result envelope

- **`ResultEnvelope`** — opaque serializable payload (JSON string or structured array) **defined by the domain**; L1 treats it as immutable blob for replay.
- **Metadata:** HTTP status / error code **should not** be the only stored artifact for ERP; prefer domain result DTO encoded by the adapter.

### 5.5 Concurrency (design for L3)

Layer 1 should document **expected adapter behavior**:

- First writer wins with **unique constraint** on `(tenant_id, operation, client_key)` in DB.
- `begin` may use short-lived lock or “INSERT pending” row; **L1** can model `tryBegin()` returning “already in progress” for duplicate parallel submits.

**L1** may expose:

- `IdempotencyReservation` — result of a successful `begin` that allows exactly one completion.

## 6) Decision Outcomes (API)

| Outcome | Meaning |
|-----------|--------|
| `FIRST_EXECUTION` | Key unused; caller must execute work and `complete()` |
| `REPLAY` | Safe replay; return stored `ResultEnvelope` |
| `CONFLICT` | Same key, different fingerprint |
| `IN_PROGRESS` | Another execution holds reservation (optional) |
| `INVALID` | Missing/invalid key, malformed operation, policy violation |

## 7) Contracts (Illustrative — final names in implementation)

- `Nexus\Idempotency\Contracts\IdempotencyServiceInterface`
- `Nexus\Idempotency\Contracts\IdempotencyStoreInterface`
- `Nexus\Idempotency\Contracts\IdempotencyClockInterface` (for testable time)
- Optional: `Nexus\Idempotency\Contracts\FingerprintHasherInterface`

**Services:**

- `IdempotencyService` (final readonly) — orchestrates begin/complete/fail.

## 8) Exceptions (Domain)

- `IdempotencyKeyMissingException`
- `IdempotencyKeyInvalidException`
- `IdempotencyFingerprintConflictException` (includes tenant + operation + key **without** leaking other tenant data)
- `IdempotencyTenantMismatchException`
- `IdempotencyRecordExpiredException`
- `IdempotencyCompletionException` (complete without begin, wrong state)

## 9) Multi-Tenancy & Security

- All store queries **must** include `tenantId` in the contract; evaluator never loads by key alone.
- Error messages **must not** reveal existence of records across tenants (align with 404-style philosophy for HTTP in L3).
- **Rate limiting / abuse** — not L1; document hooks for L3.

## 10) Alignment with Nexus Architecture

Per `docs/project/ARCHITECTURE.md`:

- Layer 1: pure PHP, no framework, no DB.
- `Contracts/` for persistence boundaries.
- CQRS-friendly: idempotency wraps **commands** only (document clearly).

## 11) Testing Strategy

- Unit tests with `InMemoryIdempotencyStore`.
- Tests: first success → replay returns same envelope; fingerprint mismatch → conflict; TTL expiry; tenant mismatch; concurrent begin (simulated in memory).
- **No** Laravel test harness in L1 package.

## 12) Non-Goals (v1)

- Cross-region idempotency federation.
- Global deduplication without tenant scope.
- Built-in encryption.

## 13) Next Steps After Approval

1. Implement package under `packages/Idempotency/` (or `packages/NexusIdempotency/` per repo naming).
2. Update `docs/project/NEXUS_PACKAGES_REFERENCE.md` with `Nexus\Idempotency`.
3. Invoke **writing-plans** skill for implementation checklist and PR-sized tasks.
4. After **Outbox** Layer 1 design: Layer 2 patterns for “command + outbox + idempotency” in orchestrators (separate doc).

---

## Approval

- [ ] Architecture / product owner approves this Layer 1 design  
- [ ] Date approved: _____________
