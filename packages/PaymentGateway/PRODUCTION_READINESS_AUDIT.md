# Nexus\PaymentGateway — Production Readiness Audit & Mitigation Plan

**Package:** `nexus/payment-gateway`  
**Audience:** Maintainers, adapter implementers (Laravel/Symfony), reviewers  
**Last Updated:** 2025-12-24  
**Scope:** `packages/PaymentGateway` (atomic package) — architecture compliance + production hardening

---

## 1. Purpose

This document consolidates the current audit findings for `Nexus\PaymentGateway` and provides a comprehensive, prioritized mitigation plan to reach “production-level ready” (security, correctness, operability, dependency hygiene), while staying compliant with Nexus architecture rules:

- **Framework-agnostic** (no Laravel/Symfony dependencies in `packages/`)
- **Stateless services** (long-lived state externalized via interfaces)
- **Contract-driven** (dependencies are interfaces; consumers bind implementations)
- **Boundary compliant** with the Payment suite decomposition (no business workflows, no reconciliation)

This is intentionally a **package-local** report. It is not a replacement for:
- `REQUIREMENTS.md` (what must be built)
- `IMPLEMENTATION_SUMMARY.md` (progress tracking)
- suite-level docs under `docs/` (decomposition and package boundaries)

---

## 2. Executive Summary

### 2.1 Boundary compliance

From a package decomposition standpoint, the package is *directionally compliant*: it focuses on gateway integration concerns (authorization/capture/refund/tokenization/webhooks) and does not attempt to implement payment orchestration workflows.

### 2.2 Production-readiness gaps (high level)

The key blockers to production readiness are:

- **Webhook safety**: event-type normalization has unsafe defaults (risk of false-positive state transitions), and **duplicate/replay protection is missing**.
- **Idempotency**: requirements specify idempotency for gateway calls, but operational wiring is missing.
- **Dependency contract drift**: `composer.json` does not accurately represent runtime dependencies used by the code/tests; one hard dependency appears unused.
- **PCI/SAQ-A posture**: DTO shapes and tokenization flows can encourage server-side handling of PAN/CVV unless guarded and documented.
- **Error classification + operability**: base gateway error mapping is too coarse; observability hooks are incomplete.

---

## 3. Audit Scope & Evidence

### 3.1 In-scope

- `src/Services/*`, `src/Webhooks/*`, `src/ValueObjects/*`, `src/Contracts/*`, `src/Enums/*`, `src/Events/*`
- `composer.json` dependency contract
- Unit tests in `tests/` for behavioral expectations and dependency usage
- Requirements mapping to `REQUIREMENTS.md`

### 3.2 Out-of-scope

- Adapter implementations in `adapters/` (framework glue, storage, HTTP clients)
- `Nexus\Payment` orchestration and business workflows
- Bank rails and recurring billing (explicitly out-of-scope per `REQUIREMENTS.md`)

---

## 4. Risk Register (P0/P1/P2)

> **Priority meanings**
> - **P0**: Production blockers (security/correctness). Must be fixed before any real deployment.
> - **P1**: Required for reliable operations at scale, but can be scheduled after P0.
> - **P2**: Completeness / enhancements.

### 4.1 P0 — Correctness & Security

#### P0-A — Webhook replay / duplicate protection is missing

- **Requirement(s):** `GW-065`, `GW-REL-001` (webhooks are also a delivery channel that must be safely deduped)
- **Observed risk:** Payment processors routinely retry webhooks; without dedupe, the same event may be processed multiple times, causing repeated downstream side-effects.
- **Mitigation:**
  - Introduce a **Webhook Deduplication** capability with externalized storage.
  - Use a stable dedupe key such as `(tenantId, provider, webhookEventId)`.
  - Store and check a dedupe record before emitting/processing.
- **Acceptance criteria:**
  - Same webhook event delivered N times results in a single processed outcome.
  - Dedupe check is atomic under concurrency.

#### P0-B — Unsafe webhook event mapping defaults

- **Requirement(s):** `GW-061`..`GW-064`, `GW-066`
- **Observed risk:** Unknown provider event types defaulting to a valid internal event (e.g., “created”) can silently misclassify a webhook.
- **Mitigation:**
  - Add an explicit “unknown/unmapped” classification (enum case or equivalent).
  - Treat unmapped events as **non-actionable** by default:
    - emit a telemetry/audit signal
    - optionally expose the raw provider type for adapters to extend mapping
  - Require provider-specific handlers to map types explicitly.
- **Acceptance criteria:**
  - Unknown provider event types never map to a “success-looking” internal event.
  - Unmapped events are trackable, not silently accepted.

#### P0-C — Webhook authenticity verification policy gaps

- **Requirement(s):** `GW-060`, `GW-SEC-003`
- **Observed risk:** Verification exists, but policy needs to be explicit and testable for:
  - missing signature headers
  - timestamp tolerance (anti-replay)
  - payload canonicalization rules
- **Mitigation:**
  - Define verification policy in contracts and enforce consistent behavior.
  - Ensure verification failures are **fail-closed**.
- **Acceptance criteria:**
  - Invalid/missing signatures fail deterministically.
  - Replay attempts outside tolerance are rejected (where provider supports timestamped signatures).

#### P0-D — Idempotency is not wired into gateway operations

- **Requirement(s):** `GW-REL-001` (P0)
- **Observed risk:** DTOs/interfaces suggest idempotency exists, but the operational flow does not reliably enforce it.
- **Mitigation:**
  - Define an idempotency key strategy per operation:
    - authorization, capture, refund, void, tokenization
  - Idempotency storage must be externalized:
    - `(tenantId, provider, operation, idempotencyKey) -> stored result / status`
  - Gateways must pass idempotency keys to providers (where supported) and locally memoize results.
- **Acceptance criteria:**
  - Retried requests with the same idempotency key return the same result.
  - Concurrent identical requests do not double-charge.

#### P0-E — PCI / SAQ-A posture needs guardrails

- **Requirement(s):** `GW-031`, `GW-032`, `GW-SEC-001`, `GW-SEC-002`, `GW-SEC-006`
- **Observed risk:** Even if the code intends “token-only storage”, DTO shapes and service usage can encourage server-side raw card handling.
- **Mitigation (policy + design):**
  - Make the **preferred path** explicit: client-side tokenization / gateway-hosted fields → server receives only gateway token.
  - If server-side tokenization is supported for some deployments, it must be:
    - clearly labeled “high PCI scope”
    - segregated behind explicit interfaces and adapter-layer enforcement
    - paired with strict logging redaction requirements
  - Document and enforce: **never store PAN/CVV**, never log them, never emit them in events.
- **Acceptance criteria:**
  - Public APIs strongly discourage raw PAN/CVV handling.
  - Tests assert PAN/CVV are never logged and never persisted.

---

### 4.2 P0 — Dependency Contract Hygiene

#### P0-F — `composer.json` dependency drift

- **Requirement(s):** not a numbered requirement, but required for production quality.
- **Observed risk:** The declared dependency set does not reflect what the package uses, and includes at least one dependency with no runtime usage.
- **Mitigation:**
  - Update `composer.json` to:
    - **remove unused hard dependencies**
    - **add required dependencies** that are used in code/tests (e.g., PSR event dispatcher, tenant context contract package)
  - Keep dependencies minimal and interface-based.
- **Acceptance criteria:**
  - No unused required dependencies.
  - All runtime interfaces referenced are satisfiable by installed packages.

---

### 4.3 P1 — Reliability, Operability, and Extensibility

#### P1-A — Retry/backoff and circuit breaker integration strategy

- **Requirement(s):** `GW-REL-002` (P0), `GW-REL-003` (P1)
- **Observed risk:** Network flakiness is common; without a consistent retry and circuit breaker strategy, gateways will be unreliable.
- **Mitigation (architecture-aligned):**
  - Prefer using the existing Nexus package intended for external calls (e.g., Connector) *if* it is truly part of the agreed design for this package.
  - Otherwise, define minimal contracts for retry/circuit breaker in `PaymentGateway` and let the consuming application bind implementations.
- **Acceptance criteria:**
  - Retry/backoff behavior is deterministic and unit-tested.
  - Circuit breaker trips on repeated failures and recovers safely.

#### P1-B — Gateway instantiation must be DI-friendly

- **Requirement(s):** `GW-002`, `GW-004`
- **Observed risk:** Direct `new` construction patterns make production integration harder (HTTP client, credentials, telemetry, retry policies).
- **Mitigation:**
  - Replace direct instantiation with a **factory interface** or registry that resolves gateway instances via DI.
- **Acceptance criteria:**
  - Consumers can bind concrete gateway implementations without modifying package code.

#### P1-C — Error classification is too coarse

- **Requirement(s):** `GW-005`, `GW-096`
- **Observed risk:** Collapsing many failures into a “network error” reduces recovery quality and observability.
- **Mitigation:**
  - Improve exception-to-error classification:
    - auth errors, validation errors, rate limits, network timeouts, provider declines
  - Ensure classification does not leak sensitive payloads.
- **Acceptance criteria:**
  - Unit tests cover common failure classes and mapping.

#### P1-D — Webhook processing status tracking

- **Requirement(s):** `GW-067`
- **Observed risk:** Without status tracking, operations cannot debug stuck/repeated webhooks.
- **Mitigation:**
  - Introduce a storage contract for webhook processing receipts (received/verified/deduped/processed/failed) keyed by event ID.
- **Acceptance criteria:**
  - Operators can query whether an event was processed and why it failed.

---

### 4.4 P2 — Completeness

- Gateway selection strategies (`GW-003`)
- Multi-partial captures (`GW-022`) and advanced refund workflows (`GW-042`, `GW-045`)
- Chargeback evidence submission interface (`GW-052`)
- Provider coverage expansion (`GW-062`..`GW-064`, `GW-080+`, etc.)

---

## 5. Mitigation Roadmap (Implementation Sequence)

### 5.1 Phase 0 (P0) — Safety first

1) **Fix dependency contract drift** (composer alignment)
- Ensure required packages are declared; remove unused hard dependencies.

2) **Webhook event normalization hardening**
- Add explicit unknown/unmapped handling.
- Ensure default behavior is non-actionable + observable.

3) **Webhook dedupe/replay protection**
- Introduce a storage-backed dedupe mechanism.
- Add tests for duplicate deliveries and concurrent processing.

4) **Idempotency wiring for gateway operations**
- Use `IdempotencyManagerInterface` consistently across authorization/capture/refund/void/tokenization.

5) **PCI posture guardrails**
- Update docs and public API guidance to prefer token-only flows.
- Add tests to prevent accidental logging/persistence of raw card data.

### 5.2 Phase 1 (P1) — Operability and scale

- Retry/backoff strategy + circuit breaker policy
- DI-friendly gateway factories
- Error classification improvements
- Webhook processing status tracking

### 5.3 Phase 2 (P2) — Feature completion

- Expand providers + advanced capabilities as per `REQUIREMENTS.md`.

---

## 6. Test Plan Additions

Add/extend unit tests to cover:

- **Webhook dedupe**: same event N times → single processing
- **Webhook replay**: timestamp tolerance (where applicable)
- **Event mapping**: unknown provider types → unmapped outcome (never mapped to “created/succeeded”)
- **Idempotency**: repeated requests with same key return same result
- **No sensitive logging**: PAN/CVV never logged, never emitted
- **Error mapping**: representative provider/transport failures classified correctly

---

## 7. Documentation Updates (After Fixes)

- Update `REQUIREMENTS.md` statuses for implemented items (especially `GW-060`, `GW-065`, `GW-REL-001`, and `GW-SEC-*`).
- Add a short section to `README.md` clarifying:
  - token-first integration path (SAQ-A posture)
  - webhook verification/dedup expectations
  - adapter responsibilities (where secrets/storage live)

---

## 8. Appendix — Requirements Coverage Map (Recommended)

When implementing items in this audit, update `REQUIREMENTS.md` as follows:

- **Webhook safety** → `GW-060`, `GW-065`, `GW-066`, `GW-067`, `GW-SEC-003`
- **Idempotency** → `GW-REL-001`
- **PCI posture** → `GW-031`, `GW-032`, `GW-SEC-001`, `GW-SEC-002`, `GW-SEC-006`
- **Retry/backoff** → `GW-REL-002`
- **Circuit breaker** → `GW-REL-003`

---

## 9. Notes on Architectural Placement

- **Storage, encryption, and secret management** must be implemented by the consuming application (adapter layer) via interfaces like `CredentialStorageInterface` and `TokenStorageInterface`.
- **No workflows**: Payment authorization/capture coordination (business rules, state machines) belongs in `Nexus\Payment` (or orchestrators), not inside `PaymentGateway`.
- **No reconciliation**: settlement and reconciliation belong in the payment suite’s settlement/reconciliation components, not here.
