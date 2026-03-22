# Atomy-Q SaaS API — Idempotency Integration Design

**Date:** 2026-03-23  
**Status:** Draft (for implementation)  
**Related:** [2026-03-22-idempotency-laravel-adapter-design.md](./2026-03-22-idempotency-laravel-adapter-design.md) (Layer 3 adapter package)  
**Layer 1:** `nexus/idempotency` (`packages/Idempotency/`)

---

## 1. Purpose

Define how **Nexus\Idempotency** and its **Laravel adapter** are used by the **Atomy-Q API** (`apps/atomy-q/API`) and **WEB** (`apps/atomy-q/WEB`) so mutating operations can be safely retried without duplicate side effects.

This document is **application-scoped**. The adapter package (database store, middleware skeleton, service provider) is specified in the related adapter design; here we fix **Atomy-Q-specific** behavior: route scope, middleware order, tenant binding, HTTP replay shape, error JSON, WEB client behavior, and phased rollout of which endpoints require a key.

---

## 2. Decisions (locked)

| Decision | Choice |
|----------|--------|
| Header | `Idempotency-Key` (trimmed, non-empty, max length per `ClientKey::MAX_LENGTH` = 256) |
| Required vs optional | **Required** on every route that is opted into idempotency middleware |
| WEB rollout | **Same release** as API changes; no compatibility window |
| Breaking changes | **Accepted** (pre-production; no external deployments yet) |
| Tenant source | **`auth_tenant_id`** on the request (set by `JwtAuthenticate` from JWT `tenant_id`). **Not** from a client-controlled header for primary resolution |

---

## 3. Goals

- Deduplicate **command-level** retries for high-risk `POST`/`PUT`/`PATCH` operations using L1 semantics: `(tenantId, operationRef, clientKey)` + **request fingerprint**.
- Keep **tenant isolation**: composite store key and fingerprint checks prevent cross-tenant leakage when correctly wired.
- Return **identical HTTP responses** on replay (status + JSON body + essential headers), within the limits of stored envelope size (`ResultEnvelope::MAX_BYTES` = 65536).

## 4. Non-goals

- Idempotency on **unauthenticated** `auth/*` routes (login, refresh, etc.).
- Admin UI for idempotency rows.
- Redis-backed store in the first implementation (optional Phase 2 per adapter doc).
- Automatic idempotency for **all** mutating routes without an explicit route/middleware opt-in.

---

## 5. Architecture

```
WEB (axios) ── Idempotency-Key + JWT ──► Api/V1 routes
                                              │
                    jwt.auth ─► auth_user_id, auth_tenant_id
                    tenant   ─► ensures tenant present
                    idempotency ─► begin() / short-circuit replay or 409
                                              │
                    Controller ─► domain work ─► complete(fail) with AttemptToken
```

**Critical:** Idempotency middleware MUST run **after** `jwt.auth` and **after** `tenant` so `auth_tenant_id` is present and validated. Suggested stack for opted-in routes:

`jwt.auth` → `tenant` → `idempotency`

---

## 6. Layer boundaries

| Layer | Responsibility |
|-------|----------------|
| **L1** `Nexus\Idempotency` | `begin` / `complete` / `fail`; policy; no HTTP |
| **L3** Laravel adapter | `DatabaseIdempotencyStore`, clock, config, middleware that calls L1 and attaches `IdempotencyRequest` to the request |
| **Atomy-Q** | Route names → `OperationRef`, controller `complete`/`fail`, mapping domain success/failure to **HTTP replay envelope**, feature tests, WEB header injection |

---

## 7. Operation reference (`OperationRef`)

Use a **stable, unique string per HTTP operation**, not a free-form path snapshot.

**Convention:** `v1.<resource>.<action>` where `<action>` matches the controller action or route purpose.

Examples:

| Route | Suggested `operation_ref` |
|-------|---------------------------|
| `POST api/v1/rfqs` | `v1.rfqs.store` |
| `POST api/v1/rfqs/bulk-action` | `v1.rfqs.bulk-action` |
| `POST api/v1/rfqs/{rfqId}/invitations` | `v1.rfqs.invitations.store` |

Implementation note: prefer Laravel **`->name('...')`** on routes and derive `OperationRef` from `route()->getName()` in middleware when possible, with a denylist for unnamed routes.

---

## 8. Request fingerprint (`RequestFingerprint`)

Must be deterministic for the same logical command.

**JSON bodies:** Canonical representation: UTF-8, `json_encode` with **sorted object keys** (recursive) and no pretty-print; combine with **HTTP method** and **path pattern** (e.g. `api/v1/rfqs`, not the raw URL with query string unless specified).

**Multipart / file uploads:** Fingerprint **cannot** rely on full file bytes in middleware without buffering. For `multipart/form-data` routes:

- Either **exclude** those routes from Wave 1 and handle idempotency in a dedicated action with a fingerprint based on **tenant + stable file metadata** after upload validation, or
- Define an explicit rule: hash **non-file fields** + **client-provided file name + size** (if available) + method + route name.

**Wave 1 recommendation:** Apply required idempotency to **JSON** mutating routes first; treat `POST quote-submissions/upload` as **Wave 1b** once fingerprint rules are defined and tested.

---

## 9. HTTP replay envelope (`ResultEnvelope`)

L1 `ResultEnvelope` holds a **single non-empty string** (`ResultEnvelope` constructor), not a PHP array.

**Atomy-Q canonical JSON payload (UTF-8 string inside `ResultEnvelope`):**

```json
{
  "version": 1,
  "status": 201,
  "headers": {
    "Content-Type": "application/json"
  },
  "body": "<stringified JSON or raw string as returned to client>"
}
```

Rules:

- `version` is reserved for future format changes; middleware **must** deserialize v1 and build a Laravel `Response`.
- `body` is the **exact JSON string** (or error payload) returned to the client on first success, so replay is byte-stable for tests.
- If the natural response exceeds `ResultEnvelope::MAX_BYTES`, the controller **must not** store the full body; instead return an error or a minimal reference (product decision). Default: **fail closed** with a domain error if serialization would exceed the limit.

On **`complete()`**, controllers pass `new ResultEnvelope($jsonString)`.

On **replay**, middleware returns early with the decoded response without invoking the controller.

---

## 10. Error contract (Atomy-Q JSON)

Align with existing `{ "error": "..." }` style where possible; add a **machine-readable code** for clients and tests.

| Condition | HTTP | `error` (human) | `code` |
|-----------|------|-----------------|--------|
| Missing `Idempotency-Key` | 400 | Idempotency-Key header is required | `idempotency_key_required` |
| Blank / invalid key (VO validation) | 400 | Invalid Idempotency-Key | `idempotency_key_invalid` |
| `BeginOutcome::InProgress` | 409 | Request is already in progress | `idempotency_in_progress` |
| Fingerprint conflict (same key, different command) | 409 | Idempotency conflict | `idempotency_fingerprint_conflict` |
| Tenant not available (middleware mis-order) | 500 | Internal error | `idempotency_tenant_missing` (log + fix; should not occur if stack is correct) |

Optional: `Retry-After` header for `409` in-progress (seconds), if policy provides a sensible value.

**Note:** Today `TenantContext` returns **403** for missing tenant (`Tenant context required`). Idempotency does not change that; keys are only required on routes that already pass `tenant`.

---

## 11. Controller responsibilities

For opted-in actions:

1. Read `IdempotencyRequest` from request attributes (trait/helper from adapter).
2. If middleware already responded (replay / 409), the controller is **not** invoked — adapter must short-circuit.
3. On **FirstExecution**, run existing validation and domain logic.
4. On success: `complete(..., new ResultEnvelope($canonicalJson))`.
5. On failure after reservation: `fail(...)` then rethrow or map to JSON error **unless** the failure is an expected validation response you choose to persist (usually **do not** complete with 422; prefer `fail()` and let the client retry with a new key — document the chosen behavior per endpoint family).

Use `try` / `finally` or a single exit path so `fail()` is not skipped on exceptions when a pending record was claimed.

---

## 12. Route scope — phased

### Phase 1 (same release as WEB — required key)

Minimum set for **JSON** mutations (adjust names to match actual `api.php` route names once named):

| Method | Path (prefix `api/v1`) | Rationale |
|--------|------------------------|-----------|
| POST | `/rfqs` | Create RFQ |
| POST | `/rfqs/bulk-action` | Amplified duplicates |
| POST | `/rfqs/{rfqId}/invitations` | Duplicate invites |
| POST | `/rfqs/{rfqId}/invitations/{invId}/remind` | Duplicate reminders |
| POST | `/rfqs/{rfqId}/duplicate` | Duplicate creates |
| POST | `/rfq-templates` | Template create |
| POST | `/rfq-templates/{id}/duplicate` | Duplicate |
| POST | `/rfq-templates/{id}/apply` | Apply template |
| POST | `/projects` | Project create |
| POST | `/tasks` | Task create |

### Phase 1b (define fingerprint first)

| Method | Path | Notes |
|--------|------|------|
| POST | `/quote-submissions/upload` | Multipart — see §8 |

### Phase 2 (expand)

Approvals bulk actions, awards, handoffs `send`/`retry`, integrations `store`, account `subscription/change`, `payment-methods`, negotiation closes, comparison `final`, etc. Add per feature when duplicate risk justifies the overhead.

**Auth section** (`/auth/*`): **out of scope** for idempotency in this design.

---

## 13. WEB client (`apps/atomy-q/WEB`)

**Transport:** Central `api` axios instance (`src/lib/api.ts`).

**Behavior:**

- For every request to a **Phase 1** endpoint (method/path match), set header `Idempotency-Key` to a **new UUID v4** per **user gesture** (submit click), not per HTTP retry.
- On **axios retry** after network failure (if any) or **refresh replay** (`originalRequest`), **preserve** the same `Idempotency-Key` as the first attempt for that gesture. Implementation options:
  - Attach `idempotencyKey` on the request config (`config.headers` + custom `config.metadata` for the retry path), or
  - Use a request id stored on the config object the interceptor reads.

**Generated OpenAPI client:** If some calls bypass raw `api` and use `generated/api/client`, apply the same rule in a wrapper or regenerate with a custom hook — document which entry point is canonical for Phase 1.

**Tests:** At least one test that asserts the header is present for a representative mutation.

---

## 14. Testing — API

- **Feature:** Missing `Idempotency-Key` → 400 + `idempotency_key_required` on a Phase 1 route.
- **Feature:** Same key + same body → second request returns **same** status/body without double side effects (assert via DB counts or mocks).
- **Feature:** Same key + **different** body → `idempotency_fingerprint_conflict` (409).
- **Feature:** `InProgress` simulation or concurrent request → 409 (if testable without flakiness).

---

## 15. Operations

- Run adapter migration for `nexus_idempotency_records` (or published table name).
- Schedule `idempotency:cleanup` daily (per adapter doc) for expired rows.
- Clock: UTC for L1 policy transitions (adapter clock implementation).

---

## 16. Open items (pre-implementation)

1. **Route naming:** Add explicit `->name()` to Phase 1 routes if not already present so `OperationRef` is stable.
2. **Multipart:** Finalize fingerprint strategy for `quote-submissions/upload` before enabling required key on that route.
3. **OpenAPI / Scramble:** Document required header and error `code`s for Phase 1 operations.

---

## 17. References

- `packages/Idempotency/README.md`
- `apps/atomy-q/API/routes/api.php`
- `apps/atomy-q/API/app/Http/Middleware/JwtAuthenticate.php` (`auth_tenant_id`)
- `apps/atomy-q/API/app/Http/Middleware/TenantContext.php`
- `apps/atomy-q/WEB/src/lib/api.ts`
