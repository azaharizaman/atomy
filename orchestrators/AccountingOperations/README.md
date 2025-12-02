# Nexus\AccountingOperations

**Framework-Agnostic Accounting Workflow Orchestrator**

[![PHP Version](https://img.shields.io/badge/php-%5E8.3-blue)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-MIT-green)](LICENSE)

## Overview

`Nexus\AccountingOperations` is an **orchestrator** that coordinates workflows across multiple accounting packages. It provides unified entry points for complex, multi-step accounting operations such as period close, financial statement generation, consolidation, and ratio analysis.

As an orchestrator, this package:
- **Owns the Flow** - Coordinates the sequence of operations across packages
- **Does NOT Own the Truth** - Entities and core logic remain in atomic packages
- **Remains Framework-Agnostic** - Pure PHP with no database access or framework code

## Installation

```bash
composer require nexus/accounting-operations
```

## Orchestrator Responsibilities

| Responsibility | Description |
|----------------|-------------|
| **Workflow Coordination** | Sequence multi-step accounting processes |
| **Cross-Package Integration** | Orchestrate calls across 5 atomic packages |
| **Data Provider Composition** | Aggregate data from multiple sources |
| **Event Handling** | React to events from atomic packages |
| **Process Rules** | Enforce business rules that span packages |

## Packages Orchestrated

| Package | Purpose in Workflow |
|---------|---------------------|
| `Nexus\FinancialStatements` | Generate statements |
| `Nexus\AccountConsolidation` | Consolidate multi-entity |
| `Nexus\AccountVarianceAnalysis` | Analyze variances |
| `Nexus\AccountPeriodClose` | Close accounting periods |
| `Nexus\FinancialRatios` | Calculate financial ratios |

---

## Architecture

```
src/
├── Contracts/           # Orchestrator interfaces
├── Coordinators/        # Stateless operation coordinators
├── DTOs/                # Request/response data transfer objects
├── DataProviders/       # Aggregate data from multiple packages
├── Services/            # Orchestration services
├── Rules/               # Cross-package validation rules
├── Workflows/           # Stateful workflow processes
│   ├── PeriodClose/     # Period close workflow steps
│   ├── StatementGeneration/  # Statement workflows
│   └── Consolidation/   # Consolidation workflows
├── Listeners/           # Event listeners
└── Exceptions/          # Orchestrator-specific errors
```

---

## Contracts (Interfaces)

### Core Orchestrator Interfaces

#### `AccountingWorkflowInterface`

Base interface for all accounting workflows.

```php
interface AccountingWorkflowInterface
{
    /**
     * Get the workflow name
     */
    public function getName(): string;
    
    /**
     * Get required steps in order
     *
     * @return array<string>
     */
    public function getSteps(): array;
    
    /**
     * Execute the workflow
     *
     * @param array $context Workflow context data
     * @return WorkflowResult
     */
    public function execute(array $context): WorkflowResult;
    
    /**
     * Get current workflow status
     */
    public function getStatus(): WorkflowStatus;
}
```

#### `AccountingCoordinatorInterface`

Base interface for stateless coordinators.

```php
interface AccountingCoordinatorInterface
{
    /**
     * Execute the coordinated operation
     *
     * @param mixed $request Operation request
     * @return mixed Operation result
     */
    public function execute(mixed $request): mixed;
    
    /**
     * Check if operation can be executed
     */
    public function canExecute(mixed $request): bool;
}
```

---

## Coordinators

Stateless coordinators that orchestrate calls across packages.

### `PeriodCloseCoordinator`

Coordinates the entire period close process.

```php
final readonly class PeriodCloseCoordinator implements AccountingCoordinatorInterface
{
    public function __construct(
        private CloseReadinessValidatorInterface $readinessValidator,  // AccountPeriodClose
        private ClosingEntryGeneratorInterface $entryGenerator,        // AccountPeriodClose
        private StatementBuilderInterface $statementBuilder,           // FinancialStatements
        private RatioCalculatorInterface $ratioCalculator,             // FinancialRatios
        private AuditLogManagerInterface $auditLogger,                  // AuditLogger
        private CloseRuleRegistry $ruleRegistry                         // Local
    ) {}
    
    /**
     * Execute complete period close process
     *
     * 1. Validate readiness (all subledgers closed, TB balanced)
     * 2. Generate and post closing entries
     * 3. Generate period-end statements
     * 4. Calculate financial ratios
     * 5. Log close event
     */
    public function execute(PeriodCloseRequest $request): PeriodCloseResult;
}
```

### `StatementGenerationCoordinator`

Coordinates financial statement generation.

```php
final readonly class StatementGenerationCoordinator implements AccountingCoordinatorInterface
{
    public function __construct(
        private StatementBuilderInterface $statementBuilder,           // FinancialStatements
        private StatementValidatorInterface $statementValidator,       // FinancialStatements
        private FinanceDataProviderInterface $financeDataProvider,     // Local composition
        private AuditLogManagerInterface $auditLogger
    ) {}
    
    /**
     * Generate a complete set of financial statements
     *
     * 1. Gather account balances
     * 2. Build each statement type
     * 3. Validate statements
     * 4. Package results
     */
    public function execute(StatementGenerationRequest $request): StatementGenerationResult;
}
```

### `ConsolidationCoordinator`

Coordinates multi-entity consolidation.

```php
final readonly class ConsolidationCoordinator implements AccountingCoordinatorInterface
{
    public function __construct(
        private ConsolidationEngineInterface $consolidationEngine,      // AccountConsolidation
        private StatementBuilderInterface $statementBuilder,            // FinancialStatements
        private CurrencyManagerInterface $currencyManager,              // Currency
        private ConsolidationDataProviderInterface $dataProvider,       // Local composition
        private AuditLogManagerInterface $auditLogger
    ) {}
    
    /**
     * Execute group consolidation
     *
     * 1. Resolve ownership structure
     * 2. Translate foreign currencies
     * 3. Eliminate intercompany
     * 4. Calculate NCI
     * 5. Generate consolidated statements
     */
    public function execute(ConsolidationRequest $request): ConsolidationResult;
}
```

### `VarianceAnalysisCoordinator`

Coordinates variance analysis and reporting.

```php
final readonly class VarianceAnalysisCoordinator implements AccountingCoordinatorInterface
{
    public function __construct(
        private VarianceCalculatorInterface $varianceCalculator,        // AccountVarianceAnalysis
        private TrendAnalyzerInterface $trendAnalyzer,                  // AccountVarianceAnalysis
        private SignificanceEvaluatorInterface $significanceEvaluator,  // AccountVarianceAnalysis
        private VarianceDataProviderInterface $dataProvider             // Local composition
    ) {}
    
    /**
     * Execute comprehensive variance analysis
     *
     * 1. Calculate budget variances
     * 2. Evaluate significance
     * 3. Analyze trends
     * 4. Generate variance report
     */
    public function execute(VarianceAnalysisRequest $request): VarianceAnalysisResult;
}
```

### `RatioAnalysisCoordinator`

Coordinates financial ratio analysis.

```php
final readonly class RatioAnalysisCoordinator implements AccountingCoordinatorInterface
{
    public function __construct(
        private RatioCalculatorInterface $ratioCalculator,   // FinancialRatios
        private DuPontAnalyzer $dupontAnalyzer,              // FinancialRatios
        private RatioBenchmarker $benchmarker,               // FinancialRatios
        private RatioDataProviderInterface $dataProvider     // Local composition
    ) {}
    
    /**
     * Execute comprehensive ratio analysis
     *
     * 1. Calculate all ratio categories
     * 2. Perform DuPont analysis
     * 3. Compare to benchmarks
     * 4. Assess financial health
     */
    public function execute(RatioAnalysisRequest $request): RatioAnalysisResult;
}
```

### `YearEndCloseCoordinator`

Coordinates annual year-end close.

```php
final readonly class YearEndCloseCoordinator implements AccountingCoordinatorInterface
{
    /**
     * Execute year-end close
     *
     * 1. Close all periods in fiscal year
     * 2. Generate closing entries to retained earnings
     * 3. Generate equity rollforward
     * 4. Create opening balances for new year
     * 5. Generate annual financial statements
     */
    public function execute(YearEndCloseRequest $request): YearEndCloseResult;
}
```

### Additional Coordinators

| Coordinator | Purpose |
|-------------|---------|
| `TrialBalanceCoordinator` | Generate and validate trial balances |
| `AuditPackageCoordinator` | Assemble audit support packages |
| `ForecastCoordinator` | Update rolling forecasts |
| `BudgetComparisonCoordinator` | Compare actual vs budget |

---

## DTOs (Data Transfer Objects)

### Request DTOs

#### `PeriodCloseRequest`

```php
final readonly class PeriodCloseRequest
{
    public function __construct(
        public string $tenantId,
        public string $periodId,
        public string $userId,
        public CloseType $closeType = CloseType::HARD,
        public bool $generateStatements = true,
        public bool $calculateRatios = true,
        public array $overrides = []
    ) {}
}
```

#### `StatementGenerationRequest`

```php
final readonly class StatementGenerationRequest
{
    public function __construct(
        public string $tenantId,
        public string $periodId,
        public array $statementTypes,
        public ComplianceFramework $framework,
        public bool $includeComparative = true,
        public int $comparativePeriods = 1,
        public ?string $entityId = null
    ) {}
}
```

#### `ConsolidationRequest`

```php
final readonly class ConsolidationRequest
{
    public function __construct(
        public string $tenantId,
        public string $parentEntityId,
        public array $subsidiaryIds,
        public string $periodId,
        public string $reportingCurrency,
        public bool $generateStatements = true
    ) {}
}
```

#### `VarianceAnalysisRequest`

```php
final readonly class VarianceAnalysisRequest
{
    public function __construct(
        public string $tenantId,
        public string $periodId,
        public VarianceType $varianceType,
        public ?float $significanceThreshold = 10.0,
        public bool $includeTrends = true,
        public int $trendPeriods = 12
    ) {}
}
```

#### `RatioAnalysisRequest`

```php
final readonly class RatioAnalysisRequest
{
    public function __construct(
        public string $tenantId,
        public string $periodId,
        public array $ratioCategories = [],
        public bool $includeDuPont = true,
        public bool $includeBenchmarks = true,
        public ?string $industryCode = null
    ) {}
}
```

#### `YearEndCloseRequest`

```php
final readonly class YearEndCloseRequest
{
    public function __construct(
        public string $tenantId,
        public string $fiscalYearId,
        public string $userId,
        public string $retainedEarningsAccountId,
        public bool $generateAnnualStatements = true
    ) {}
}
```

---

## Data Providers

Aggregate data from multiple packages to support coordinators.

### `FinanceDataProvider`

```php
final readonly class FinanceDataProvider implements StatementDataProviderInterface
{
    public function __construct(
        private GeneralLedgerManagerInterface $glManager,     // Finance
        private ChartOfAccountsInterface $coa,                // Finance
        private PeriodManagerInterface $periodManager         // Period
    ) {}
    
    /**
     * Aggregates GL data for statement generation
     */
    public function getAccountBalances(
        string $tenantId,
        string $periodId,
        \DateTimeImmutable $asOfDate
    ): array;
}
```

### `BudgetDataProvider`

```php
final readonly class BudgetDataProvider implements VarianceDataProviderInterface
{
    public function __construct(
        private BudgetManagerInterface $budgetManager,         // Budget
        private GeneralLedgerManagerInterface $glManager,      // Finance
        private PeriodManagerInterface $periodManager          // Period
    ) {}
    
    /**
     * Aggregates actual and budget data for variance analysis
     */
    public function getActualBalances(string $tenantId, string $periodId): array;
    public function getBudgetAmounts(string $tenantId, string $periodId): array;
}
```

### `ConsolidationDataProvider`

```php
final readonly class ConsolidationDataProvider implements ConsolidationDataProviderInterface
{
    public function __construct(
        private CompanyManagerInterface $companyManager,       // Backoffice
        private GeneralLedgerManagerInterface $glManager,      // Finance
        private ExchangeRateRepositoryInterface $exchangeRates // Currency
    ) {}
    
    /**
     * Aggregates entity and financial data for consolidation
     */
    public function getEntity(string $entityId): ConsolidationEntity;
    public function getIntercompanyBalances(string $periodId, array $entityIds): array;
}
```

### `RatioDataProvider`

```php
final readonly class RatioDataProvider implements RatioDataProviderInterface
{
    public function __construct(
        private GeneralLedgerManagerInterface $glManager,      // Finance
        private ChartOfAccountsInterface $coa,                 // Finance
        private StatementBuilderInterface $statementBuilder    // FinancialStatements
    ) {}
    
    /**
     * Aggregates financial data for ratio calculations
     */
    public function getBalanceSheetData(string $tenantId, string $periodId): BalanceSheetData;
    public function getIncomeStatementData(string $tenantId, string $periodId): IncomeStatementData;
    public function getCashFlowData(string $tenantId, string $periodId): CashFlowData;
}
```

---

## Services

### `FinanceStatementBuilder`

Orchestration service that builds complete statement packages.

```php
final readonly class FinanceStatementBuilder
{
    /**
     * Build a complete financial statement package
     * (Balance Sheet, Income Statement, Cash Flow, Equity Changes, Notes)
     */
    public function buildCompletePackage(
        string $tenantId,
        string $periodId,
        ComplianceFramework $framework
    ): FinancialStatementPackage;
}
```

### `CloseRuleRegistry`

Registry for cross-package close validation rules.

```php
final readonly class CloseRuleRegistry
{
    /**
     * Get all registered close rules
     */
    public function getRules(): array;
    
    /**
     * Register a close rule
     */
    public function register(CloseRuleInterface $rule): void;
    
    /**
     * Get rules by severity
     */
    public function getRulesBySeverity(ValidationSeverity $severity): array;
}
```

---

## Rules

Cross-package validation rules that enforce business logic spanning multiple packages.

### `AllSubledgersClosedRule`

```php
final readonly class AllSubledgersClosedRule implements CloseRuleInterface
{
    public function __construct(
        private ReceivableManagerInterface $receivableManager,  // Receivable
        private PayableManagerInterface $payableManager,        // Payable
        private InventoryManagerInterface $inventoryManager     // Inventory
    ) {}
    
    /**
     * Verify all subledgers are closed before GL close
     */
    public function check(string $tenantId, string $periodId): CloseCheckResult;
}
```

### `WorkflowApprovalRule`

```php
final readonly class WorkflowApprovalRule implements CloseRuleInterface
{
    public function __construct(
        private WorkflowManagerInterface $workflowManager  // Workflow
    ) {}
    
    /**
     * Verify required approvals are complete
     */
    public function check(string $tenantId, string $periodId): CloseCheckResult;
}
```

---

## Workflows

Stateful, multi-step workflows for complex accounting processes.

### Period Close Workflow

Located in `src/Workflows/PeriodClose/`:

#### `PeriodCloseWorkflow`

```php
final class PeriodCloseWorkflow implements AccountingWorkflowInterface
{
    public function getSteps(): array
    {
        return [
            'validate_readiness',
            'close_subledgers',
            'generate_adjusting_entries',
            'post_adjusting_entries',
            'generate_closing_entries',
            'post_closing_entries',
            'generate_statements',
            'lock_period',
        ];
    }
}
```

#### Step Implementations

| Step Class | Responsibility |
|------------|----------------|
| `ValidateReadinessStep` | Run all close validation rules |
| `CloseSubledgersStep` | Close AR, AP, Inventory subledgers |
| `GenerateAdjustingEntriesStep` | Create accruals, deferrals |
| `PostEntriesStep` | Post adjusting/closing entries to GL |
| `GenerateClosingEntriesStep` | Create closing entries |
| `GenerateStatementsStep` | Generate period-end statements |
| `LockPeriodStep` | Set period status to HARD_CLOSED |

#### `YearEndCloseWorkflow`

Extended workflow for annual close with equity rollforward.

### Statement Generation Workflows

Located in `src/Workflows/StatementGeneration/`:

| Workflow | Purpose |
|----------|---------|
| `BalanceSheetWorkflow` | Generate balance sheet |
| `IncomeStatementWorkflow` | Generate income statement |
| `CashFlowWorkflow` | Generate cash flow statement |
| `EquityChangesWorkflow` | Generate equity changes statement |

### Consolidation Workflow

Located in `src/Workflows/Consolidation/`:

#### `ConsolidationWorkflow`

```php
final class ConsolidationWorkflow implements AccountingWorkflowInterface
{
    public function getSteps(): array
    {
        return [
            'resolve_ownership',
            'translate_currencies',
            'eliminate_intercompany',
            'calculate_nci',
            'calculate_goodwill',
            'generate_consolidated_statements',
        ];
    }
}
```

---

## Listeners

Event listeners that react to events from atomic packages.

### `OnPeriodClosedGenerateStatements`

```php
final readonly class OnPeriodClosedGenerateStatements
{
    /**
     * Automatically generate statements when a period is closed
     */
    public function handle(PeriodClosedEvent $event): void;
}
```

### `OnStatementGeneratedCalculateRatios`

```php
final readonly class OnStatementGeneratedCalculateRatios
{
    /**
     * Automatically calculate ratios when statements are generated
     */
    public function handle(StatementGeneratedEvent $event): void;
}
```

### `OnConsolidationCompletedNotify`

```php
final readonly class OnConsolidationCompletedNotify
{
    /**
     * Send notifications when consolidation completes
     */
    public function handle(ConsolidationCompletedEvent $event): void;
}
```

### `OnVarianceThresholdExceededAlert`

```php
final readonly class OnVarianceThresholdExceededAlert
{
    /**
     * Alert when significant variances detected
     */
    public function handle(VarianceThresholdExceededEvent $event): void;
}
```

---

## Exceptions

| Exception | When Thrown |
|-----------|-------------|
| `WorkflowException` | Workflow execution fails |
| `CoordinationException` | Coordinator operation fails |

---

## Usage Example

```php
use Nexus\AccountingOperations\Coordinators\PeriodCloseCoordinator;
use Nexus\AccountingOperations\DTOs\PeriodCloseRequest;
use Nexus\AccountPeriodClose\Enums\CloseType;

// Inject the coordinator
public function __construct(
    private readonly PeriodCloseCoordinator $periodCloseCoordinator
) {}

public function closeMonthEnd(string $tenantId, string $periodId, string $userId): void
{
    // Create the request
    $request = new PeriodCloseRequest(
        tenantId: $tenantId,
        periodId: $periodId,
        userId: $userId,
        closeType: CloseType::HARD,
        generateStatements: true,
        calculateRatios: true
    );
    
    // Execute the coordinated close process
    $result = $this->periodCloseCoordinator->execute($request);
    
    if ($result->isSuccessful()) {
        echo "Period closed successfully!";
        echo "Statements generated: " . count($result->getStatements());
        echo "Ratios calculated: " . count($result->getRatios());
    } else {
        foreach ($result->getErrors() as $error) {
            echo "Error: " . $error->getMessage();
        }
    }
}
```

---

## Orchestration Flow Diagram

```
┌─────────────────────────────────────────────────────────────────────────┐
│                     ACCOUNTING OPERATIONS ORCHESTRATOR                   │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                          │
│   ┌───────────────────┐    ┌───────────────────┐    ┌─────────────────┐ │
│   │ Period Close      │    │ Statement Gen     │    │ Consolidation   │ │
│   │ Coordinator       │    │ Coordinator       │    │ Coordinator     │ │
│   └─────────┬─────────┘    └─────────┬─────────┘    └────────┬────────┘ │
│             │                        │                       │          │
│   ┌─────────▼──────────────────────────────────────────────────────────┐│
│   │                          DATA PROVIDERS                             ││
│   │  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐  ┌───────────┐  ││
│   │  │FinanceData  │  │ BudgetData  │  │Consolidation│  │ RatioData │  ││
│   │  │  Provider   │  │  Provider   │  │DataProvider │  │ Provider  │  ││
│   │  └─────────────┘  └─────────────┘  └─────────────┘  └───────────┘  ││
│   └────────────────────────────────────────────────────────────────────┘│
│                                    │                                     │
└────────────────────────────────────┼─────────────────────────────────────┘
                                     │
          ┌──────────────────────────┼──────────────────────────┐
          │                          │                          │
          ▼                          ▼                          ▼
┌──────────────────┐   ┌──────────────────┐   ┌──────────────────┐
│ AccountPeriodClose│   │FinancialStatements│   │AccountConsolidation│
├──────────────────┤   ├──────────────────┤   ├──────────────────┤
│• CloseReadiness  │   │• StatementBuilder│   │• ConsolidationEngine│
│• ClosingEntryGen │   │• StatementValidator│  │• EliminationRules│
│• ReopenValidator │   │• ComplianceLayouts│   │• CurrencyTranslator│
└──────────────────┘   └──────────────────┘   └──────────────────┘
          │                          │                          │
          ▼                          ▼                          ▼
┌──────────────────┐   ┌──────────────────┐   
│AccountVarianceAnalysis│   │ FinancialRatios  │   
├──────────────────┤   ├──────────────────┤   
│• VarianceCalculator│  │• RatioCalculator │   
│• TrendAnalyzer   │   │• DuPontAnalyzer  │   
│• SignificanceEval│   │• Benchmarker     │   
└──────────────────┘   └──────────────────┘   
```

---

## Integration with Atomic Packages

| Atomic Package | Integration Purpose |
|----------------|---------------------|
| `Nexus\Finance` | GL data, journal entries |
| `Nexus\Period` | Period definitions, status |
| `Nexus\Budget` | Budget amounts for variance |
| `Nexus\Currency` | Exchange rates |
| `Nexus\Backoffice` | Entity structure |
| `Nexus\Receivable` | AR subledger status |
| `Nexus\Payable` | AP subledger status |
| `Nexus\Inventory` | Inventory subledger status |
| `Nexus\AuditLogger` | Audit trail logging |
| `Nexus\Notifier` | Alerts and notifications |
| `Nexus\Workflow` | Approval workflows |

---

## Related Documentation

- [ARCHITECTURE.md](../../ARCHITECTURE.md) - Overall system architecture
- [CODING_GUIDELINES.md](../../CODING_GUIDELINES.md) - Coding standards
- [Nexus Packages Reference](../../docs/NEXUS_PACKAGES_REFERENCE.md) - All available packages
- [ACCOUNTING_OPERATIONS_FINAL_STRUCTURE.md](../../ACCOUNTING_OPERATIONS_FINAL_STRUCTURE.md) - Detailed structure

---

## License

MIT License - See [LICENSE](LICENSE) for details.
