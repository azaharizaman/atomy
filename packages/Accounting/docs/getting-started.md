# Getting Started with Nexus Accounting

## Prerequisites

- PHP 8.3 or higher
- Composer
- Nexus\Finance package (for GL data access)
- Nexus\Period package (for fiscal period management)
- Nexus\Budget package (optional, for variance analysis)

## Installation

```bash
composer require nexus/accounting:"*@dev"
```

## When to Use This Package

This package is designed for:
- ✅ **Generating financial statements** (Balance Sheet, Income Statement, Cash Flow)
- ✅ **Period close operations** (month-end, quarter-end, year-end)
- ✅ **Multi-entity consolidation** with intercompany eliminations
- ✅ **Budget variance analysis** across accounts, cost centers, departments
- ✅ **Segment reporting** by geography, product line, division
- ✅ **Comparative period reporting** (current vs prior year)
- ✅ **Financial statement export** (PDF, Excel, JSON, CSV)

Do NOT use this package for:
- ❌ **General ledger management** (use `Nexus\Finance` instead)
- ❌ **Transaction posting** (use `Nexus\Finance` for journal entries)
- ❌ **Invoice generation** (use `Nexus\Receivable` or `Nexus\Payable`)
- ❌ **Budget creation** (use `Nexus\Budget` for budget planning)

---

## Core Concepts

### Concept 1: Financial Statement Generation

The **StatementBuilder** engine reads GL data from `Nexus\Finance` and constructs hierarchical financial statements following GAAP, IFRS, or MFRS standards.

**Key Components:**
- **StatementLineItem** - Hierarchical line items with subtotals and percentages
- **StatementSection** - Logical groupings (Assets, Liabilities, Revenue, Expenses)
- **ComplianceStandard** - GAAP, IFRS, or MFRSMalaysia formatting rules

### Concept 2: Period Close

The **PeriodCloseService** validates and locks fiscal periods to prevent backdated transactions.

**Key Steps:**
1. **Validate trial balance** - Ensure debits = credits
2. **Post automatic accruals** - Prepayments, deferrals
3. **Transfer net income** - Year-end retained earnings transfer
4. **Lock period** - Prevent further postings

### Concept 3: Multi-Entity Consolidation

The **ConsolidationEngine** combines financial statements from parent and subsidiary entities, eliminating intercompany transactions.

**Consolidation Methods:**
- **Full Consolidation** - 100% ownership
- **Proportional Consolidation** - Partial ownership
- **Equity Method** - Investment accounting

### Concept 4: Variance Analysis

The **VarianceCalculator** compares actual results against budget, identifying favorable and unfavorable variances.

**Dimensions:**
- Account-level variance
- Cost center variance
- Department variance
- Multi-dimensional analysis

---

## Basic Configuration

### Step 1: Implement Required Interfaces

The Accounting package requires implementations for:

1. **StatementRepositoryInterface** - Store/retrieve financial statements

```php
namespace App\Repositories;

use Nexus\Accounting\Contracts\StatementRepositoryInterface;
use Nexus\Accounting\Contracts\FinancialStatementInterface;
use App\Models\FinancialStatement;

final readonly class EloquentStatementRepository implements StatementRepositoryInterface
{
    public function save(FinancialStatementInterface $statement): void
    {
        FinancialStatement::create([
            'id' => $statement->getId(),
            'tenant_id' => $statement->getTenantId(),
            'type' => $statement->getType()->value,
            'period' => json_encode($statement->getPeriod()),
            'data' => json_encode($statement->toArray()),
        ]);
    }
    
    public function findById(string $id): ?FinancialStatementInterface
    {
        $model = FinancialStatement::find($id);
        return $model ? $this->hydrate($model) : null;
    }
    
    // ... other methods
}
```

2. **LedgerRepositoryInterface** (from Nexus\Finance) - Read GL data

This is already implemented in your Finance package integration.

---

### Step 2: Bind Interfaces in Service Provider

**Laravel Example:**

```php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Nexus\Accounting\Contracts\{
    StatementRepositoryInterface,
    AccountingManagerInterface
};
use Nexus\Accounting\Services\AccountingManager;
use App\Repositories\EloquentStatementRepository;

class AccountingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind repository
        $this->app->singleton(
            StatementRepositoryInterface::class,
            EloquentStatementRepository::class
        );
        
        // Bind accounting manager
        $this->app->singleton(
            AccountingManagerInterface::class,
            AccountingManager::class
        );
    }
}
```

**Symfony Example (services.yaml):**

```yaml
services:
    # Repository
    Nexus\Accounting\Contracts\StatementRepositoryInterface:
        class: App\Repository\StatementRepository
    
    # Accounting Manager
    Nexus\Accounting\Contracts\AccountingManagerInterface:
        class: Nexus\Accounting\Services\AccountingManager
        arguments:
            $statementRepository: '@Nexus\Accounting\Contracts\StatementRepositoryInterface'
            $ledgerRepository: '@Nexus\Finance\Contracts\LedgerRepositoryInterface'
            $periodManager: '@Nexus\Period\Contracts\PeriodManagerInterface'
```

---

### Step 3: Use the Package

```php
use Nexus\Accounting\Contracts\AccountingManagerInterface;
use Nexus\Accounting\Core\ValueObjects\ReportingPeriod;
use Nexus\Accounting\Core\Enums\StatementType;

final readonly class FinancialReportingService
{
    public function __construct(
        private AccountingManagerInterface $accounting
    ) {}
    
    public function generateMonthlyStatements(string $tenantId, \DateTimeImmutable $date): array
    {
        $period = ReportingPeriod::monthly($date);
        
        // Generate all financial statements
        $balanceSheet = $this->accounting->generateBalanceSheet($tenantId, $period);
        $incomeStatement = $this->accounting->generateIncomeStatement($tenantId, $period);
        $cashFlow = $this->accounting->generateCashFlowStatement($tenantId, $period);
        
        return [
            'balance_sheet' => $balanceSheet,
            'income_statement' => $incomeStatement,
            'cash_flow' => $cashFlow,
        ];
    }
}
```

---

## Your First Integration

### Example 1: Generate Balance Sheet

```php
use Nexus\Accounting\Contracts\AccountingManagerInterface;
use Nexus\Accounting\Core\ValueObjects\ReportingPeriod;
use Nexus\Accounting\Core\ValueObjects\ComplianceStandard;

// Inject AccountingManager
public function __construct(
    private readonly AccountingManagerInterface $accounting
) {}

// Generate balance sheet
public function generateBalanceSheet(): void
{
    $tenantId = 'tenant-123';
    $period = ReportingPeriod::monthly(new \DateTimeImmutable('2024-11-30'));
    $standard = ComplianceStandard::mfrsMalaysia();
    
    $balanceSheet = $this->accounting->generateBalanceSheet(
        tenantId: $tenantId,
        period: $period,
        standard: $standard
    );
    
    // Access statement data
    echo "Total Assets: " . $balanceSheet->getTotalAssets() . "\n";
    echo "Total Liabilities: " . $balanceSheet->getTotalLiabilities() . "\n";
    echo "Total Equity: " . $balanceSheet->getTotalEquity() . "\n";
    
    // Verify balance
    if ($balanceSheet->isBalanced()) {
        echo "✓ Balance sheet is balanced!\n";
    }
}
```

### Example 2: Close Fiscal Period

```php
use Nexus\Accounting\Contracts\AccountingManagerInterface;

public function closeMonthEndPeriod(string $tenantId, \DateTimeImmutable $periodEnd): void
{
    // Execute period close
    $closeResult = $this->accounting->closePeriod(
        tenantId: $tenantId,
        periodEnd: $periodEnd,
        closedBy: 'user-456'
    );
    
    // Check results
    if ($closeResult->isSuccess()) {
        echo "✓ Period closed successfully\n";
        echo "Close ID: " . $closeResult->getCloseId() . "\n";
    } else {
        echo "✗ Period close failed:\n";
        foreach ($closeResult->getValidationErrors() as $error) {
            echo "  - {$error}\n";
        }
    }
}
```

### Example 3: Calculate Budget Variance

```php
use Nexus\Accounting\Contracts\AccountingManagerInterface;
use Nexus\Accounting\Core\ValueObjects\ReportingPeriod;

public function analyzeBudgetVariance(string $tenantId): void
{
    $period = ReportingPeriod::quarterly(new \DateTimeImmutable('2024-09-30'));
    
    $variance = $this->accounting->calculateBudgetVariance(
        tenantId: $tenantId,
        period: $period
    );
    
    // Display variance by account
    foreach ($variance->getVariancesByAccount() as $accountId => $accountVariance) {
        echo sprintf(
            "Account %s: Budget %s, Actual %s, Variance %s (%s%%)\n",
            $accountId,
            $accountVariance->getBudgetAmount(),
            $accountVariance->getActualAmount(),
            $accountVariance->getVarianceAmount(),
            $accountVariance->getVariancePercentage()
        );
        
        if ($accountVariance->isFavorable()) {
            echo "  ✓ Favorable variance\n";
        } else {
            echo "  ✗ Unfavorable variance\n";
        }
    }
}
```

---

## Next Steps

- Read the [API Reference](api-reference.md) for detailed interface documentation
- Check [Integration Guide](integration-guide.md) for Laravel/Symfony examples
- See [Examples](examples/) for more code samples
- Review [IMPLEMENTATION_SUMMARY.md](../IMPLEMENTATION_SUMMARY.md) for architecture details

---

## Troubleshooting

### Common Issues

**Issue 1: "StatementRepositoryInterface not bound"**
- **Cause:** Service provider not registered or interface not bound
- **Solution:** 
  - Laravel: Add `AccountingServiceProvider::class` to `config/app.php`
  - Symfony: Ensure `services.yaml` includes interface bindings

**Issue 2: "Trial balance does not balance"**
- **Cause:** Unbalanced journal entries in GL
- **Solution:** 
  - Use `validateTrialBalance()` to identify unbalanced accounts
  - Review journal entries in `Nexus\Finance`
  - Ensure all entries have equal debits and credits

**Issue 3: "Period is not open"**
- **Cause:** Attempting to post transactions to a closed period
- **Solution:**
  - Check period status with `getPeriodCloseStatus()`
  - Use `reopenPeriod()` if authorized to modify closed periods
  - Ensure `Nexus\Period` integration is working correctly

**Issue 4: "Consolidation failed - missing elimination entries"**
- **Cause:** Intercompany transactions not identified or elimination rules not configured
- **Solution:**
  - Configure `ConsolidationRule` value objects with elimination criteria
  - Tag intercompany transactions in GL with entity identifiers
  - Review consolidation entries with `getConsolidationEntries()`

**Issue 5: "Export failed - invalid format"**
- **Cause:** Unsupported export format requested
- **Solution:**
  - Use supported formats: PDF, Excel, JSON, CSV
  - Create `ExportFormat` value object with valid format enum
  - Ensure `Nexus\Export` package is installed for non-JSON exports
