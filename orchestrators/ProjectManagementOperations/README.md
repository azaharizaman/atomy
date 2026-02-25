# ProjectManagementOperations Orchestrator

The `ProjectManagementOperations` orchestrator manages the professional services and project-based business lifecycle within the Nexus ERP system. It bridges the gap between project structures, resource allocation, and financial tracking.

## Core Responsibilities

- **Project Health Monitoring**: Real-time comparison between budget allocations and actual labor/expense costs.
- **Timeline Drift Detection**: Monitoring milestone completion against scheduled dates.
- **Milestone Billing Automation**: Automatically triggering invoice creation, notifications, and revenue recognition upon milestone completion.

## Architecture

This orchestrator follows the Nexus Three-Layer Architecture (Layer 2). It is pure PHP 8.3+ and depends exclusively on interfaces defined in its `src/Contracts/` directory.

### Internal Services

- `ProjectManagementOperationsCoordinator`: Unified facade for project management operations.
- `LaborHealthService`: Reconciles attendance hours with budget allocations.
- `ExpenseHealthService`: Reconciles material/overhead expenses with budget limits.
- `TimelineDriftService`: Identifies schedule deviations in milestones.
- `MilestoneBillingService`: Orchestrates financial and communication flows for billable milestones.

## Usage Example

```php
use Nexus\ProjectManagementOperations\ProjectManagementOperationsCoordinator;

// Inject the coordinator into your application service or controller
public function __construct(
    private ProjectManagementOperationsCoordinator $coordinator
) {}

public function showDashboard(string $projectId)
{
    // Get comprehensive health report
    $health = $this->coordinator->getFullHealth($projectId);
    
    return [
        'overall_score' => $health->overallScore,
        'labor_utilization' => $health->laborHealth->healthPercentage,
        'expense_utilization' => $health->expenseHealth->healthPercentage,
        'completion' => $health->timelineHealth->completionPercentage,
        'delays' => $health->timelineHealth->driftDetails
    ];
}
```

## Testing

Run unit tests with:
```bash
vendor/bin/phpunit orchestrators/ProjectManagementOperations/tests/Unit
```
