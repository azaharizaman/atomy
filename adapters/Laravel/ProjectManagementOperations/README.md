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

The adapters call **L1** interfaces such as:

- `Nexus\Project\Contracts\ProjectQueryInterface`, `ProjectPersistInterface`
- `Nexus\Task\Contracts\TaskQueryInterface`, `TaskPersistInterface` (and related)
- `Nexus\TimeTracking\Contracts\TimesheetQueryInterface`, `TimesheetPersistInterface`
- `Nexus\ResourceAllocation\Contracts\AllocationQueryInterface`, `OverallocationCheckerInterface`
- `Nexus\Milestone\Contracts\MilestoneQueryInterface`, `MilestonePersistInterface`
- `Nexus\Receivable\Contracts\ReceivableManagerInterface` (used by `ReceivablePersistAdapter`)

You must **bind concrete implementations** of these in your Laravel app (e.g. Eloquent repositories). The adapter package does not ship with default implementations; it only wires the orchestrator to L1 and to the four app contracts above.

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
