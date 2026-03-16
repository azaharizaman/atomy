# Laravel ProjectManagementOperations Adapter

Laravel adapter for the **ProjectManagementOperations** orchestrator. It implements the orchestrator’s contracts by delegating to the Nexus L1 packages (Project, Task, TimeTracking, ResourceAllocation, Milestone) and to app- or adapter-provided budget, receivable, and messaging implementations.

## Orchestrator contracts → implementations

| Orchestrator contract | Adapter class | Backed by (L1 / app) |
|----------------------|---------------|------------------------|
| `ProjectQueryInterface` | `ProjectQueryAdapter` | **Nexus\Project** `ProjectQueryInterface`, **Nexus\Milestone** `MilestoneQueryInterface` |
| `AttendanceQueryInterface` | `AttendanceQueryAdapter` | App `ProjectTaskIdsQueryInterface`, **Nexus\TimeTracking** `TimesheetQueryInterface` |
| `BudgetQueryInterface` | `BudgetQueryAdapter` | App `ProjectBudgetQueryInterface` |
| `BudgetPersistInterface` | `BudgetPersistAdapter` | App `ProjectBudgetPersistInterface` |
| `SchedulerQueryInterface` | `SchedulerQueryAdapter` | **Nexus\Project** `ProjectQueryInterface` (project start/end) |
| `ReceivablePersistInterface` | `ReceivablePersistAdapter` | **Nexus\Receivable** `ReceivableManagerInterface` |
| `MessagingServiceInterface` | `MessagingServiceAdapter` | App `ProjectMessagingSenderInterface` |

## App-provided contracts (you must implement)

The adapter expects the **application** (or another package) to bind these interfaces. Implement them with Eloquent, DB, or your existing services:

| Interface | Purpose |
|-----------|---------|
| `Nexus\Laravel\ProjectManagementOperations\Contracts\ProjectTaskIdsQueryInterface` | Return task IDs for a project (used for attendance by project). |
| `Nexus\Laravel\ProjectManagementOperations\Contracts\ProjectBudgetQueryInterface` | Labor/expense budget and actuals per project (Money). |
| `Nexus\Laravel\ProjectManagementOperations\Contracts\ProjectBudgetPersistInterface` | Update earned revenue for a project (e.g. on milestone billing). |
| `Nexus\Laravel\ProjectManagementOperations\Contracts\ProjectMessagingSenderInterface` | Send messages (e.g. milestone notifications) to a recipient. |

## L1 query/persist implementations

This adapter **actually uses** (and the service provider binds) these L1 interfaces:

- **Required:** `Nexus\Project\Contracts\ProjectQueryInterface` (and optionally `ProjectPersistInterface` if you extend the adapter)
- **Required:** `Nexus\Milestone\Contracts\MilestoneQueryInterface` (and optionally `MilestonePersistInterface`)
- **Required:** `Nexus\TimeTracking\Contracts\TimesheetQueryInterface` (and optionally `TimesheetPersistInterface`)
- **Required:** `Nexus\Receivable\Contracts\ReceivableManagerInterface` (used by `ReceivablePersistAdapter`)

The following are **not** wired by this adapter; list them only if your app uses them elsewhere or you add custom adapters:

- **Optional:** `Nexus\Task\Contracts\TaskQueryInterface`, `TaskPersistInterface` (this adapter uses app `ProjectTaskIdsQueryInterface` for task IDs by project, not L1 Task directly)
- **Optional:** `Nexus\ResourceAllocation\Contracts\AllocationQueryInterface`, `OverallocationCheckerInterface` (not used by the current orchestrator contracts bound here)

You must **bind concrete implementations** of the required L1 interfaces above and the four app contracts (ProjectTaskIdsQueryInterface, ProjectBudgetQueryInterface, ProjectBudgetPersistInterface, ProjectMessagingSenderInterface) in your Laravel app.

For **testing**, you can use in-memory or fake implementations of the L1 and app contracts (see integration tests in the orchestrator or adapter test suite).

## Registration

1. **Require the package** (in your app or monorepo root):

   ```json
   "require": {
     "nexus/laravel-project-management-operations": "*@dev"
   }
   ```

   If using the monorepo, add a path repository for the adapter, e.g.:

   ```json
   "repositories": [
     { "type": "path", "url": "./adapters/Laravel/ProjectManagementOperations" }
   ]
   ```

2. **Register the service provider** (usually automatic via `extra.laravel.providers`):

   - `Nexus\Laravel\ProjectManagementOperations\Providers\ProjectManagementOperationsAdapterServiceProvider`

   This provider binds each orchestrator contract to the corresponding adapter. The coordinator and internal services (LaborHealthService, ExpenseHealthService, etc.) will then receive these adapters when resolved from the container.

3. **Bind app and L1 implementations** in your `AppServiceProvider` or a domain service provider:

   - Implement and bind `ProjectTaskIdsQueryInterface`, `ProjectBudgetQueryInterface`, `ProjectBudgetPersistInterface`, `ProjectMessagingSenderInterface`.
   - Implement and bind all required L1 query/persist interfaces (Project, Task, TimeTracking, ResourceAllocation, Milestone, and Receivable as needed).

After that, inject `ProjectManagementOperationsCoordinator` (or the individual health/billing services) where you need project health, timeline drift, or milestone billing.

## Usage example

```php
use Nexus\ProjectManagementOperations\ProjectManagementOperationsCoordinator;

// Inject the coordinator (adapters are injected automatically)
public function __construct(
    private ProjectManagementOperationsCoordinator $coordinator
) {}

public function projectHealth(string $tenantId, string $projectId): array
{
    $health = $this->coordinator->getFullHealth($tenantId, $projectId);
    return [
        'overall_score'      => $health->overallScore,
        'labor_utilization'  => $health->laborHealth->healthPercentage,
        'expense_utilization' => $health->expenseHealth->healthPercentage,
        'timeline_completion' => $health->timelineHealth->completionPercentage,
        'drift_details'      => $health->timelineHealth->driftDetails,
    ];
}
```

## References

- Orchestrator: [orchestrators/ProjectManagementOperations/](../../../orchestrators/ProjectManagementOperations/)
- L1 packages: Task, TimeTracking, Project, ResourceAllocation, Milestone under `packages/`
- Project & Delivery docs: [docs/project/NEXUS_PACKAGES_REFERENCE.md](../../../docs/project/NEXUS_PACKAGES_REFERENCE.md) (when present)
