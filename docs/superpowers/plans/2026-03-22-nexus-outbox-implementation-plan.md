# Nexus\Outbox Layer 1 Implementation Plan

> **For agentic workers:** Use **subagent-driven-development** or execute tasks sequentially. Checkbox steps for tracking.

**Goal:** Ship `packages/Outbox` — transactional outbox domain model, publish lifecycle, split store contracts, in-memory adapter, tests, and documentation aligned with `2026-03-22-nexus-outbox-layer1-design.md`.

**Architecture:** Mirror `Nexus\Idempotency` (bounded VOs, composite JSON keys in memory store, `OutboxClockInterface` UTC, claim token for `Sending` → terminal states). No framework/DB in package.

**Tech stack:** PHP 8.3, PHPUnit 11.

---

### Task 1: Package skeleton

**Files:**
- Create: `packages/Outbox/composer.json`, `packages/Outbox/phpunit.xml`
- Modify: `composer.json` (root autoload + autoload-dev)

- [ ] Add PSR-4 `Nexus\Outbox\` → `packages/Outbox/src/`
- [ ] Run `composer dump-autoload`

---

### Task 2: Core domain + contracts + services + memory store

**Files:** Under `packages/Outbox/src/` (exceptions, internal validator, VOs, enums, domain records, contracts, `OutboxService`, `InMemoryOutboxStore`, `SystemClock`)

- [ ] Implement enqueue (dedup by tenant + `DedupKey`), `claimNextPending`, `markSent`, `markFailed`, `scheduleRetry`
- [ ] State machine: `Pending` → `Sending` → `Sent`|`Failed`; `Failed` → `Pending` (retry)

---

### Task 3: Unit tests

**Files:** `packages/Outbox/tests/Unit/**`

- [ ] Enqueue duplicate vs first; tenant mismatch on completion; invalid transitions; expired claim; token mismatch

---

### Task 4: Documentation + catalog

**Files:**
- `packages/Outbox/README.md`, `packages/Outbox/IMPLEMENTATION_SUMMARY.md`, `packages/Outbox/docs/event-stream-bridge.md`
- `docs/project/NEXUS_PACKAGES_REFERENCE.md`, `docs/project/ARCHITECTURE.md` (Foundation / Integration table rows)

---

### Task 5: Verification

- [ ] `cd packages/Outbox && ../../vendor/bin/phpunit`
- [ ] `phpstan` if configured at package level (optional)
