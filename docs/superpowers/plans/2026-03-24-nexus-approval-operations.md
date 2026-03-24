# Nexus ApprovalOperations Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Deliver `Nexus\ApprovalOperations` (Layer 2 orchestrator), optional Laravel adapter, and Workflow L1 hardening per the approved design at `docs/superpowers/specs/2026-03-24-nexus-approval-operations-design.md`, without conflating operational approval with RFQ quote approval.

**Architecture:** Orchestrator-first: `ApprovalProcessCoordinator` composes `Nexus\Workflow` (instances, `ApprovalEngine`, escalation/delegation) and `Nexus\PolicyEngine` (`PolicyEngineInterface`) behind narrow ports; persistence via CQRS-split interfaces (`*QueryInterface` / `*PersistInterface`); tenant isolation with 404 for missing or cross-tenant resources; Idempotency/Notifier/Outbox at L3 boundaries.

**Tech Stack:** PHP 8.3, PHPUnit 11, `nexus/workflow`, `nexus/policy-engine`, Laravel 12 (L3 only), Composer path packages.

**Related skills:** @superpowers:subagent-driven-development, @superpowers:executing-plans, @superpowers:verification-before-completion, @superpowers:test-driven-development (for L1/L2 changes).

---

## Preconditions

- Read the full design spec: `docs/superpowers/specs/2026-03-24-nexus-approval-operations-design.md` (especially §2 glossary, §8 modules, §10 Atomy-Q).
- Prefer a dedicated git worktree for implementation (see @superpowers:using-git-worktrees).
- Do not add `Illuminate\*` to `packages/` or `orchestrators/` sources.

---

## Target file tree (incremental)

```
packages/Workflow/src/
  Exceptions/UnknownApprovalStrategyException.php   (new)
  Core/ApprovalEngine.php                             (modify)

orchestrators/ApprovalOperations/
  composer.json
  phpunit.xml.dist
  README.md
  IMPLEMENTATION_SUMMARY.md
  src/
    Contracts/
      ApprovalTemplateQueryInterface.php
      ApprovalTemplatePersistInterface.php
      ApprovalInstanceQueryInterface.php
      ApprovalInstancePersistInterface.php
      ApprovalCommentPersistInterface.php
    DTOs/
      ApprovalSubjectRef.php
      StartOperationalApprovalCommand.php
      RecordApprovalDecisionCommand.php
    Services/
      ApprovalTemplateResolver.php
      ApprovalProcessCoordinator.php
      ApprovalSlaViewBuilder.php
  tests/Unit/
    ApprovalTemplateResolverTest.php
    ApprovalProcessCoordinatorTest.php

adapters/Laravel/ApprovalOperations/                 (phase 4+)
  composer.json
  database/migrations/...
  src/...
  tests/...
```

Exact filenames may vary; keep responsibilities aligned with the spec §8 table.

---

## Phase 1 — Workflow: domain exception for unknown strategies

**Spec reference:** design §7 (Workflow row).

**Files:**
- Create: `packages/Workflow/src/Exceptions/UnknownApprovalStrategyException.php`
- Modify: `packages/Workflow/src/Core/ApprovalEngine.php`
- Test: `packages/Workflow/tests/Unit/Core/ApprovalEngineTest.php` (extend or create alongside existing ApprovalEngine tests)

- [ ] **Step 1: Write failing test** — Assert `canProceed()` with an unregistered strategy throws `UnknownApprovalStrategyException` (not `RuntimeException`).

- [ ] **Step 2: Run test** — From `packages/Workflow`: `composer install` then `./vendor/bin/phpunit tests/Unit/Core/ApprovalEngineTest.php` (adjust path if tests live under a different subfolder; use `glob` to locate `ApprovalEngineTest.php`).

- [ ] **Step 3: Implement** — Add `final readonly class UnknownApprovalStrategyException extends \DomainException` (or project-standard base if one exists under `Nexus\Workflow\Exceptions`); replace both `RuntimeException` throws in `ApprovalEngine::canProceed` and `shouldReject` with this exception; include strategy name in exception message for operators (avoid leaking tenant data).

- [ ] **Step 4: Run full Workflow unit tests** — `./vendor/bin/phpunit` in `packages/Workflow`.

- [ ] **Step 5: PHPStan** — `./vendor/bin/phpstan analyse src --level=max` if Workflow uses PHPStan.

- [ ] **Step 6: Commit** — `feat(workflow): use UnknownApprovalStrategyException in ApprovalEngine`

- [ ] **Step 7: Update Workflow package docs** — `packages/Workflow/README.md` or `IMPLEMENTATION_SUMMARY.md` note the exception change for L3 error mapping.

---

## Phase 2 — Orchestrator package scaffold

**Files:**
- Create: `orchestrators/ApprovalOperations/composer.json`
- Create: `orchestrators/ApprovalOperations/phpunit.xml.dist`
- Create: `orchestrators/ApprovalOperations/README.md` (purpose, dependencies, no framework)

**composer.json requirements (minimum):**
- `php: ^8.3`
- `nexus/workflow: *@dev`
- `nexus/policy-engine: *@dev`
- `psr/log: ^3.0` (if logging ports used)

**Autoload:** `Nexus\ApprovalOperations\` → `src/`

- [ ] **Step 1: Create composer.json** — Name: `nexus/approval-operations`. Match `orchestrators/SourcingOperations/composer.json` style.

- [ ] **Step 2: composer install** — Run from `orchestrators/ApprovalOperations` with monorepo path repositories (install from repo root or symlink packages per local convention).

- [ ] **Step 3: Add root integration** — If other orchestrators are registered in root `composer.json` `autoload` / `autoload-dev`, add:
  - `"Nexus\\ApprovalOperations\\": "orchestrators/ApprovalOperations/src/"`
  - `"Nexus\\ApprovalOperations\\Tests\\": "orchestrators/ApprovalOperations/tests/"`  
  And add `"nexus/approval-operations": "*@dev"` to root `require` if required by monorepo policy (mirror how `nexus/sourcing-operations` is wired).

- [ ] **Step 4: Commit** — `chore(approval-operations): add orchestrator package scaffold`

---

## Phase 3 — Contracts and DTOs

**Files (create):**
- `orchestrators/ApprovalOperations/src/DTOs/ApprovalSubjectRef.php`
- `orchestrators/ApprovalOperations/src/DTOs/StartOperationalApprovalCommand.php`
- `orchestrators/ApprovalOperations/src/DTOs/RecordApprovalDecisionCommand.php`
- `orchestrators/ApprovalOperations/src/Contracts/ApprovalTemplateQueryInterface.php`
- `orchestrators/ApprovalOperations/src/Contracts/ApprovalTemplatePersistInterface.php`
- `orchestrators/ApprovalOperations/src/Contracts/ApprovalInstanceQueryInterface.php`
- `orchestrators/ApprovalOperations/src/Contracts/ApprovalInstancePersistInterface.php`
- `orchestrators/ApprovalOperations/src/Contracts/ApprovalCommentPersistInterface.php`

**Rules:**
- `declare(strict_types=1);` everywhere.
- Value objects / DTOs use `readonly` where appropriate.
- `ApprovalSubjectRef` carries generic `subject_type` + `subject_id` (string or VO per existing Nexus conventions in other orchestrators).
- Tenant id appears on commands or as a separate VO parameter consistent with `Nexus\PolicyEngine` / Idempotency patterns — pick one approach and document in class docblocks.

- [ ] **Step 1: TDD for ApprovalSubjectRef** — Unit test normalization/validation (non-empty type, canonical id format if applicable).

- [ ] **Step 2: Implement DTOs and interfaces** — Methods return types explicit; no `object` type hints for dependencies.

- [ ] **Step 3: PHPUnit** — `cd orchestrators/ApprovalOperations && ./vendor/bin/phpunit`

- [ ] **Step 4: Commit** — `feat(approval-operations): add contracts and DTOs`

---

## Phase 4 — Services: template resolver and coordinator (incremental)

**Files:**
- Create: `orchestrators/ApprovalOperations/src/Services/ApprovalTemplateResolver.php`
- Create: `orchestrators/ApprovalOperations/src/Services/ApprovalProcessCoordinator.php`
- Create: `orchestrators/ApprovalOperations/src/Services/ApprovalSlaViewBuilder.php` (may stub read model until Workflow SLA ports are clear)

**Dependencies:** Inject **interfaces only** — e.g. `PolicyEngineInterface` from `Nexus\PolicyEngine\Contracts`, Workflow managers/engines as needed (discover actual Workflow entry points from `packages/Workflow/src` and research report paths; do not instantiate framework types).

**Coordinator responsibilities (first vertical slice):**
1. Resolve template for subject type (via `ApprovalTemplateQueryInterface`).
2. Build policy request / evaluate branch (PolicyEngine).
3. Create or transition Workflow instance (port or Workflow service — align with existing Workflow usage in `orchestrators/ProcurementOperations` or similar if present).

- [ ] **Step 1: Spike read** — Grep the repo for `ApprovalEngine`, `WorkflowInstance`, or coordinator patterns to align method signatures (`SemanticSearch` / `grep`).

- [ ] **Step 2: Unit tests with mocks** — `ApprovalProcessCoordinatorTest` uses PHPUnit mocks for Workflow and PolicyEngine ports; assert tenant id is passed to every store/policy call.

- [ ] **Step 3: Implement minimal happy path** — `start()` returns a stable instance id DTO; document TODO for parallel stages if not in first slice.

- [ ] **Step 4: Wrong-tenant / missing** — Unit tests expect domain exceptions or not-found behavior that L3 maps to **404** (per design §3, §9).

- [ ] **Step 5: Commit** — `feat(approval-operations): add coordinator and template resolver`

---

## Phase 5 — Laravel adapter (reusable)

**Spec reference:** design §9.

**Files (typical; align with `adapters/Laravel/Idempotency/` layout):**
- `adapters/Laravel/ApprovalOperations/composer.json` — require `illuminate/database`, `nexus/approval-operations`, `nexus/laravel-idempotency-adapter` (if wiring idempotency middleware).
- `adapters/Laravel/ApprovalOperations/database/migrations/*_create_approval_templates_table.php`
- `adapters/Laravel/ApprovalOperations/database/migrations/*_create_approval_instances_table.php`
- `adapters/Laravel/ApprovalOperations/database/migrations/*_create_approval_comments_table.php`
- Eloquent models + repository classes implementing orchestrator `*QueryInterface` / `*PersistInterface`.
- `src/Providers/ApprovalOperationsAdapterServiceProvider.php`

**Rules:**
- Every query: `where('tenant_id', $tenantId)`.
- HTTP controllers live under `apps/atomy-q/API` or adapter’s `Http/` — follow `ProjectManagementOperations` / Idempotency adapter conventions.
- Mutating routes: idempotency `OperationRef` per route name (see existing middleware registration).

- [ ] **Step 1: Migrations** — UUID or bigint ids per existing Atomy-Q conventions; indexes on `(tenant_id, id)` and foreign keys as needed.

- [ ] **Step 2: Eloquent implementations** of orchestrator ports.

- [ ] **Step 3: Feature tests** — `apps/atomy-q/API/tests` or adapter `tests/Integration`: tenant A cannot read tenant B’s instance (404).

- [ ] **Step 4: Commit** — `feat(laravel): add ApprovalOperations adapter`

---

## Phase 6 — Atomy-Q product boundaries

**Spec reference:** design §10.

**Files (illustrative):**
- `apps/atomy-q/API/routes/api.php` — distinct route group for operational approvals vs RFQ quote flows.
- OpenAPI / Scramble tags — separate tag for operational approval.
- `apps/atomy-q/WEB` — new hook or route prefix only if product exposes generic approvals; **do not** rename RFQ “approvals” UI without an explicit migration task.

- [ ] **Step 1: Document route naming** in API changelog or `API_ENDPOINTS.md` if the project uses it.

- [ ] **Step 2: `npm run generate:api`** in `apps/atomy-q/WEB` when OpenAPI changes.

- [ ] **Step 3: Commit** — `feat(atomy-q): expose operational approval routes separate from RFQ`

---

## Phase 7 — Project documentation

**Files:**
- Modify: `docs/project/NEXUS_PACKAGES_REFERENCE.md` — add `Nexus\ApprovalOperations` row (per design §13).
- Modify: `docs/project/ARCHITECTURE.md` — orchestrator table cross-link if missing.
- Update: `orchestrators/ApprovalOperations/IMPLEMENTATION_SUMMARY.md` per AGENTS.md.

- [ ] **Step 1: Edit docs** — Keep entries consistent with existing table format.

- [ ] **Step 2: Commit** — `docs: register ApprovalOperations orchestrator`

---

## Verification matrix (before merge)

| Check | Command / action |
|--------|------------------|
| Workflow tests | `cd packages/Workflow && ./vendor/bin/phpunit` |
| Orchestrator tests | `cd orchestrators/ApprovalOperations && ./vendor/bin/phpunit` |
| Adapter tests | `cd adapters/Laravel/ApprovalOperations && ./vendor/bin/phpunit` (when present) |
| API tests | From `apps/atomy-q/API`: `php artisan test` or project-standard PHPUnit for HTTP suite |
| PHPStan | Per package, `composer` scripts if defined |

Use @superpowers:verification-before-completion before claiming the epic is done.

---

## Plan review loop

1. Self-review against design spec §§3–11 (goals, non-goals, tenant 404 policy).
2. Optional: dispatch code-reviewer / doc reviewer with paths to this plan + design spec.
3. If more than three review iterations, escalate to the human author.

---

## Execution handoff

**Plan complete and saved to `docs/superpowers/plans/2026-03-24-nexus-approval-operations.md`. Two execution options:**

1. **Subagent-driven (recommended)** — Fresh subagent per task; review between tasks (@superpowers:subagent-driven-development).
2. **Inline execution** — Run tasks in this session with checkpoints (@superpowers:executing-plans).

**Which approach do you want?**
