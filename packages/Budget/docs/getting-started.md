# Getting Started with Nexus Budget

## Prerequisites

- PHP 8.3 or higher
- Composer
- A Nexus-compatible application (Laravel, Symfony, etc.)
- Nexus\Period package installed
- Nexus\Currency package installed

## Installation

```bash
composer require nexus/budget:"*@dev"
```

## When to Use This Package

This package is designed for:
- ✅ Budget allocation and tracking across departments
- ✅ Financial control and spending limit enforcement
- ✅ Dual-currency budget management
- ✅ Budget commitment (encumbrance) tracking
- ✅ AI-powered budget forecasting
- ✅ Budget variance analysis and investigation
- ✅ Hierarchical budget consolidation
- ✅ Budget simulations and what-if analysis

Do NOT use this package for:
- ❌ General ledger accounting (use Nexus\Finance)
- ❌ Purchase order management (use Nexus\Procurement)
- ❌ Invoice processing (use Nexus\Payable or Nexus\Receivable)

## Core Concepts

### Concept 1: Budget Lifecycle

Budgets go through several states:
1. **Draft** - Initial creation, editable
2. **PendingApproval** - Submitted for approval
3. **Approved** - Approved but not yet active
4. **Active** - Currently enforcing spending limits
5. **Locked** - No modifications allowed (period closed)
6. **Closed** - Budget period ended
7. **Cancelled** - Budget cancelled

### Concept 2: Commitment vs Actual

- **Commitment (Encumbrance):** Reserved budget when a PO is approved
- **Actual:** Budget consumed when expense is recorded in GL
- **Available:** Budget - (Committed + Actual)

### Concept 3: Dual-Currency Tracking

Every budget transaction is tracked in:
- **Base Currency:** Company's functional currency
- **Presentation Currency:** Reporting currency with snapshot exchange rate

### Concept 4: Budget Hierarchy

Budgets can have parent-child relationships for:
- Department hierarchies
- Multi-year capital projects
- Consolidation and rollup reporting

## Basic Configuration

### Step 1: Implement Required Interfaces

```php
namespace App\Repositories\Budget;

use Nexus\Budget\Contracts\BudgetRepositoryInterface;
use Nexus\Budget\Contracts\BudgetInterface;

final readonly class EloquentBudgetRepository implements BudgetRepositoryInterface
{
    public function __construct(
        private ConnectionInterface $db
    ) {}
    
    public function findById(string $id): BudgetInterface
    {
        return Budget::findOrFail($id);
    }
    
    public function save(BudgetInterface $budget): void
    {
        $budget->save();
    }
    
    public function findByDepartment(string $departmentId, string $periodId): array
    {
        return Budget::where('department_id', $departmentId)
            ->where('period_id', $periodId)
            ->get()
            ->all();
    }
    
    public function findDescendants(string $budgetId): array
    {
        // Implementation using recursive CTE
        return Budget::descendantsOf($budgetId)->get()->all();
    }
    
    // Implement other methods...
}
```

### Step 2: Bind Interfaces in Service Provider

```php
// Laravel example
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Nexus\Budget\Contracts\BudgetRepositoryInterface;
use Nexus\Budget\Contracts\BudgetManagerInterface;
use Nexus\Budget\Services\BudgetManager;
use App\Repositories\Budget\EloquentBudgetRepository;

class BudgetServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind repository
        $this->app->singleton(
            BudgetRepositoryInterface::class,
            EloquentBudgetRepository::class
        );
        
        // Bind manager
        $this->app->singleton(
            BudgetManagerInterface::class,
            BudgetManager::class
        );
        
        // Bind other interfaces...
    }
}
```

### Step 3: Use the Package

```php
use Nexus\Budget\Contracts\BudgetManagerInterface;
use Nexus\Budget\Enums\BudgetType;
use Nexus\Budget\Enums\BudgetStatus;

final readonly class BudgetController
{
    public function __construct(
        private BudgetManagerInterface $budgetManager
    ) {}
    
    public function createBudget(Request $request)
    {
        $budget = $this->budgetManager->createBudget(
            tenantId: $request->user()->tenant_id,
            periodId: $request->input('period_id'),
            departmentId: $request->input('department_id'),
            accountId: $request->input('account_id'),
            budgetType: BudgetType::OperatingExpense,
            allocatedAmount: $request->input('amount'),
            baseCurrency: 'MYR',
            presentationCurrency: 'USD',
            exchangeRate: 4.50
        );
        
        return response()->json($budget);
    }
}
```

## Your First Integration

Complete working example showing budget creation, commitment, and variance checking:

```php
<?php

use Nexus\Budget\Contracts\BudgetManagerInterface;
use Nexus\Budget\Enums\BudgetType;

// Inject the manager
$budgetManager = app(BudgetManagerInterface::class);

// 1. Create a budget
$budget = $budgetManager->createBudget(
    tenantId: 'tenant-123',
    periodId: 'FY2025',
    departmentId: 'dept-IT',
    accountId: 'acc-software',
    budgetType: BudgetType::OperatingExpense,
    allocatedAmount: 100000.00,
    baseCurrency: 'MYR',
    presentationCurrency: 'USD',
    exchangeRate: 4.50
);

// 2. Check availability before committing
$availability = $budgetManager->checkAvailability(
    budgetId: $budget->getId(),
    amount: 25000.00
);

if ($availability->isAvailable) {
    // 3. Commit budget (when PO approved)
    $budgetManager->commitAmount(
        budgetId: $budget->getId(),
        amount: 25000.00,
        sourceDocumentId: 'PO-12345',
        sourceDocumentType: 'purchase_order',
        description: 'Software licenses'
    );
}

// 4. Record actual (when invoice posted)
$budgetManager->recordActual(
    budgetId: $budget->getId(),
    amount: 23500.00,
    sourceDocumentId: 'JE-67890',
    sourceDocumentType: 'journal_entry',
    description: 'Software licenses - actual cost'
);

// 5. Calculate variance
$variance = $budgetManager->calculateVariance($budget->getId());

echo "Budget Variance: {$variance->getPercentageVariance()}%\n";
echo "Status: {$variance->getSeverity()->name}\n";
```

## Next Steps

- Read the [API Reference](api-reference.md) for detailed interface documentation
- Check [Integration Guide](integration-guide.md) for framework-specific examples
- See [Examples](examples/) for more code samples

## Troubleshooting

### Common Issues

**Issue 1: BudgetExceededException thrown**
- Cause: Attempting to commit or record amount exceeding available budget
- Solution: Check availability first with `checkAvailability()`, or use workflow override approval

**Issue 2: InvalidBudgetStatusException**
- Cause: Operation not allowed in current budget status (e.g., trying to commit to Draft budget)
- Solution: Ensure budget is in Active status before committing amounts

**Issue 3: PeriodClosedException**
- Cause: Attempting to modify budget in a closed fiscal period
- Solution: Budgets are locked when periods close; use budget amendment process or reopen period
