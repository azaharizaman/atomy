# Nexus ApprovalOperations Implementation Plan
> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Finish the merged `Nexus\ApprovalOperations` feature by replacing the remaining stubs with real workflow, policy, SLA, and attachment behavior while keeping RFQ quote approvals separate from operational approvals.

**Architecture:** Treat the current merge as the baseline. The orchestrator, controller, routes, persistence scaffolding, and tenant-scoped exception mapping already exist; this plan only covers the missing runtime behavior and the unplanned follow-up capabilities. Keep the Layer 2 orchestrator-first shape, keep Laravel code in L3 adapters/API, and do not reintroduce RFQ-specific approval logic into the generic approval engine.

**Tech Stack:** PHP 8.3, PHPUnit 11, Laravel 12 API, `nexus/approval-operations`, `nexus/laravel-approval-operations-adapter`, `nexus/workflow`, `nexus/policy-engine`, `nexus/storage`, `nexus/idempotency`.

**Related skills:** @superpowers:subagent-driven-development, @superpowers:executing-plans, @superpowers:verification-before-completion, @superpowers:test-driven-development.

---

## Baseline State

These pieces are already implemented on `main`; do **not** recreate them:

- `Nexus\ApprovalOperations` package scaffold, DTOs, contracts, template resolver, and `ApprovalProcessCoordinator`.
- Laravel adapter package, migrations, Eloquent persistence, comment persistence, and the current workflow bridge stub.
- Atomy-Q API routes under `/api/v1/operational-approvals/*`.
- `OperationalApprovalController` and tenant-scoped 404 exception mapping.
- `ApprovalSlaViewBuilder` exists, but it still returns a stub read model.
- `AtomyPermissivePolicyEngine` exists, but it always allows starts.

This plan replaces the old pre-merge phase list. The deleted `docs/superpowers/plans/2026-03-24-nexus-approval-operations.md` is obsolete.

---

## Scope Map

### Already shipped

- Start/decision API surface for operational approvals.
- Template resolution by subject type.
- Persistence for approval templates, instances, and comments.
- Tenant-scoped not-found behavior.
- Idempotency middleware on mutating routes.

### Still stubbed or partial

- Workflow bridge only generates ULIDs and does not drive persisted workflow state.
- Policy engine binding is permissive and not policy-backed.
- SLA/read model is a placeholder.
- Attachment metadata is not modeled as a first-class approval capability.
- There is no dedicated inbox/list/read API for operational approvals yet.

### Not yet planned, but required by the spec

- Real attachment metadata storage and any storage-backed upload flow.
- Documentation/client regeneration after the new routes and read models stabilize.
- A clean obsolescence note for the old phase plan and any stale implementation-summary text.

---

## Task 1: Replace the workflow bridge stub with a real workflow bridge

**Files:**
- Modify: `adapters/Laravel/ApprovalOperations/src/Bridge/GeneratingOperationalWorkflowBridge.php`
- Modify: `adapters/Laravel/ApprovalOperations/src/Providers/ApprovalOperationsAdapterServiceProvider.php`
- Modify: `orchestrators/ApprovalOperations/src/Contracts/OperationalWorkflowBridgeInterface.php` only if the real workflow API needs a narrower or richer contract
- Modify: `apps/atomy-q/API/app/Providers/AppServiceProvider.php` only if the binding target changes
- Test: `apps/atomy-q/API/tests/Feature/OperationalApprovalApiTest.php`
- Test: `orchestrators/ApprovalOperations/tests/Unit/Services/ApprovalProcessCoordinatorTest.php`

- [ ] **Step 1: Write the failing test**

  Add a test that starts an operational approval and asserts the workflow instance is produced by a real bridge path, not just a generated correlation ID. Add a decision test that asserts the bridge apply path is invoked and the instance remains tenant-scoped.

- [ ] **Step 2: Run the targeted tests**

  Run:
  - `cd apps/atomy-q/API && php artisan test --filter=OperationalApprovalApiTest`
  - `cd orchestrators/ApprovalOperations && ./vendor/bin/phpunit tests/Unit/Services/ApprovalProcessCoordinatorTest.php`

  Expected: failures that point at the bridge stub and incomplete workflow state handling.

- [ ] **Step 3: Implement the bridge**

  Replace the ULID-only bridge with a workflow-backed implementation. Persist or resolve a workflow instance using the existing Workflow package entry points. Keep the bridge L3-only and do not move workflow state into the orchestrator.

- [ ] **Step 4: Re-run the tests**

  Run the same two commands again.

  Expected: both pass, with a real workflow instance identifier coming back from the API.

- [ ] **Step 5: Commit**

  Use a focused commit such as:

  ```bash
  git add adapters/Laravel/ApprovalOperations orchestrators/ApprovalOperations apps/atomy-q/API
  git commit -m "feat(approval-operations): wire workflow bridge"
  ```

---

## Task 2: Replace the permissive policy stub with a real approval policy path

**Files:**
- Modify: `apps/atomy-q/API/app/Services/ApprovalOperations/AtomyPermissivePolicyEngine.php`
- Modify: `apps/atomy-q/API/app/Providers/AppServiceProvider.php`
- Create or modify: `apps/atomy-q/API/app/Services/ApprovalOperations/*` for policy-backed lookup/configuration if needed
- Modify: `orchestrators/ApprovalOperations/src/Services/ApprovalProcessCoordinator.php` only if policy outcomes need additional handling
- Test: `orchestrators/ApprovalOperations/tests/Unit/Services/ApprovalProcessCoordinatorTest.php`
- Test: `apps/atomy-q/API/tests/Feature/OperationalApprovalApiTest.php`

- [ ] **Step 1: Write the failing test**

  Add tests that exercise both outcomes:
  - a template/policy combination that allows start
  - a template/policy combination that denies start and maps to a 403

  Keep the tests tenant-scoped and make the denial path explicit so the permissive stub cannot satisfy them.

- [ ] **Step 2: Run the tests**

  Run:
  - `cd apps/atomy-q/API && php artisan test --filter=OperationalApprovalApiTest`
  - `cd orchestrators/ApprovalOperations && ./vendor/bin/phpunit tests/Unit/Services/ApprovalProcessCoordinatorTest.php`

  Expected: denial-related failures until the stub is replaced.

- [ ] **Step 3: Implement the policy path**

  Replace the permissive `Allow` response with real policy lookup/evaluation. Keep the orchestrator as the coordinator, not the policy engine owner. If policy storage is still app-local, keep that in L3 and bind it through `AppServiceProvider`.

- [ ] **Step 4: Re-run the tests**

  Run the same commands again.

  Expected: allow/deny behavior matches the tests and the API returns 403 for denied starts.

- [ ] **Step 5: Commit**

  ```bash
  git add apps/atomy-q/API/app/Services/ApprovalOperations apps/atomy-q/API/app/Providers/AppServiceProvider.php orchestrators/ApprovalOperations
  git commit -m "feat(approval-operations): add policy-backed start evaluation"
  ```

---

## Task 3: Replace the SLA placeholder with a real operational read model

**Files:**
- Modify: `orchestrators/ApprovalOperations/src/Services/ApprovalSlaViewBuilder.php`
- Create: `orchestrators/ApprovalOperations/src/Contracts/ApprovalSlaQueryInterface.php` if a dedicated read port is needed
- Create or modify: `adapters/Laravel/ApprovalOperations/src/Persistence/*` for SLA-derived reads if needed
- Modify: `apps/atomy-q/API/app/Http/Controllers/Api/V1/OperationalApprovalController.php`
- Test: `apps/atomy-q/API/tests/Feature/OperationalApprovalApiTest.php`
- Test: `orchestrators/ApprovalOperations/tests/Unit/Services/ApprovalSlaViewBuilderTest.php` if it does not exist yet

- [ ] **Step 1: Write the failing test**

  Add a test that seeds an operational approval instance with due-date or deadline data and asserts `show()` returns real `due_at` and `seconds_remaining` values instead of `null`.

- [ ] **Step 2: Run the tests**

  Run:
  - `cd apps/atomy-q/API && php artisan test --filter=OperationalApprovalApiTest`
  - `cd orchestrators/ApprovalOperations && ./vendor/bin/phpunit`

  Expected: the SLA assertions fail while the builder remains stubbed.

- [ ] **Step 3: Implement the read model**

  Build the SLA view from persisted instance data and workflow/deadline context. Keep it tenant-scoped and make missing instances return the existing 404 path.

- [ ] **Step 4: Re-run the tests**

  Expected: `show()` returns non-null SLA fields for active instances and still returns 404 for cross-tenant access.

- [ ] **Step 5: Commit**

  ```bash
  git add orchestrators/ApprovalOperations apps/atomy-q/API
  git commit -m "feat(approval-operations): add SLA read model"
  ```

---

## Task 4: Add attachment metadata and storage-backed comment support

**Files:**
- Create: `orchestrators/ApprovalOperations/src/Contracts/ApprovalAttachmentMetadataPersistInterface.php`
- Create: `orchestrators/ApprovalOperations/src/DTOs/ApprovalAttachmentMetadata.php`
- Modify: `orchestrators/ApprovalOperations/src/Contracts/ApprovalCommentPersistInterface.php` only if the comment/attachment contract needs to be split
- Create: `adapters/Laravel/ApprovalOperations/database/migrations/*_create_operational_approval_attachments_table.php`
- Create: `adapters/Laravel/ApprovalOperations/src/Models/OperationalApprovalAttachment.php`
- Create: `adapters/Laravel/ApprovalOperations/src/Persistence/EloquentApprovalAttachmentPersist.php`
- Modify: `adapters/Laravel/ApprovalOperations/src/Providers/ApprovalOperationsAdapterServiceProvider.php`
- Modify: `apps/atomy-q/API/app/Http/Controllers/Api/V1/OperationalApprovalController.php` or create a dedicated comment controller if the route needs to stay narrow
- Test: `apps/atomy-q/API/tests/Feature/OperationalApprovalApiTest.php`

- [ ] **Step 1: Write the failing test**

  Add a test that records a comment with attachment metadata and asserts the attachment storage key is persisted tenant-scoped, not discarded.

- [ ] **Step 2: Run the tests**

  Run:
  - `cd apps/atomy-q/API && php artisan test --filter=OperationalApprovalApiTest`
  - `cd adapters/Laravel/ApprovalOperations && ./vendor/bin/phpunit` if adapter tests exist

  Expected: attachment-related assertions fail until the metadata table and persist path exist.

- [ ] **Step 3: Implement storage-backed metadata**

  Add the attachment metadata model/persistence and connect it to the comment/decision flow. Keep blobs in `Nexus\Storage`; only store metadata and storage keys here.

- [ ] **Step 4: Re-run the tests**

  Expected: comment and attachment metadata round-trip correctly, and tenant isolation still holds.

- [ ] **Step 5: Commit**

  ```bash
  git add orchestrators/ApprovalOperations adapters/Laravel/ApprovalOperations apps/atomy-q/API
  git commit -m "feat(approval-operations): add attachment metadata support"
  ```

---

## Task 5: Add the missing read/list surface for generic operational approvals

**Files:**
- Modify or create: `apps/atomy-q/API/app/Http/Controllers/Api/V1/OperationalApprovalController.php`
- Create: `apps/atomy-q/API/app/Http/Controllers/Api/V1/OperationalApprovalInboxController.php` if a separate inbox/list controller is cleaner
- Create or modify: `orchestrators/ApprovalOperations/src/DTOs/*` for inbox/list read models
- Create or modify: `adapters/Laravel/ApprovalOperations/src/Persistence/*` for list queries if needed
- Modify: `apps/atomy-q/API/routes/api.php`
- Test: `apps/atomy-q/API/tests/Feature/OperationalApprovalApiTest.php`

- [ ] **Step 1: Write the failing test**

  Add a list/inbox test that proves operational approvals can be queried as a tenant-scoped read surface and that cross-tenant access returns 404.

- [ ] **Step 2: Run the tests**

  Run:
  - `cd apps/atomy-q/API && php artisan test --filter=OperationalApprovalApiTest`

  Expected: the list/read assertions fail until the read DTOs and query path are in place.

- [ ] **Step 3: Implement the read surface**

  Add the smallest read surface that the product actually needs. Keep the generic operational approval naming distinct from RFQ quote approval resources.

- [ ] **Step 4: Re-run the tests**

  Expected: the list/inbox read surface works and remains tenant-scoped.

- [ ] **Step 5: Commit**

  ```bash
  git add apps/atomy-q/API orchestrators/ApprovalOperations adapters/Laravel/ApprovalOperations
  git commit -m "feat(approval-operations): add operational approval read surface"
  ```

---

## Task 6: Documentation and plan cleanup

**Files:**
- Modify: `docs/project/NEXUS_PACKAGES_REFERENCE.md`
- Modify: `docs/project/ARCHITECTURE.md`
- Modify: `orchestrators/ApprovalOperations/IMPLEMENTATION_SUMMARY.md`
- Modify: `apps/atomy-q/API/IMPLEMENTATION_SUMMARY.md` if the approval routes or status semantics changed
- Modify: any OpenAPI/Scramble docs that expose the new operational approval surface
- Modify: `docs/superpowers/specs/2026-03-24-nexus-approval-operations-design.md` only if the implementation-plan link should point at this new plan file

- [ ] **Step 1: Update the package reference docs**

  Add or refresh the `Nexus\ApprovalOperations` entry and make sure the docs no longer describe the shipped pieces as stubs.

- [ ] **Step 2: Update the implementation summary**

  Replace any follow-up text that is now obsolete and call out the remaining stubs only if they still exist after Tasks 1-5.

- [ ] **Step 3: Decide whether to retarget the spec link**

  If the old spec link should now point here, update the spec file. Otherwise, leave it alone and rely on this plan file as the current implementation reference.

- [ ] **Step 4: Commit**

  ```bash
  git add docs/project orchestrators/ApprovalOperations apps/atomy-q/API docs/superpowers/specs
  git commit -m "docs(approval-operations): refresh plan and implementation notes"
  ```

---

## Verification Matrix

Run these before claiming the work is complete:

- `cd orchestrators/ApprovalOperations && ./vendor/bin/phpunit`
- `cd adapters/Laravel/ApprovalOperations && ./vendor/bin/phpunit` if the adapter has its own suite
- `cd apps/atomy-q/API && php artisan test --filter=OperationalApprovalApiTest`
- `cd apps/atomy-q/API && php artisan test --filter=IdempotencyApiTest` to ensure the new route bindings still cooperate with idempotency middleware
- Any package-specific static analysis commands already used in the repo

Use `@superpowers:verification-before-completion` before claiming the feature is done.

---

## Review Loop

1. Check the plan against spec §§3-10.
2. Verify every task has exact file paths and a corresponding test.
3. If a review suggests a missing capability, add a new task rather than hiding it inside a broad existing task.
4. Keep the old 2026-03-24 plan deleted or replaced; do not resurrect the pre-merge phase list.
