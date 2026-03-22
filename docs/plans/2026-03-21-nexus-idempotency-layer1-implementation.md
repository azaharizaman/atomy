# Nexus\Idempotency (Layer 1) Implementation Plan

> **For Claude / Codex:** REQUIRED SUB-SKILL: Use **superpowers:executing-plans** to implement this plan task-by-task (or **superpowers:subagent-driven-development** if executing interactively in one session).

**Goal:** Ship `packages/Idempotency/` as Layer 1 only: contracts, immutable domain model, `IdempotencyService` (begin / complete / fail), `InMemoryIdempotencyStore`, PHPUnit coverage, and package docs—aligned with `docs/plans/2026-03-21-nexus-idempotency-layer1-design.md` (Option B + anti-overlap rules).

**Architecture:** Mirror `packages/PolicyEngine/` layout (`src/Contracts`, `src/Domain`, `src/Services`, `src/Exceptions`, `src/ValueObjects`, `src/Enums`). No `Illuminate\*`, no DB. Persistence via `IdempotencyStoreInterface`; time via `IdempotencyClockInterface`. Composite identity: `(tenantId, operationRef, clientKey)`; fingerprint is opaque string (caller-precomputed hash recommended).

**Tech Stack:** PHP 8.3+, `declare(strict_types=1);`, PHPUnit 11, `final readonly` services, readonly promoted properties on VOs.

**Prerequisites (read once):**

- `docs/project/NEXUS_PACKAGES_REFERENCE.md` — **consult before starting** to avoid duplicating packages or interfaces (“Consult `NEXUS_PACKAGES_REFERENCE.md` to prevent duplication”).
- `docs/plans/2026-03-21-nexus-idempotency-layer1-design.md` — source of truth
- `docs/project/ARCHITECTURE.md` — Layer 1 rules
- `AGENTS.md` — mandates
- Reference package: `packages/PolicyEngine/` (`composer.json`, `src/`, `tests/Unit/`)

**Monorepo wiring:** Root `composer.json` uses explicit PSR-4 paths (not only `packages/*` discovery). You **must** add `Nexus\Idempotency\` and `Nexus\Idempotency\Tests\` entries like `PolicyEngine`.

**Verify commands (from repo root):**

```bash
composer dump-autoload
vendor/bin/phpunit -c packages/Idempotency/phpunit.xml
```

Expected after full implementation: PHPUnit **PASS**, **0** risky coverage gaps on core service paths.

---

## Task 0: Scaffold package skeleton

**Files:**

- Create: `packages/Idempotency/composer.json` (name `nexus/idempotency`, `php ^8.3`, autoload `Nexus\Idempotency\` → `src/`, dev autoload `Nexus\Idempotency\Tests\` → `tests/`)
- Create: `packages/Idempotency/phpunit.xml` (copy structure from `packages/PolicyEngine/phpunit.xml`; adjust test suite name; bootstrap `../../vendor/autoload.php`)
- Create: `packages/Idempotency/.gitignore` (align with PolicyEngine: `.phpunit.cache`, `build/` if coverage generated)
- Create: `packages/Idempotency/LICENSE` (MIT, match PolicyEngine if identical)
- Modify: `composer.json` (root) — add autoload + autoload-dev entries for `Nexus\Idempotency` and `Nexus\Idempotency\Tests`

**Step 1:** Add files only; no classes yet.

**Step 2:** Run `composer dump-autoload` from repo root.

Expected: completes with no errors.

**Step 3:** Commit

```bash
git add packages/Idempotency/composer.json packages/Idempotency/phpunit.xml packages/Idempotency/.gitignore packages/Idempotency/LICENSE composer.json
git commit -m "chore: scaffold Nexus Idempotency package skeleton"
```

---

## Task 1: Domain enums — record status & begin outcome

**Files:**

- Create: `packages/Idempotency/src/Enums/IdempotencyRecordStatus.php`
- Create: `packages/Idempotency/src/Enums/IdempotencyBeginOutcome.php` (or `BeginResult` enum naming — pick one and use consistently)

**Suggested values:**

- `IdempotencyRecordStatus`: `Pending`, `InProgress`, `Completed`, `Failed`, `Expired` (subset ok for v1 if documented)
- `IdempotencyBeginOutcome`: `FirstExecution`, `Replay`, `Conflict`, `InProgress`, `Invalid` (map design §6)

**Step 1:** Write failing test `packages/Idempotency/tests/Unit/Enums/IdempotencyRecordStatusTest.php` that asserts enum cases exist (smoke).

**Step 2:** Run `vendor/bin/phpunit -c packages/Idempotency/phpunit.xml --filter IdempotencyRecordStatusTest`

Expected: FAIL until enums exist.

**Step 3:** Implement enums.

**Step 4:** Run tests — PASS.

**Step 5:** Commit `feat(idempotency): add record status and begin outcome enums`

---

## Task 2: Value objects — TenantId, OperationRef, ClientKey, RequestFingerprint, ResultEnvelope

**Files:**

- Create: `packages/Idempotency/src/ValueObjects/TenantId.php`
- Create: `packages/Idempotency/src/ValueObjects/OperationRef.php`
- Create: `packages/Idempotency/src/ValueObjects/ClientKey.php`
- Create: `packages/Idempotency/src/ValueObjects/RequestFingerprint.php`
- Create: `packages/Idempotency/src/ValueObjects/ResultEnvelope.php`

**Rules:**

- `declare(strict_types=1);` everywhere
- Normalize: trim strings; reject empty after trim; **max length** enforced for `ClientKey` and `OperationRef` (constants, e.g. 256 / 128 — document in REQUIREMENTS)
- `RequestFingerprint`: opaque non-empty string (hex hash from caller)
- `ResultEnvelope`: wrap `string` (JSON or serialized domain DTO) **or** `array` — pick **one** immutable representation for v1 (recommend `string` only to avoid mutable arrays in readonly classes)

**Step 1:** Tests in `packages/Idempotency/tests/Unit/ValueObjects/` — empty string throws, trim works, equality via value.

**Step 2:** Implement VOs.

**Step 3:** PHPUnit PASS.

**Step 4:** Commit `feat(idempotency): add core value objects`

---

## Task 3: Domain exceptions

**Files:**

- Create: `packages/Idempotency/src/Exceptions/IdempotencyException.php` (base, optional)
- Create: `packages/Idempotency/src/Exceptions/IdempotencyKeyMissingException.php`
- Create: `packages/Idempotency/src/Exceptions/IdempotencyKeyInvalidException.php`
- Create: `packages/Idempotency/src/Exceptions/IdempotencyFingerprintConflictException.php`
- Create: `packages/Idempotency/src/Exceptions/IdempotencyTenantMismatchException.php`
- Create: `packages/Idempotency/src/Exceptions/IdempotencyRecordExpiredException.php`
- Create: `packages/Idempotency/src/Exceptions/IdempotencyCompletionException.php`

**Rules:** No sensitive payload in messages; tenant-safe wording (aligns with design §9).

**Step 1:** Unit test: each exception can be constructed with minimal context (smoke).

**Step 2:** Implement.

**Step 3:** Commit `feat(idempotency): add domain exceptions`

---

## Task 4: Domain records — `IdempotencyRecord`, `BeginDecision`, `IdempotencyPolicy`

**Files:**

- Create: `packages/Idempotency/src/Domain/IdempotencyRecord.php` (readonly — holds status, fingerprint, optional result envelope, timestamps, attempt count)
- Create: `packages/Idempotency/src/Domain/BeginDecision.php` (outcome + optional `IdempotencyRecord` / replay envelope)
- Create: `packages/Idempotency/src/Domain/IdempotencyPolicy.php` (readonly: TTL `?DateInterval` or seconds as int, `allowRetryAfterFail` bool, `expireCompleted` bool — **YAGNI**: minimal policy for v1)

**Step 1:** Unit tests for `IdempotencyPolicy` validation (e.g. negative TTL rejected).

**Step 2:** Implement.

**Step 3:** Commit `feat(idempotency): add domain record and policy objects`

---

## Task 5: Contracts

**Files:**

- Create: `packages/Idempotency/src/Contracts/IdempotencyClockInterface.php`

```php
<?php

declare(strict_types=1);

namespace Nexus\Idempotency\Contracts;

use DateTimeImmutable;

interface IdempotencyClockInterface
{
    public function now(): DateTimeImmutable;
}
```

- Create: `packages/Idempotency/src/Contracts/IdempotencyStoreInterface.php` — methods (exact signatures in implementation, but must support):
  - `findByKey(TenantId, OperationRef, ClientKey): ?IdempotencyRecord`
  - `tryReservePending(...)` or `saveInitial(...)` for first use
  - `markCompleted(...)`, `markFailed(...)`
  - Updates must be tenant-scoped

- Create: `packages/Idempotency/src/Contracts/IdempotencyServiceInterface.php` — `begin(...)`, `complete(...)`, `fail(...)`

**Step 1:** No test for interfaces alone; proceed to Task 6–7.

**Step 2:** Commit `feat(idempotency): add contracts`

---

## Task 6: `SystemClock` + `InMemoryIdempotencyStore`

**Files:**

- Create: `packages/Idempotency/src/Services/SystemClock.php` — implements `IdempotencyClockInterface` using `new DateTimeImmutable('now')` (or inject timezone **only if needed** — YAGNI: UTC document in README)

- Create: `packages/Idempotency/src/Services/InMemoryIdempotencyStore.php` — implements `IdempotencyStoreInterface`; array storage keyed by **JSON-encoding the tuple** `[tenantId, operationRef, clientKey]` with `json_encode(..., JSON_THROW_ON_ERROR)` so delimiter characters in segment values cannot collide (e.g. `|` inside a segment). (This is the same tuple shape that `InMemoryIdempotencyStore::compositeKey` will implement and that [README](packages/Idempotency/README.md) will describe—name the method in the plan only as forward reference, not as a prerequisite symbol.) Concurrent `begin` uses **`claimPending()`** (insert-if-absent) on `IdempotencyPersistInterface`; losers observe the winner’s row (see `IdempotencyService`).

**Step 1:** Tests `packages/Idempotency/tests/Unit/Services/InMemoryIdempotencyStoreTest.php`:

- save + find roundtrip
- tenant isolation: same client key different tenant = different rows
- conflict detection delegated to service layer OR store returns existing — **pick one** (recommend: store is dumb persistence; service compares fingerprint)

**Step 2:** Implement store **minimal** methods used by `IdempotencyService`.

**Step 3:** Commit `feat(idempotency): add system clock and in-memory store`

---

## Task 7: `IdempotencyService` (core) — TDD

**Files:**

- Create: `packages/Idempotency/src/Services/IdempotencyService.php` — `final readonly`, implements `IdempotencyServiceInterface`

**Required behaviors (tests first):**

1. **begin — first execution:** no record → create `Pending`/`InProgress`, outcome `FirstExecution`.
2. **begin — replay:** `Completed` + same fingerprint → `Replay` + same `ResultEnvelope`.
3. **begin — conflict:** existing record with **same** key scope but **different** fingerprint → throw `IdempotencyFingerprintConflictException` (or outcome `Conflict` if you use enum-only flow — **prefer exception** for failure paths per AGENTS.md).
4. **complete:** transitions to `Completed`, stores envelope.
5. **fail:** transitions to `Failed` per policy (`allowRetryAfterFail`).
6. **TTL expiry:** if policy says expired for **incomplete** record → `IdempotencyRecordExpiredException` or allow new reservation per design flag — **implement one policy** and test.

**Files:**

- Create: `packages/Idempotency/tests/Unit/Services/IdempotencyServiceTest.php`

**Step 1:** Write tests for cases 1–3 minimum (fail first).

**Step 2:** Run PHPUnit — FAIL.

**Step 3:** Implement service.

**Step 4:** PHPUnit — PASS.

**Step 5:** Commit `feat(idempotency): implement IdempotencyService with tests`

---

## Task 8: Documentation package artifacts

**Files:**

- Create: `packages/Idempotency/README.md` — overview, Layer 1 boundaries, anti-overlap pointer to design doc, usage example (begin → domain work → complete)
- Create: `packages/Idempotency/REQUIREMENTS.md` — functional requirements + fingerprint rules
- Create: `packages/Idempotency/IMPLEMENTATION_SUMMARY.md` — **living** checklist: update it incrementally after **each** relevant task or behavioral change (at minimum after Tasks **1–7** and any follow-up fixes) so status, surface area, and verification commands stay truthful.
- Create: `packages/Idempotency/VALUATION_MATRIX.md` — complexity/coverage notes (template like other packages)

**Step 1:** Fill `IMPLEMENTATION_SUMMARY.md` with honest initial status.

**Step 2:** Commit `docs(idempotency): add package documentation`.

**Ongoing:** Whenever implementation changes land, update `IMPLEMENTATION_SUMMARY.md` in the **same** change (or an immediate follow-up commit). Prefer pairing commits such as `docs(idempotency): update IMPLEMENTATION_SUMMARY` with the implementation commit so the summary always reflects current progress.

**Step 3:** After Tasks 1–7, ensure `IMPLEMENTATION_SUMMARY.md` documents shipped contracts, behaviors, and verification; repeat after any subsequent fix.

---

## Task 9: Nexus packages reference + design approval checkbox

**Files:**

- Modify: `docs/project/NEXUS_PACKAGES_REFERENCE.md` — add `Nexus\Idempotency` row (Layer 1, command idempotency + replay; not Outbox/EventStream)
- Modify: `docs/plans/2026-03-21-nexus-idempotency-layer1-design.md` — set final sign-off when implementation merges (optional in this PR)

**Step 1:** Follow existing PolicyEngine entry style.

**Step 2:** Commit `docs: reference Nexus Idempotency in package guide`

---

## Task 10: Final verification

**Step 1:** From repo root:

```bash
composer dump-autoload
vendor/bin/phpunit -c packages/Idempotency/phpunit.xml
```

Expected: **OK** (all tests pass).

**Step 2:** Optional coverage:

```bash
vendor/bin/phpunit -c packages/Idempotency/phpunit.xml --coverage-text
```

Target: **≥70%** lines on `src/Services` and `src/Domain` (align with project goals).

**Step 3:** Final commit if any fixes: `fix(idempotency): address review nits` or merge as-is.

---

## Explicit non-tasks (anti-overlap)

Do **not** add: Outbox types, EventStream, Messaging, AuditLogger calls, HTTP, Eloquent, Laravel service provider, or `Nexus\PolicyEngine` coupling inside this package.

---

## Execution handoff

Plan complete and saved to `docs/plans/2026-03-21-nexus-idempotency-layer1-implementation.md`.

**Two execution options:**

1. **Subagent-driven (this session)** — Use **superpowers:subagent-driven-development**: one subagent per task, review between tasks.

2. **Parallel session** — New session with **superpowers:executing-plans** for batch execution with checkpoints.

**Which approach do you want?**
