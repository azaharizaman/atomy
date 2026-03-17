# Projects & Tasks Phase 1 Implementation Plan

> Execute this plan task-by-task in order.

**Goal:** Implement Phase 1 of the Projects & Tasks rollout for Atomy-Q, introducing `Nexus\Project` and `Nexus\Task` as first-class concepts in the Laravel API, wiring `Nexus\ProjectManagementOperations`, and defining feature-flagged project/task APIs without impacting existing RFQ flows.

**Architecture:** Use existing Nexus Layer 1 packages (`Nexus\Project`, `Nexus\Task`) and the `Nexus\ProjectManagementOperations` orchestrator via a Laravel adapter. Add optional `project_id` to RFQ persistence, implement app-level bindings for project and task contracts, expose internal project/task services behind feature flags, and update `API_ENDPOINTS.md` with the planned surface for projects and tasks.

**Tech Stack:** PHP 8.x, Laravel (Atomy-Q API), Nexus Layer 1 packages, `Nexus\ProjectManagementOperations` orchestrator, PHPUnit.

---

## Task 1: Add optional `project_id` to RFQ domain & persistence

**Files:**
- Modify: `apps/atomy-q/API/app/Models/Rfq.php`
- Modify: `apps/atomy-q/API/database/migrations/*_create_rfqs_table.php` (or add new migration if altering existing table)
- Create/Modify: `apps/atomy-q/API/app/Http/Resources/RfqResource.php` (include `project_id` in API JSON)
- Test: `apps/atomy-q/API/tests/Feature/RfqProjectIdTest.php`

**Step 1: Write the failing test**

```php
<?php

namespace Tests\Feature;

use App\Models\Rfq;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RfqProjectIdTest extends TestCase
{
    use RefreshDatabase;

    public function test_rfqs_support_optional_project_id_column_and_attribute(): void
    {
        $rfq = Rfq::factory()->create([
            'project_id' => null,
        ]);

        $this->assertArrayHasKey('project_id', $rfq->getAttributes());
        $this->assertNull($rfq->project_id);
    }
}
```

**Step 2: Run test to verify it fails**

Run:

```bash
php artisan test --filter=RfqProjectIdTest
```

Expected: FAIL because the `rfqs` table/model does not yet have a `project_id` column/attribute.

**Step 3: Write minimal implementation**

- Add nullable `project_id` column (unsigned big integer) to RFQ table via migration.
- Ensure `Rfq` model has `project_id` fillable/cast appropriately.
- Keep foreign key constraints and indexes minimal for now (no hard enforcement to preserve optionality).

**Step 3.1: Ensure API includes `project_id`**

- Update `RfqResource` (or equivalent response mapping) to return `project_id` from the RFQ model.

**Step 4: Run test to verify it passes**

```bash
php artisan test --filter=RfqProjectIdTest
```

Expected: PASS.

**Step 5: Commit**

```bash
git add apps/atomy-q/API/app/Models/Rfq.php \
    apps/atomy-q/API/database/migrations/*project_id*rfqs*.php \
    apps/atomy-q/API/tests/Feature/RfqProjectIdTest.php
git commit -m "feat: add optional project_id to RFQs"
```

---

## Task 2: Introduce Laravel bindings for `Nexus\Project` contracts

**Files:**
- Create: `apps/atomy-q/API/app/Services/Project/NexusProjectManager.php`
- Create: `apps/atomy-q/API/app/Services/Project/NexusProjectQuery.php`
- Create: `apps/atomy-q/API/app/Services/Project/NexusProjectPersist.php`
- Modify: `apps/atomy-q/API/app/Providers/AppServiceProvider.php` (or dedicated Nexus provider)
- Test: `apps/atomy-q/API/tests/Unit/Services/NexusProjectBindingsTest.php`

**Step 1: Write the failing test**

```php
<?php

namespace Tests\Unit\Services;

use Nexus\Project\Contracts\ProjectManagerInterface;
use Nexus\Project\Contracts\ProjectPersistInterface;
use Nexus\Project\Contracts\ProjectQueryInterface;
use Tests\TestCase;

class NexusProjectBindingsTest extends TestCase
{
    public function test_project_contracts_are_bound_in_container(): void
    {
        $this->assertInstanceOf(
            ProjectManagerInterface::class,
            $this->app->make(ProjectManagerInterface::class)
        );

        $this->assertInstanceOf(
            ProjectQueryInterface::class,
            $this->app->make(ProjectQueryInterface::class)
        );

        $this->assertInstanceOf(
            ProjectPersistInterface::class,
            $this->app->make(ProjectPersistInterface::class)
        );
    }
}
```

**Step 2: Run test to verify it fails**

```bash
php artisan test --filter=NexusProjectBindingsTest
```

Expected: FAIL because bindings do not exist.

**Step 3: Write minimal implementation**

- Implement thin Laravel adapters (`NexusProjectManager`, `NexusProjectQuery`, `NexusProjectPersist`) that delegate to `Nexus\Project` domain services or repositories as appropriate.
- Register bindings in a service provider so the contracts resolve to these adapters.

**Step 4: Run test to verify it passes**

```bash
php artisan test --filter=NexusProjectBindingsTest
```

Expected: PASS.

**Step 5: Commit**

```bash
git add apps/atomy-q/API/app/Services/Project \
    apps/atomy-q/API/app/Providers/AppServiceProvider.php \
    apps/atomy-q/API/tests/Unit/Services/NexusProjectBindingsTest.php
git commit -m "feat: bind Nexus Project contracts in Laravel container"
```

---

## Task 3: Introduce Laravel bindings for `Nexus\Task` contracts

**Files:**
- Create: `apps/atomy-q/API/app/Services/Task/NexusTaskManager.php`
- Create: `apps/atomy-q/API/app/Services/Task/NexusTaskQuery.php`
- Create: `apps/atomy-q/API/app/Services/Task/NexusTaskPersist.php`
- Modify: `apps/atomy-q/API/app/Providers/AppServiceProvider.php` (or dedicated Nexus provider)
- Test: `apps/atomy-q/API/tests/Unit/Services/NexusTaskBindingsTest.php`

**Step 1: Write the failing test**

```php
<?php

namespace Tests\Unit\Services;

use Nexus\Task\Contracts\TaskManagerInterface;
use Nexus\Task\Contracts\TaskPersistInterface;
use Nexus\Task\Contracts\TaskQueryInterface;
use Tests\TestCase;

class NexusTaskBindingsTest extends TestCase
{
    public function test_task_contracts_are_bound_in_container(): void
    {
        $this->assertInstanceOf(
            TaskManagerInterface::class,
            $this->app->make(TaskManagerInterface::class)
        );

        $this->assertInstanceOf(
            TaskQueryInterface::class,
            $this->app->make(TaskQueryInterface::class)
        );

        $this->assertInstanceOf(
            TaskPersistInterface::class,
            $this->app->make(TaskPersistInterface::class)
        );
    }
}
```

**Step 2: Run test to verify it fails**

```bash
php artisan test --filter=NexusTaskBindingsTest
```

Expected: FAIL because bindings do not exist.

**Step 3: Write minimal implementation**

- Implement Laravel service classes for task management that delegate to `Nexus\Task` domain logic.
- Register bindings so the contracts resolve correctly in the application container.

**Step 4: Run test to verify it passes**

```bash
php artisan test --filter=NexusTaskBindingsTest
```

Expected: PASS.

**Step 5: Commit**

```bash
git add apps/atomy-q/API/app/Services/Task \
    apps/atomy-q/API/app/Providers/AppServiceProvider.php \
    apps/atomy-q/API/tests/Unit/Services/NexusTaskBindingsTest.php
git commit -m "feat: bind Nexus Task contracts in Laravel container"
```

---

## Task 4: Wire `Nexus\ProjectManagementOperations` Laravel adapter & app contracts

**Files:**
- Modify: `orchestrators/ProjectManagementOperations/src/ProjectManagementOperationsCoordinator.php`
- Create/Modify: `Nexus\Laravel\ProjectManagementOperations` adapter classes under `orchestrators/ProjectManagementOperationsLaravel/src/*` (or existing path)
- Create: `src/Contracts/` (or equivalent) with explicit interface definitions before implementing:
  - **ProjectTaskIdsQueryInterface**: e.g. `getTaskIdsForProject(string $projectId, string $tenantId): array`
  - **ProjectBudgetQueryInterface**: e.g. `getBudget(string $projectId, string $tenantId): BudgetDto`
  - **ProjectBudgetPersistInterface**: e.g. `saveBudget(string $projectId, string $tenantId, BudgetDto $dto): void`
  - **ProjectMessagingSenderInterface**: e.g. `sendMessage(string $topic, array $payload): void`
- Create: App contract implementations under `apps/atomy-q/API/app/Services/ProjectManagementOperations/*` conforming to the above
- Modify: relevant service providers to register these app contracts
- Test: `orchestrators/ProjectManagementOperations/tests/Unit/LaravelAdapterWiringTest.php`

**Step 1: Write the failing test**

Write a test that:
- Boots the Laravel container for the worktree app (or a lightweight container for the orchestrator’s Laravel adapter).
- Asserts that the Laravel adapter can be constructed and that it receives the required app contracts.

**Step 2: Run test to verify it fails**

```bash
phpunit orchestrators/ProjectManagementOperations/tests/Unit/LaravelAdapterWiringTest.php
```

Expected: FAIL due to missing adapter wiring or app contract implementations.

**Step 3: Write minimal implementation**

- Implement the Laravel adapter that wires `ProjectManagementOperationsCoordinator` to the app contracts.
- Implement the four app contracts against existing RFQ and financial data models.
- **Tenant scoping:** Ensure every query and operation (e.g. `ProjectTaskIdsQueryInterface::getTaskIdsForProject`, `ProjectBudgetQueryInterface::getBudget`) is filtered by the authenticated user's `tenantId`; obtain `tenantId` from the current auth context (or pass it from `ProjectManagementOperationsCoordinator`) so all repository/ORM queries include a `tenant_id` predicate and multi-tenant isolation is enforced.

**Step 4: Run test to verify it passes**

```bash
phpunit orchestrators/ProjectManagementOperations/tests/Unit/LaravelAdapterWiringTest.php
```

Expected: PASS.

**Step 5: Commit**

```bash
git add orchestrators/ProjectManagementOperations \
    apps/atomy-q/API/app/Services/ProjectManagementOperations
git commit -m "feat: wire ProjectManagementOperations Laravel adapter and app contracts"
```

---

## Task 5: Implement internal project and task services with feature-flagged API endpoints

**Files:**
- Create: `apps/atomy-q/API/app/Http/Controllers/Api/V1/ProjectController.php`
- Create: `apps/atomy-q/API/app/Http/Controllers/Api/V1/TaskController.php`
- Modify: `apps/atomy-q/API/routes/api.php` (or equivalent) to register routes
- Create: feature flag configuration entries (e.g. `config/features.php`)
- Test: `apps/atomy-q/API/tests/Feature/ProjectsApiTest.php`
- Test: `apps/atomy-q/API/tests/Feature/TasksApiTest.php`

**Step 1: Write the failing tests**

Write feature tests that:
- Call planned endpoints (e.g. `GET /api/v1/projects`, `POST /api/v1/tasks`) while the feature flags are enabled.
- Assert basic JSON structure and HTTP 200/201 responses.

**Step 2: Run tests to verify they fail**

```bash
php artisan test --filter=ProjectsApiTest
php artisan test --filter=TasksApiTest
```

Expected: FAIL because controllers/routes/flags are missing.

**Step 3: Write minimal implementation**

- Implement controllers that:
  - Check feature flags.
  - Delegate to the internal services bound to `Nexus\Project` and `Nexus\Task` contracts.
  - Return basic JSON responses (no WEB coupling).
- Register routes under `/api/v1` but guard them with feature flags.
- Ensure routes are authenticated and tenant-scoped (e.g., `jwt.auth` + `tenant` middleware).
- Enforce tenant scoping in all project/task reads and writes and validate request payloads before delegating.

**Step 4: Run tests to verify they pass**

```bash
php artisan test --filter=ProjectsApiTest
php artisan test --filter=TasksApiTest
```

Expected: PASS.

**Step 5: Commit**

```bash
git add apps/atomy-q/API/app/Http/Controllers/Api/V1/ProjectController.php \
    apps/atomy-q/API/app/Http/Controllers/Api/V1/TaskController.php \
    apps/atomy-q/API/routes/api.php \
    apps/atomy-q/API/config/features.php \
    apps/atomy-q/API/tests/Feature/ProjectsApiTest.php \
    apps/atomy-q/API/tests/Feature/TasksApiTest.php
git commit -m "feat: add feature-flagged project and task API endpoints"
```

---

## Task 6: Update `API_ENDPOINTS.md` with Sections 28 & 29 for projects and tasks

**Files:**
- Modify: `apps/atomy-q/API_ENDPOINTS.md`
- Test: `apps/atomy-q/API/tests/Documentation/ApiEndpointsProjectsTasksTest.php`

**Step 1: Write the failing test**

Write a documentation test that:
- Reads `API_ENDPOINTS.md`.
- Asserts that there are sections titled “28. Projects (planned)” and “29. Tasks (planned)” and that they include the planned endpoints listed in `PROJECTS_AND_TASKS_ROLLOUT_PLAN.md`.

**Step 2: Run test to verify it fails**

```bash
php artisan test --filter=ApiEndpointsProjectsTasksTest
```

Expected: FAIL because the sections are missing.

**Step 3: Write minimal implementation**

- Add the two sections to `API_ENDPOINTS.md` with the precise endpoints and descriptions defined in the rollout plan.

**Step 4: Run test to verify it passes**

```bash
php artisan test --filter=ApiEndpointsProjectsTasksTest
```

Expected: PASS.

**Step 5: Commit**

```bash
git add apps/atomy-q/API_ENDPOINTS.md \
    apps/atomy-q/API/tests/Documentation/ApiEndpointsProjectsTasksTest.php
git commit -m "docs: document planned projects and tasks API endpoints"
```

---

## Task 7: Verify Phase 1 integration & non-regression for existing RFQ flows

**Files:**
- Modify/Create: `apps/atomy-q/API/tests/Feature/RfqRegressionForProjectsTest.php`

**Step 1: Write the failing tests**

Add regression tests that:
- Create and update RFQs without `project_id` and assert existing behaviour is unchanged.
- Optionally create RFQs with `project_id` (when feature flag is enabled) and ensure they are accepted by existing RFQ endpoints.

**Step 2: Run tests to verify they fail or highlight missing behaviour**

```bash
php artisan test --filter=RfqRegressionForProjectsTest
```

Expected: Initial FAIL until behaviour is confirmed and wired correctly.

**Step 3: Adjust implementation if needed**

- Make minimal changes to ensure RFQ flows remain backwards compatible while supporting optional `project_id`.

**Step 4: Run full test suite**

```bash
php artisan test
```

Expected: All tests (including new ones) PASS.

**Step 5: Commit**

```bash
git add apps/atomy-q/API/tests/Feature/RfqRegressionForProjectsTest.php
git commit -m "test: guard RFQ flows against project_id regressions"
```

---

## Rollback, feature flags, and observability

- **Rollback migrations**: use `php artisan migrate:rollback` (or target the specific RFQ `project_id` migration) to remove the column if needed.
- **Disable features**: set `FEATURE_PROJECTS_ENABLED=false` and/or `FEATURE_TASKS_ENABLED=false` (see `config/features.php`).
- **Data cleanup**: if rolling back after writing `project_id` values, plan to null out `rfqs.project_id` first.
- **Controller error mapping**: map domain/validation failures to \(4xx\) responses (e.g., circular task dependencies -> 422) and log unexpected exceptions.
- **Minimal logs**: log project creation/update, task creation/update, and project/task association changes with tenant + ids for debugging.

