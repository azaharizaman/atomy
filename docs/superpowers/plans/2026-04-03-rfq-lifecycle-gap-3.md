# RFQ Lifecycle Gap 3 Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [x]`) syntax for tracking.

**Goal:** Close Alpha Gap 3 by replacing RFQ duplicate, draft-save, bulk-action, and status-transition stubs with real tenant-safe lifecycle mutations that persist through the Nexus Layer 1/2/3 architecture.

**Architecture:** Layer 1 owns reusable RFQ lifecycle rules, action vocabulary, and failure semantics. Layer 2 owns the orchestration boundary that coordinates tenant-scoped reads, cloning, persistence, invitation reminder side effects, and transition policy. Layer 3 owns the Atomy-Q Laravel adapter: request validation, controller wiring, Eloquent-backed ports, idempotency wrappers, OpenAPI export, and the generated web client. For the first Alpha slice, duplicate copies the RFQ core record and line items only, save-draft persists editable RFQ fields, bulk-action supports only real persisted actions (`close` and `cancel`), vendor invitation reminder is wired through the same tenant-safe lifecycle boundary, and status transitions are validated through one shared policy instead of controller-local conditionals.

**Tech Stack:** PHP 8.3, Laravel 12, PHPUnit, Scramble/OpenAPI export, Next.js generated client, Nexus `Sourcing`, Nexus `SourcingOperations`, Nexus `Idempotency`.

---

## File Structure

### Layer 1: `packages/Sourcing`

**Create**
- `packages/Sourcing/src/ValueObjects/RfqLifecycleAction.php` - normalized lifecycle action vocabulary for duplicate/save-draft/bulk-action/status flows.
- `packages/Sourcing/src/ValueObjects/RfqBulkAction.php` - allowlisted bulk action names for Alpha-safe bulk operations.
- `packages/Sourcing/src/ValueObjects/RfqDuplicationOptions.php` - explicit copy policy for duplication, including which child records are in scope.
- `packages/Sourcing/src/ValueObjects/RfqLifecycleResult.php` - framework-agnostic result payload for lifecycle mutations.
- `packages/Sourcing/src/Contracts/RfqStatusTransitionPolicyInterface.php` - reusable policy contract for allowed RFQ transitions.
- `packages/Sourcing/src/Exceptions/InvalidRfqStatusTransitionException.php` - thrown when a transition is disallowed.
- `packages/Sourcing/src/Exceptions/UnsupportedRfqBulkActionException.php` - thrown when a bulk action is not allowlisted.
- `packages/Sourcing/src/Exceptions/RfqLifecyclePreconditionException.php` - thrown when tenant/state preconditions are not met.

**Modify**
- `packages/Sourcing/IMPLEMENTATION_SUMMARY.md` - document the new lifecycle primitives and how they relate to quotations and awards.

**Create tests**
- `packages/Sourcing/tests/Unit/RfqLifecycleActionTest.php`
- `packages/Sourcing/tests/Unit/RfqStatusTransitionPolicyTest.php`
- `packages/Sourcing/tests/Unit/RfqLifecycleResultTest.php`

### Layer 2: `orchestrators/SourcingOperations`

**Create**
- `orchestrators/SourcingOperations/src/Contracts/RfqLifecycleCoordinatorInterface.php` - public orchestration contract for duplicate, save draft, bulk action, and transition operations.
- `orchestrators/SourcingOperations/src/Contracts/RfqLifecycleQueryPortInterface.php` - tenant-scoped RFQ reads for the orchestrator.
- `orchestrators/SourcingOperations/src/Contracts/RfqLifecyclePersistPortInterface.php` - RFQ persistence port for create, update, duplicate, and transition writes.
- `orchestrators/SourcingOperations/src/Contracts/RfqLineItemQueryPortInterface.php` - line-item reads used for duplication and save-draft validation.
- `orchestrators/SourcingOperations/src/Contracts/RfqLineItemPersistPortInterface.php` - line-item copy/update persistence port.
- `orchestrators/SourcingOperations/src/Contracts/RfqInvitationQueryPortInterface.php` - invitation reads used for reminder and copy-scope checks.
- `orchestrators/SourcingOperations/src/Contracts/RfqInvitationPersistPortInterface.php` - invitation write port for reminder metadata and tenant-safe invitation updates.
- `orchestrators/SourcingOperations/src/Contracts/RfqInvitationReminderPortInterface.php` - optional notification side-effect port for vendor reminders.
- `orchestrators/SourcingOperations/src/DTOs/DuplicateRfqCommand.php`
- `orchestrators/SourcingOperations/src/DTOs/SaveRfqDraftCommand.php`
- `orchestrators/SourcingOperations/src/DTOs/ApplyRfqBulkActionCommand.php`
- `orchestrators/SourcingOperations/src/DTOs/TransitionRfqStatusCommand.php`
- `orchestrators/SourcingOperations/src/DTOs/RemindRfqInvitationCommand.php`
- `orchestrators/SourcingOperations/src/DTOs/RfqLifecycleOutcome.php`

**Modify**
- `orchestrators/SourcingOperations/src/SourcingOperationsCoordinator.php` - turn the current stub into the concrete RFQ lifecycle facade implementing the coordinator interface.
- `orchestrators/SourcingOperations/README.md` - document the supported lifecycle methods and Alpha scope.
- `orchestrators/SourcingOperations/IMPLEMENTATION_SUMMARY.md` - summarize what the coordinator owns and what stays in adapters.

**Create tests**
- `orchestrators/SourcingOperations/tests/Unit/RfqLifecycleCoordinatorTest.php`
- `orchestrators/SourcingOperations/tests/Unit/RfqLifecycleOutcomeTest.php`
- `orchestrators/SourcingOperations/tests/Unit/RfqInvitationReminderTest.php`

### Layer 3: `apps/atomy-q/API`

**Create**
- `apps/atomy-q/API/app/Http/Requests/RfqDraftRequest.php` - validate the editable RFQ draft payload.
- `apps/atomy-q/API/app/Http/Requests/RfqBulkActionRequest.php` - validate the action name and RFQ ids for bulk operations.
- `apps/atomy-q/API/app/Http/Requests/RfqStatusTransitionRequest.php` - validate transition requests so the controller stops accepting arbitrary status strings.
- `apps/atomy-q/API/app/Services/SourcingOperations/AtomyRfqLifecycleQuery.php` - tenant-scoped RFQ query adapter.
- `apps/atomy-q/API/app/Services/SourcingOperations/AtomyRfqLifecyclePersist.php` - RFQ persistence adapter.
- `apps/atomy-q/API/app/Services/SourcingOperations/AtomyRfqLineItemQuery.php` - line-item query adapter for duplication and draft validation.
- `apps/atomy-q/API/app/Services/SourcingOperations/AtomyRfqLineItemPersist.php` - line-item write/copy adapter.
- `apps/atomy-q/API/app/Services/SourcingOperations/AtomyRfqInvitationQuery.php` - invitation read adapter for tenant-safe reminder checks.
- `apps/atomy-q/API/app/Services/SourcingOperations/AtomyRfqInvitationPersist.php` - invitation write adapter for reminder metadata.
- `apps/atomy-q/API/app/Services/SourcingOperations/AtomyRfqInvitationReminder.php` - optional side-effect adapter for reminder notifications.
- `apps/atomy-q/API/tests/Feature/Api/RfqLifecycleMutationTest.php` - duplicate, draft-save, bulk-action, and status-transition regression coverage.
- `apps/atomy-q/API/tests/Feature/Api/RfqLifecycleIdempotencyTest.php` - replay-safe duplicate/bulk-action coverage.
- `apps/atomy-q/API/tests/Feature/Api/RfqInvitationReminderTest.php` - tenant-safe reminder coverage.

**Modify**
- `apps/atomy-q/API/app/Http/Controllers/Api/V1/RfqController.php` - delegate duplicate/saveDraft/bulkAction/updateStatus to the orchestrator and remove synthetic/stub responses.
- `apps/atomy-q/API/app/Providers/AppServiceProvider.php` - bind the new sourcing lifecycle ports to the Atomy adapters and register the orchestrator facade.
- `apps/atomy-q/API/app/Http/Resources/RfqResource.php` - reuse the canonical RFQ shape where the controller now returns persisted records.
- `apps/atomy-q/API/app/Models/Rfq.php` - only if a small relation/helper is needed for duplication or transition reads.
- `apps/atomy-q/API/IMPLEMENTATION_SUMMARY.md` - document the newly honest lifecycle behavior.
- `apps/atomy-q/API_ENDPOINTS.md` - update the endpoint descriptions so they match the real persistence semantics.
- `apps/atomy-q/docs/ALPHA_PROGRESS_ANALYSIS_2026-03-31.md` - mark Gap 3 as closed and move RFQ lifecycle mutations out of the biggest-gap list.
- `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/page.tsx` - hide or disable unsupported RFQ bulk actions in live mode so the UI matches the supported backend scope.
- `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/page.test.tsx` - verify the live-mode toolbar does not expose actions the backend cannot honor.

### Generated/public contract artifacts

**Regenerate**
- `apps/atomy-q/openapi/openapi.json`
- `apps/atomy-q/WEB/src/generated/api/*`

**Modify if the regenerated output does not fully capture the intent**
- `apps/atomy-q/WEB/IMPLEMENTATION_SUMMARY.md`
- `apps/atomy-q/WEB/BACKEND_API_GAPS.md`

---

## Task 1: Lock the RFQ lifecycle vocabulary and transition rules in Layer 1

**Files:**
- Create `packages/Sourcing/src/ValueObjects/RfqLifecycleAction.php`
- Create `packages/Sourcing/src/ValueObjects/RfqBulkAction.php`
- Create `packages/Sourcing/src/ValueObjects/RfqDuplicationOptions.php`
- Create `packages/Sourcing/src/ValueObjects/RfqLifecycleResult.php`
- Create `packages/Sourcing/src/Contracts/RfqStatusTransitionPolicyInterface.php`
- Create `packages/Sourcing/src/Exceptions/InvalidRfqStatusTransitionException.php`
- Create `packages/Sourcing/src/Exceptions/UnsupportedRfqBulkActionException.php`
- Create `packages/Sourcing/src/Exceptions/RfqLifecyclePreconditionException.php`
- Modify `packages/Sourcing/IMPLEMENTATION_SUMMARY.md`
- Create `packages/Sourcing/tests/Unit/RfqLifecycleActionTest.php`
- Create `packages/Sourcing/tests/Unit/RfqStatusTransitionPolicyTest.php`
- Create `packages/Sourcing/tests/Unit/RfqLifecycleResultTest.php`

- [x] **Step 1: Write the failing tests**

  Add tests that prove:
  - the lifecycle action and bulk-action value objects normalize input deterministically,
  - only the intended Alpha bulk actions are allowed,
  - valid RFQ transitions are accepted and invalid ones throw a domain exception,
  - duplication options preserve the intended child-copy defaults,
  - lifecycle result objects carry the created/updated RFQ identifiers and counts needed by the orchestrator.

- [x] **Step 2: Run the package tests to confirm the current baseline**

  Run:
  ```bash
  cd packages/Sourcing
  composer install
  ./vendor/bin/phpunit
  ```

  Expected: the baseline still passes before the new assertions land, then the new tests fail until the primitives exist.

- [x] **Step 3: Implement the Layer 1 primitives**

  Add the VOs, exceptions, and transition policy contract with strict tenant-agnostic semantics:
  - no framework imports,
  - no database access,
  - no controller knowledge,
  - no synthetic IDs or synthetic success values.

  Keep the first slice narrow: duplication copies RFQ core data and line items, bulk actions are allowlisted, and transition validation is explicit.

- [x] **Step 4: Re-run the package tests**

  Run:
  ```bash
  cd packages/Sourcing
  ./vendor/bin/phpunit
  ```

  Expected: PASS.

- [x] **Step 5: Commit**

  ```bash
  git add packages/Sourcing/src packages/Sourcing/tests/Unit packages/Sourcing/IMPLEMENTATION_SUMMARY.md
  git commit -m "feat(sourcing): add rfq lifecycle primitives"
  ```

---

## Task 2: Turn `SourcingOperationsCoordinator` into the RFQ lifecycle orchestration boundary

**Files:**
- Create `orchestrators/SourcingOperations/src/Contracts/RfqLifecycleCoordinatorInterface.php`
- Create `orchestrators/SourcingOperations/src/Contracts/RfqLifecycleQueryPortInterface.php`
- Create `orchestrators/SourcingOperations/src/Contracts/RfqLifecyclePersistPortInterface.php`
- Create `orchestrators/SourcingOperations/src/Contracts/RfqLineItemQueryPortInterface.php`
- Create `orchestrators/SourcingOperations/src/Contracts/RfqLineItemPersistPortInterface.php`
- Create `orchestrators/SourcingOperations/src/DTOs/DuplicateRfqCommand.php`
- Create `orchestrators/SourcingOperations/src/DTOs/SaveRfqDraftCommand.php`
- Create `orchestrators/SourcingOperations/src/DTOs/ApplyRfqBulkActionCommand.php`
- Create `orchestrators/SourcingOperations/src/DTOs/TransitionRfqStatusCommand.php`
- Create `orchestrators/SourcingOperations/src/DTOs/RfqLifecycleOutcome.php`
- Modify `orchestrators/SourcingOperations/src/SourcingOperationsCoordinator.php`
- Modify `orchestrators/SourcingOperations/README.md`
- Modify `orchestrators/SourcingOperations/IMPLEMENTATION_SUMMARY.md`
- Create `orchestrators/SourcingOperations/tests/Unit/RfqLifecycleCoordinatorTest.php`
- Create `orchestrators/SourcingOperations/tests/Unit/RfqLifecycleOutcomeTest.php`

- [x] **Step 1: Write the failing tests**

  Add tests with in-memory fake ports that prove:
  - duplicate looks up the source RFQ with tenant scope only,
  - duplicate creates a new RFQ, copies only the approved fields/line items, and returns a structured outcome,
  - save draft persists only editable fields and rejects illegal state edits,
  - bulk action applies only allowlisted actions and returns an accurate affected count,
  - status transitions run through the shared Layer 1 policy instead of controller-local rules,
  - reminder reads and writes stay tenant-scoped and never return a synthetic success payload.

- [x] **Step 2: Run the orchestrator tests to confirm the current baseline**

  Run:
  ```bash
  cd orchestrators/SourcingOperations
  composer install
  ./vendor/bin/phpunit
  ```

  Expected: the stub coordinator still passes the current baseline, then the new unit tests fail until the orchestration boundary is implemented.

- [x] **Step 3: Implement the coordinator and ports**

  Turn `SourcingOperationsCoordinator` into the concrete facade for the RFQ lifecycle boundary.
  - inject the query and persist ports via interfaces,
  - keep tenant scoping explicit on every read/write,
  - make duplicate/save-draft/bulk-action/status-transition return `RfqLifecycleOutcome`,
  - keep invitation/reminder copying out of the first Alpha slice unless the adapter already proves it is safe and required.

  The default duplicate path should copy the RFQ core record and line items only. Bulk actions should stay conservative: `close` and `cancel` are in scope; `archive` stays deferred unless it maps cleanly to a persisted state.

- [x] **Step 4: Re-run the orchestrator tests**

  Run:
  ```bash
  cd orchestrators/SourcingOperations
  ./vendor/bin/phpunit
  ```

  Expected: PASS.

- [x] **Step 5: Commit**

  ```bash
  git add orchestrators/SourcingOperations/src orchestrators/SourcingOperations/tests/Unit orchestrators/SourcingOperations/README.md orchestrators/SourcingOperations/IMPLEMENTATION_SUMMARY.md
  git commit -m "feat(sourcing-operations): add rfq lifecycle coordinator"
  ```

---

## Task 3: Wire the Atomy-Q API adapter to the real lifecycle boundary

**Files:**
- Create `apps/atomy-q/API/app/Http/Requests/RfqDraftRequest.php`
- Create `apps/atomy-q/API/app/Http/Requests/RfqBulkActionRequest.php`
- Create `apps/atomy-q/API/app/Http/Requests/RfqStatusTransitionRequest.php`
- Create `apps/atomy-q/API/app/Services/SourcingOperations/AtomyRfqLifecycleQuery.php`
- Create `apps/atomy-q/API/app/Services/SourcingOperations/AtomyRfqLifecyclePersist.php`
- Create `apps/atomy-q/API/app/Services/SourcingOperations/AtomyRfqLineItemQuery.php`
- Create `apps/atomy-q/API/app/Services/SourcingOperations/AtomyRfqLineItemPersist.php`
- Modify `apps/atomy-q/API/app/Http/Controllers/Api/V1/RfqController.php`
- Modify `apps/atomy-q/API/app/Http/Controllers/Api/V1/VendorInvitationController.php`
- Modify `apps/atomy-q/API/app/Providers/AppServiceProvider.php`
- Modify `apps/atomy-q/API/app/Http/Resources/RfqResource.php`
- Create `apps/atomy-q/API/tests/Feature/Api/RfqLifecycleMutationTest.php`
- Create `apps/atomy-q/API/tests/Feature/Api/RfqLifecycleIdempotencyTest.php`
- Create `apps/atomy-q/API/tests/Feature/Api/RfqInvitationReminderTest.php`

- [x] **Step 1: Write the failing feature tests**

  Add API tests that prove:
  - duplicate returns a real persisted RFQ record instead of `stub-duplicate-id`,
  - duplicate is tenant-scoped and returns 404 for wrong-tenant access,
  - duplicate reuses the same response for a repeated request with the same idempotency key,
  - save draft persists editable RFQ fields and keeps the RFQ in draft when that is the allowed lifecycle state,
  - bulk action returns a real `affected` count and rejects unsupported actions,
  - update status uses the shared lifecycle policy and refuses invalid transitions,
  - vendor invitation reminder returns a real tenant-scoped response and never leaks existence across tenants.

  Reuse `RfqResource` so the controller returns the same canonical RFQ shape as the rest of the API.

- [x] **Step 2: Run the API tests to confirm the current baseline**

  Run:
  ```bash
  cd apps/atomy-q/API
  composer install
  ./vendor/bin/phpunit tests/Feature/Api/RfqLifecycleMutationTest.php tests/Feature/Api/RfqLifecycleIdempotencyTest.php tests/Feature/Api/RfqInvitationReminderTest.php tests/Feature/Api/ProtectedEndpointsTest.php
  ```

  Expected: the existing code still passes the old baseline, then the new lifecycle assertions fail until the controller and bindings are updated.

- [x] **Step 3: Implement the adapter wiring**

  - Add the request classes so the controller stops accepting free-form action/status payloads.
  - Implement the Eloquent-backed adapters under `app/Services/SourcingOperations/` for RFQ queries and persistence.
  - Implement the invitation query/persist/reminder adapters in the same folder so `VendorInvitationController::remind` stops returning a synthetic payload.
  - Bind the interfaces in `AppServiceProvider`.
  - Update `RfqController` to delegate duplicate, saveDraft, bulkAction, and updateStatus to the orchestrator instead of mutating state inline or returning synthetic JSON.
  - Update `VendorInvitationController` to route remind through the same tenant-safe invitation/reminder ports instead of echoing the request ids.
  - Keep the existing `IdempotencyCompletion` wrapper on the mutating POST paths so replay behavior stays deterministic.

- [x] **Step 4: Re-run the API tests**

  Run:
  ```bash
  cd apps/atomy-q/API
  ./vendor/bin/phpunit tests/Feature/Api/RfqLifecycleMutationTest.php tests/Feature/Api/RfqLifecycleIdempotencyTest.php tests/Feature/Api/RfqInvitationReminderTest.php tests/Feature/Api/ProtectedEndpointsTest.php
  ```

  Expected: PASS.

- [x] **Step 5: Commit**

  ```bash
  git add apps/atomy-q/API/app/Http/Controllers/Api/V1/RfqController.php apps/atomy-q/API/app/Http/Controllers/Api/V1/VendorInvitationController.php apps/atomy-q/API/app/Http/Requests apps/atomy-q/API/app/Services/SourcingOperations apps/atomy-q/API/app/Providers/AppServiceProvider.php apps/atomy-q/API/app/Http/Resources/RfqResource.php apps/atomy-q/API/tests/Feature/Api/RfqLifecycleMutationTest.php apps/atomy-q/API/tests/Feature/Api/RfqLifecycleIdempotencyTest.php apps/atomy-q/API/tests/Feature/Api/RfqInvitationReminderTest.php
  git commit -m "feat(atomy-q-api): real rfq lifecycle mutations"
  ```

---

## Task 4: Regenerate public contracts and close the Alpha gap analysis

**Files:**
- Modify `apps/atomy-q/API/IMPLEMENTATION_SUMMARY.md`
- Modify `apps/atomy-q/API_ENDPOINTS.md`
- Modify `apps/atomy-q/docs/ALPHA_PROGRESS_ANALYSIS_2026-03-31.md`
- Regenerate `apps/atomy-q/openapi/openapi.json`
- Regenerate `apps/atomy-q/WEB/src/generated/api/*`
- Modify `apps/atomy-q/WEB/IMPLEMENTATION_SUMMARY.md` if the regenerated client or response shapes change any live-mode notes
- Modify `apps/atomy-q/WEB/BACKEND_API_GAPS.md` if any temporary warning text becomes stale
- Modify `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/page.tsx` to hide or disable unsupported live-mode bulk actions
- Create `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/page.test.tsx` to keep the toolbar aligned with the supported backend actions

- [x] **Step 1: Write the documentation diffs**

  Update the docs so they say:
  - duplicate, save-draft, bulk-action, and status-transition are real persisted flows,
  - wrong-tenant access still resolves to 404 without leaking existence,
  - bulk action intentionally stays conservative if archive is not backed by a persisted state, and the RFQ list toolbar no longer advertises unsupported actions in live mode,
  - Gap 3 is closed in `ALPHA_PROGRESS_ANALYSIS_2026-03-31.md`.

- [x] **Step 2: Regenerate the OpenAPI export and the web client**

  Run:
  ```bash
  cd apps/atomy-q/API
  php artisan scramble:export --path=../openapi/openapi.json
  cd ../WEB
  npm run generate:api
  npm run build
  ```

  Expected: the OpenAPI file and generated client now match the real RFQ lifecycle responses, and the web build succeeds against the regenerated types.

- [x] **Step 3: Run the focused verification suite**

  Run:
  ```bash
  cd apps/atomy-q/API
  ./vendor/bin/phpunit tests/Feature/Api/RfqLifecycleMutationTest.php tests/Feature/Api/RfqLifecycleIdempotencyTest.php tests/Feature/Api/RfqInvitationReminderTest.php tests/Feature/RfqOverviewActivityTest.php tests/Feature/RfqRegressionForProjectsTest.php
  cd ../WEB
  npm run test:unit -- --run src/lib/api.test.ts
  ```

  Expected: PASS.

- [x] **Step 4: Commit**

  ```bash
  git add apps/atomy-q/API/IMPLEMENTATION_SUMMARY.md apps/atomy-q/API_ENDPOINTS.md apps/atomy-q/docs/ALPHA_PROGRESS_ANALYSIS_2026-03-31.md apps/atomy-q/openapi/openapi.json apps/atomy-q/WEB/src/generated/api apps/atomy-q/WEB/src/app/(dashboard)/rfqs/page.tsx apps/atomy-q/WEB/src/app/(dashboard)/rfqs/page.test.tsx apps/atomy-q/WEB/IMPLEMENTATION_SUMMARY.md apps/atomy-q/WEB/BACKEND_API_GAPS.md
  git commit -m "docs(atomy-q): close rfq lifecycle gap 3"
  ```

---

## Execution Notes

1. Keep tenant filtering explicit on every read and write path touched by the lifecycle work.
2. Do not reintroduce synthetic IDs or fake success payloads anywhere in the duplicate/save-draft/bulk-action flow.
3. Use the shared transition policy for `updateStatus` and any bulk action that changes RFQ state so the controller does not drift from the orchestrator.
4. Prefer small commits after each task so failures stay isolated.
5. If the `archive` action cannot be mapped to a real persisted state without ambiguity, leave it out of the Alpha slice and document it as deferred rather than faking the response.
