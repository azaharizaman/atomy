# Nexus\Budget

Enterprise-grade budget management and financial control plane for the Nexus ERP system.

## Overview

The **Nexus\Budget** package provides comprehensive budget allocation, commitment tracking, variance analysis, and financial forecasting capabilities. It serves as the **financial control layer** that enforces spending limits across all ERP modules through event-driven integration.

## Key Features

### 🎯 Core Capabilities

- **Budget Allocation**: Multi-dimensional budgeting by department, project, GL account, or cost center
- **Dual-Currency Tracking**: Full support for functional and reporting currencies with exchange rate snapshots
- **Line-Item Granularity**: Precise commitment and actual tracking at the transaction line level
- **Hierarchical Budgets**: Unlimited budget hierarchy for multi-year capital projects and organizational structures
- **Revenue Budget Support**: Inverted variance logic for revenue targets

### 🔄 Event-Driven Integration

- **Automatic Encumbrance**: Budget commitments on PO approval via Procurement package
- **Actual Recording**: Automatic actual posting from Journal Entries via Finance package
- **Period Controls**: Budget locking on period close via Period package
- **Workflow Approvals**: Multi-level approval routing for overrides via Workflow package

### 📊 Advanced Analytics

- **Variance Analysis**: Real-time variance calculation with revenue budget logic
- **Budget Forecasting**: AI-powered predictions via Intelligence package integration
- **Performance Dashboards**: Manager KPI scoring and department rankings
- **Utilization Alerts**: Proactive notifications at configurable thresholds (50%, 75%, 90%)

### 🔐 Financial Controls

- **Zero-Based Budgeting**: ZBB methodology support with mandatory justification
- **Rollover Policies**: Configurable handling of unused funds (Expire, Auto-Roll, Require Approval)
- **Approval Hierarchies**: Tiered approval levels (Manager, Director, CFO, Board)
- **Variance Investigations**: Automated workflow triggers for significant variances
- **Budget Simulations**: "What-if" scenario testing before commitment

## Architecture

### Framework Agnosticism

This package contains **pure PHP logic** and is completely framework-agnostic:

- ✅ No Laravel dependencies in `/src`
- ✅ All dependencies via contracts (interfaces)
- ✅ Readonly constructor property promotion
- ✅ Native PHP 8.3 enums with business logic
- ✅ Immutable value objects
- ✅ PSR-3 logging, PSR-14 event dispatching

### Integration Points

| Package | Integration Purpose |
|---------|-------------------|
| **Nexus\Period** | Fiscal period validation, period locking |
| **Nexus\Finance** | Variance calculation from GL actuals |
| **Nexus\Procurement** | PO commitment (encumbrance) tracking |
| **Nexus\Workflow** | Multi-level approval routing |
| **Nexus\Currency** | Dual-currency conversion and rate snapshots |
| **Nexus\Party** | Organizational hierarchy consolidation |
| **Nexus\Intelligence** | AI-powered overrun predictions |
| **Nexus\Notifier** | Budget utilization alerts |
| **Nexus\AuditLogger** | Complete audit trail |
| **Nexus\Setting** | Configurable thresholds and policies |

## Installation

### 1. Add to Root Composer

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "./packages/Budget"
        }
    ]
}
```

### 2. Install Package

```bash
composer require nexus/budget:"*@dev"
```

### 3. Register Service Provider (Laravel)

In `apps/Atomy/config/app.php`:

```php
'providers' => [
    // ...
    App\Providers\BudgetServiceProvider::class,
],
```

### 4. Run Migrations

```bash
php artisan migrate
```

## Core Concepts

### Budget Allocation Formula

```
Available = Allocated - Committed - Actual
```

- **Allocated**: Original budget amount approved for the period
- **Committed**: PO encumbrances (funds reserved but not yet spent)
- **Actual**: Posted journal entry amounts (actual spending)
- **Available**: Remaining funds available for new commitments

### Budget Types

- **Operational**: Day-to-day operating expenses (Manager approval)
- **Capital**: Long-term asset investments (CFO approval)
- **Project**: Project-specific budgets (Director approval)
- **Revenue**: Revenue targets with inverted variance logic (CFO approval)

### Budgeting Methodologies

- **Incremental**: Budget based on prior period with adjustments
- **Zero-Based (ZBB)**: Every expense requires justification from zero

### Rollover Policies

| Policy | Behavior |
|--------|----------|
| **Expire** | Unused funds zeroed at period end |
| **Auto-Roll** | Automatic carry-forward to next period |
| **Require Approval** | Workflow approval required for rollover |

## Quick Start

### Create a Budget

```php
use Nexus\Budget\Contracts\BudgetManagerInterface;
use Nexus\Budget\Enums\BudgetType;
use Nexus\Budget\Enums\BudgetingMethodology;
use Nexus\Budget\Enums\RolloverPolicy;
use Nexus\Uom\ValueObjects\Money;

$budgetManager = app(BudgetManagerInterface::class);

$budget = $budgetManager->createBudget([
    'period_id' => '01JCEXAMPLE',
    'department_id' => 'DEPT-001',
    'budget_type' => BudgetType::Operational,
    'budgeting_methodology' => BudgetingMethodology::Incremental,
    'rollover_policy' => RolloverPolicy::Expire,
    'allocated_amount' => Money::of(100000, 'MYR'),
    'functional_currency_code' => 'USD',
]);
```

### Check Budget Availability

```php
$result = $budgetManager->checkAvailability(
    budgetId: $budget->getId(),
    requestedAmount: Money::of(15000, 'MYR')
);

if ($result->isAvailable()) {
    echo "Budget available: " . $result->getAvailableAmount()->format();
} else {
    echo "Insufficient: " . $result->getShortfall()->format();
}
```

### Commit Budget (Encumbrance)

```php
use Nexus\Budget\Exceptions\BudgetExceededException;

try {
    $budgetManager->commitAmount(
        budgetId: $budget->getId(),
        amount: Money::of(5000, 'MYR'),
        accountId: 'ACC-5010', // GL Account
        sourceType: 'purchase_order_line',
        sourceId: 'PO-2024-001',
        sourceLineNumber: 1,
        costCenterId: 'CC-100'
    );
} catch (BudgetExceededException $e) {
    if ($e->requiresWorkflowApproval()) {
        // Trigger workflow approval
        $workflowId = $workflowAdapter->requestBudgetOverrideApproval(
            budgetId: $budget->getId(),
            requestedAmount: $e->getRequestedAmount(),
            requestorId: auth()->id(),
            reason: "Urgent procurement requirement"
        );
    }
}
```

### Record Actual Spending

```php
// Called automatically by JournalEntryPostedListener
$budgetManager->recordActual(
    budgetId: $budget->getId(),
    amount: Money::of(4850, 'MYR'),
    accountId: 'ACC-5010',
    sourceType: 'journal_entry_line',
    sourceId: 'JE-2024-056',
    sourceLineNumber: 3
);
```

### Calculate Variance

```php
$variance = $budgetManager->calculateVariance($budget->getId());

echo $variance->getStatusMessage();
// "Under budget: 50,150.00 MYR (50.15%)"

if ($variance->requiresInvestigation(threshold: 15.0)) {
    // Trigger investigation workflow
}
```

### Transfer Budget Between Departments

```php
$budgetManager->transferAllocation(
    fromBudgetId: 'BUDGET-DEPT-A',
    toBudgetId: 'BUDGET-DEPT-B',
    amount: Money::of(10000, 'MYR'),
    reason: 'Reallocation for project X'
);
// Requires workflow approval if configured
```

## Configuration

### Settings (via Nexus\Setting)

```php
'budget.variance_analysis_period_count' => 6,           // Historical periods for burn rate
'budget.alert_threshold_percentage' => 85.0,            // Alert when utilization exceeds
'budget.investigation_threshold_percentage' => 15.0,    // Variance investigation trigger
'budget.workflow_threshold_amount' => 50000.00,         // Override approval threshold
'budget.workflow_manager_limit' => 10000.00,            // Manager approval limit
'budget.workflow_director_limit' => 50000.00,           // Director approval limit
'budget.realtime_po_check_threshold_pct' => 20.0,       // Real-time alert for large POs
'budget.notification_thresholds' => [50, 75, 90],       // Utilization alert tiers
'budget.max_hierarchy_depth' => 5,                      // Max budget nesting
'budget.simulation_expiration_days' => 30,              // Simulation auto-cleanup
```

## Event Catalog

### Published Events

- `BudgetCreatedEvent` - New budget created
- `BudgetApprovedEvent` - Budget approved
- `BudgetAllocatedEvent` - Allocation amount set
- `BudgetCommittedEvent` - Commitment recorded (includes utilization %)
- `BudgetActualRecordedEvent` - Actual spending recorded
- `BudgetExceededEvent` - Budget limit exceeded (for Notifier)
- `BudgetOverrideRequestedEvent` - Override approval requested
- `BudgetReallocationRequestedEvent` - Transfer approval requested
- `BudgetVarianceThresholdExceededEvent` - Variance investigation needed
- `BudgetUtilizationAlertEvent` - Utilization threshold crossed
- `BudgetLockedEvent` - Budget locked for closed period

### Subscribed Events

- `Nexus\Procurement\Events\PurchaseOrderApprovedEvent` → Commit budget
- `Nexus\Procurement\Events\PurchaseOrderCancelledEvent` → Release commitment
- `Nexus\Finance\Events\JournalEntryPostedEvent` → Record actual
- `Nexus\Period\Events\PeriodClosedEvent` → Lock budgets, process rollovers
- `Nexus\Workflow\Events\WorkflowApprovedEvent` → Process approved overrides

## API Reference

### BudgetManagerInterface

```php
createBudget(array $data): BudgetInterface
allocate(string $budgetId, Money $amount): void
commitAmount(string $budgetId, Money $amount, string $accountId, ...): void
releaseCommitment(string $budgetId, Money $amount, ...): void
recordActual(string $budgetId, Money $amount, ...): void
calculateVariance(string $budgetId): BudgetVariance
checkAvailability(string $budgetId, Money $requestedAmount): BudgetAvailabilityResult
lockBudget(string $budgetId): void
transferAllocation(string $fromBudgetId, string $toBudgetId, Money $amount, string $reason): void
amendBudget(string $budgetId, Money $newAmount, string $reason): void
createSimulation(string $baseBudgetId, array $modifications): BudgetInterface
```

### BudgetAnalyticsRepositoryInterface

```php
getConsolidatedBudget(string $parentDepartmentId, string $periodId): BudgetConsolidation
getBurnRateByDepartment(string $periodId): array
getVarianceTrends(string $budgetId, int $periodCount): array
getDepartmentRankings(string $periodId, int $limit): array
getManagerPerformanceScore(string $managerId, string $periodId): ManagerPerformanceScore
getUtilizationBreakdown(string $periodId): array
```

## Testing

```bash
# Package tests (framework-agnostic)
vendor/bin/phpunit packages/Budget/tests

# Atomy integration tests
php artisan test --filter Budget
```

## 📖 Documentation

### Package Documentation
- **[Getting Started Guide](docs/getting-started.md)** - Quick start guide with prerequisites, concepts, and first integration
- **[API Reference](docs/api-reference.md)** - Complete documentation of all interfaces, value objects, and exceptions
- **[Integration Guide](docs/integration-guide.md)** - Laravel and Symfony integration examples
- **[Basic Usage Example](docs/examples/basic-usage.php)** - Simple usage patterns
- **[Advanced Usage Example](docs/examples/advanced-usage.php)** - Advanced scenarios and patterns

### Additional Resources
- `IMPLEMENTATION_SUMMARY.md` - Implementation progress and metrics
- `REQUIREMENTS.md` - Detailed requirements (45 requirements documented)
- `TEST_SUITE_SUMMARY.md` - Test coverage and results
- `VALUATION_MATRIX.md` - Package valuation metrics (estimated value: $599,035)
- See root `ARCHITECTURE.md` for overall system architecture

## License

MIT License - see [LICENSE](LICENSE) file for details.

## Support

For questions or issues, please refer to the main Nexus documentation or contact the development team.
