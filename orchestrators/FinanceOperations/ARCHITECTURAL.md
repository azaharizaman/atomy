# FinanceOperations Orchestrator Architecture

## 1. Package Overview

### Purpose

The **Nexus\FinanceOperations** orchestrator coordinates day-to-day financial operations across multiple Layer 1 atomic packages. While the existing `AccountingOperations` orchestrator handles period-end closing, consolidation, and financial statement generation, `FinanceOperations` focuses on the operational financial activities that occur continuously throughout the accounting period.

This orchestrator serves as the central coordination point for:
- Treasury operations (cash flow, liquidity management)
- Cost accounting operations (cost allocation, product costing)
- Asset depreciation lifecycle management
- Subledger-to-General Ledger (GL) posting consistency
- Cross-package validation and reconciliation

### Scope

The FinanceOperations orchestrator manages these core operational workflows:

| Workflow | Description |
|----------|-------------|
| **Cash Flow Coordination** | Orchestrates treasury operations including cash position monitoring, forecasting, and bank reconciliation |
| **Cost Allocation Coordination** | Coordinates cost center management, cost pool allocation, and product costing across departments |
| **Depreciation Coordination** | Manages asset depreciation calculation, schedule execution, and journal entry posting |
| **GL Posting Coordination** | Ensures subledger (AR, AP, Assets) consistency with the general ledger |
| **Budget vs Actual Tracking** | Monitors budget consumption and flags variances during the period |

### Namespace

```text
Nexus\FinanceOperations
```

### Position in Architecture

| Layer | Role |
|-------|------|
| Layer 2 (Orchestrator) | Cross-package workflow coordination, framework-agnostic |
| Domain | Financial Management |
| Dependencies | Own interfaces (Contracts/), PSR interfaces only |
| Coordinates | Treasury, CostAccounting, FixedAssetDepreciation, ChartOfAccount, JournalEntry, Receivable, Payable, Assets, Budget |

---

## 2. Architecture Principles

This orchestrator adheres strictly to the Layer 2 (Orchestrator) requirements as defined in the root [`ARCHITECTURE.md`](../../ARCHITECTURE.md), specifically Section 4 "The Advanced Orchestrator Pattern" and Section 5 "Orchestrator Interface Segregation".

### 2.1 Framework Agnosticism

The orchestrator contains **zero framework dependencies**:

- ✅ No Laravel, Symfony, or other framework imports in `/src`
- ✅ All external dependencies expressed via interfaces in `Contracts/`
- ✅ Pure PHP 8.3+ with strict typing
- ✅ PSR-3 logging and PSR-14 event dispatching support

### 2.2 Interface Segregation (Critical)

Following Section 5 of ARCHITECTURE.md, this orchestrator **MUST define its own interfaces** and NOT depend on atomic package interfaces directly:

```php
// ✅ CORRECT: Orchestrator defines its own interface
namespace Nexus\FinanceOperations\Contracts;
use Nexus\Treasury\Contracts\TreasuryManagerInterface; // Atomic package interface

interface CashFlowCoordinatorInterface
{
    public function getCashPosition(string $tenantId, string $bankAccountId): CashPositionResult;
}

// Usage in coordinator:
final readonly class CashFlowCoordinator implements CashFlowCoordinatorInterface
{
    public function __construct(
        private TreasuryManagerInterface $treasuryManager, // Injected via atomic interface
    ) {}
}
```

This approach:
- Enables the orchestrator to be published independently
- Allows swapping atomic package implementations via adapters
- Maintains SOLID compliance (ISP, DIP)
- Keeps atomic packages truly atomic

### 2.3 Dependency Injection

All dependencies are injected via constructors:

```php
final readonly class GLPostingCoordinator implements GLPostingCoordinatorInterface
{
    public function __construct(
        private ReceivableQueryInterface $receivableQuery,     // Atomic package interface
        private PayableQueryInterface $payableQuery,          // Atomic package interface
        private JournalEntryPersistInterface $journalEntry,   // Atomic package interface
        private GLPostingRuleRegistry $ruleRegistry,           // Local service
    ) {}
}
```

### 2.4 Immutability

- **Service Classes**: `final readonly class` with constructor property promotion
- **DTOs**: All properties are `readonly`
- **Coordinators**: Stateless, thread-safe design

### 2.5 Single Responsibility

Following the Advanced Orchestrator Pattern, this orchestrator delegates:

| Component | Responsibility | Rule |
| :--- | :--- | :--- |
| **Coordinators** | Traffic management | Directs flow, executes no logic or fetching |
| **DataProviders** | Aggregation | Fetches context from multiple packages into a Context DTO |
| **Rules** | Validation | Single-class business constraints |
| **Services** | Calculations | Heavy lifting and cross-boundary logic |
| **Workflows** | Statefulness | Manages long-running Sagas and state-machine transitions |

---

## 3. Directory Structure

```
orchestrators/FinanceOperations/
├── composer.json
├── README.md
├── ARCHITECTURAL.md
├── REQUIREMENTS.md
├── src/
│   ├── Contracts/              # Orchestrator-defined interfaces
│   │   ├── FinanceWorkflowInterface.php
│   │   ├── FinanceCoordinatorInterface.php
│   │   ├── CashFlowCoordinatorInterface.php
│   │   ├── CostAllocationCoordinatorInterface.php
│   │   ├── DepreciationCoordinatorInterface.php
│   │   ├── GLPostingCoordinatorInterface.php
│   │   ├── BudgetTrackingCoordinatorInterface.php
│   │   ├── TreasuryDataProviderInterface.php
│   │   ├── CostAccountingDataProviderInterface.php
│   │   ├── DepreciationDataProviderInterface.php
│   │   └── GLReconciliationProviderInterface.php
│   │
│   ├── Coordinators/           # Stateless workflow directors
│   │   ├── CashFlowCoordinator.php
│   │   ├── CostAllocationCoordinator.php
│   │   ├── DepreciationCoordinator.php
│   │   ├── GLPostingCoordinator.php
│   │   └── BudgetTrackingCoordinator.php
│   │
│   ├── DataProviders/         # Cross-package data aggregation
│   │   ├── TreasuryDataProvider.php
│   │   ├── CostAccountingDataProvider.php
│   │   ├── DepreciationDataProvider.php
│   │   ├── GLReconciliationProvider.php
│   │   └── BudgetVarianceProvider.php
│   │
│   ├── Services/              # Orchestration services
│   │   ├── CashPositionService.php
│   │   ├── CostAllocationService.php
│   │   ├── DepreciationRunService.php
│   │   ├── GLReconciliationService.php
│   │   └── BudgetMonitoringService.php
│   │
│   ├── Rules/                 # Cross-package validation
│   │   ├── SubledgerClosedRule.php
│   │   ├── PeriodOpenRule.php
│   │   ├── BudgetAvailableRule.php
│   │   ├── GLAccountMappingRule.php
│   │   └── CostCenterActiveRule.php
│   │
│   ├── Workflows/            # Stateful processes (Sagas)
│   │   ├── DepreciationRun/
│   │   │   ├── DepreciationRunWorkflow.php
│   │   │   └── Steps/
│   │   │       ├── CalculateDepreciationStep.php
│   │   │       ├── ValidateDepreciationStep.php
│   │   │       ├── PostToGLStep.php
│   │   │       └── UpdateAssetRegisterStep.php
│   │   ├── CostAllocation/
│   │   │   ├── CostAllocationWorkflow.php
│   │   │   └── Steps/
│   │   │       ├── GatherCostsStep.php
│   │   │       ├── ApplyAllocationRulesStep.php
│   │   │       └── PostAllocatedCostsStep.php
│   │   └── CashReconciliation/
│   │       ├── CashReconciliationWorkflow.php
│   │       └── Steps/
│   │           ├── MatchTransactionsStep.php
│   │           ├── IdentifyDiscrepanciesStep.php
│   │           └── CreateAdjustingEntriesStep.php
│   │
│   ├── DTOs/                  # Request/Response objects
│   │   ├── CashPositionRequest.php
│   │   ├── CashPositionResult.php
│   │   ├── CostAllocationRequest.php
│   │   ├── CostAllocationResult.php
│   │   ├── DepreciationRunRequest.php
│   │   ├── DepreciationRunResult.php
│   │   ├── GLPostingRequest.php
│   │   ├── GLPostingResult.php
│   │   ├── BudgetVarianceRequest.php
│   │   └── BudgetVarianceResult.php
│   │
│   ├── Exceptions/           # Process-specific exceptions
│   │   ├── CoordinationException.php
│   │   ├── CashFlowException.php
│   │   ├── CostAllocationException.php
│   │   ├── DepreciationCoordinationException.php
│   │   ├── GLReconciliationException.php
│   │   └── BudgetTrackingException.php
│   │
│   └── Listeners/           # Event listeners
│       ├── OnDepreciationCalculated.php
│       ├── OnCostAllocated.php
│       ├── OnGLReconciliationCompleted.php
│       └── OnBudgetThresholdExceeded.php
│
└── tests/
    ├── Unit/
    └── Feature/
```

---

## 4. Core Interfaces

### 4.1 FinanceCoordinatorInterface

Base interface for all finance operation coordinators.

```php
interface FinanceCoordinatorInterface
{
    /**
     * Get the coordinator name.
     */
    public function getName(): string;

    /**
     * Check if required data is available.
     */
    public function hasRequiredData(string $tenantId, string $periodId): bool;

    /**
     * Get the supported operations.
     *
     * @return array<string>
     */
    public function getSupportedOperations(): array;
}
```

### 4.2 CashFlowCoordinatorInterface

Coordinates treasury operations including cash position, forecasting, and bank reconciliation.

```php
interface CashFlowCoordinatorInterface
{
    /**
     * Get current cash position for a bank account.
     */
    public function getCashPosition(CashPositionRequest $request): CashPositionResult;

    /**
     * Generate cash flow forecast.
     */
    public function generateForecast(CashFlowForecastRequest $request): CashFlowForecastResult;

    /**
     * Reconcile bank statements.
     */
    public function reconcileBankAccount(BankReconciliationRequest $request): BankReconciliationResult;
}
```

### 4.3 CostAllocationCoordinatorInterface

Coordinates cost center management and cost allocation.

```php
interface CostAllocationCoordinatorInterface
{
    /**
     * Allocate costs to cost centers.
     */
    public function allocateCosts(CostAllocationRequest $request): CostAllocationResult;

    /**
     * Calculate product costs.
     */
    public function calculateProductCost(ProductCostRequest $request): ProductCostResult;

    /**
     * Run periodic cost allocation.
     */
    public function runPeriodicAllocation(PeriodicAllocationRequest $request): PeriodicAllocationResult;
}
```

### 4.4 DepreciationCoordinatorInterface

Manages asset depreciation lifecycle.

```php
interface DepreciationCoordinatorInterface
{
    /**
     * Run depreciation calculation for a period.
     */
    public function runDepreciation(DepreciationRunRequest $request): DepreciationRunResult;

    /**
     * Generate depreciation schedules.
     */
    public function generateSchedules(DepreciationScheduleRequest $request): DepreciationScheduleResult;

    /**
     * Process asset revaluation.
     */
    public function processRevaluation(RevaluationRequest $request): RevaluationResult;
}
```

### 4.5 GLPostingCoordinatorInterface

Ensures subledger-to-GL consistency.

```php
interface GLPostingCoordinatorInterface
{
    /**
     * Post subledger transactions to GL.
     */
    public function postToGL(GLPostingRequest $request): GLPostingResult;

    /**
     * Reconcile subledger with GL.
     */
    public function reconcileWithGL(GLReconciliationRequest $request): GLReconciliationResult;

    /**
     * Validate posting consistency.
     */
    public function validateConsistency(ConsistencyCheckRequest $request): ConsistencyCheckResult;
}
```

### 4.6 BudgetTrackingCoordinatorInterface

Monitors budget vs actuals during the period.

```php
interface BudgetTrackingCoordinatorInterface
{
    /**
     * Check budget availability.
     */
    public function checkBudgetAvailable(BudgetCheckRequest $request): BudgetCheckResult;

    /**
     * Calculate budget variances.
     */
    public function calculateVariances(BudgetVarianceRequest $request): BudgetVarianceResult;

    /**
     * Alert on budget threshold exceeded.
     */
    public function checkThresholds(BudgetThresholdRequest $request): BudgetThresholdResult;
}
```

---

## 5. Data Flow

### 5.1 Cross-Package Data Aggregation

The orchestrator uses DataProviders to aggregate data from multiple atomic packages:

```
┌─────────────────────────────────────────────────────────────────┐
│                    FinanceOperations                            │
│                                                                  │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐          │
│  │   Coord.     │  │   Coord.     │  │   Coord.     │          │
│  │  (Traffic)   │  │  (Traffic)   │  │  (Traffic)   │          │
│  └──────┬───────┘  └──────┬───────┘  └──────┬───────┘          │
│         │                 │                 │                   │
│         ▼                 ▼                 ▼                   │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │                  DataProviders                           │   │
│  │  (Aggregates data from multiple packages)               │   │
│  └─────────────────────────────────────────────────────────┘   │
│                            │                                     │
└────────────────────────────┼────────────────────────────────────┘
                             │
        ┌────────────────────┼────────────────────┐
        │                    │                    │
        ▼                    ▼                    ▼
┌───────────────┐   ┌───────────────┐   ┌───────────────┐
│   Treasury    │   │CostAccounting │   │ FixedAsset   │
│   Package     │   │   Package     │   │ Depreciation  │
└───────────────┘   └───────────────┘   └───────────────┘
        │                    │                    │
        ▼                    ▼                    ▼
┌───────────────┐   ┌───────────────┐   ┌───────────────┐
│  ChartOfAcct  │   │    Budget     │   │    Assets     │
│  JournalEntry │   │    Inventory  │   │  JournalEntry │
│   Currency   │   │   Manufacturing│   │  ChartOfAcct  │
└───────────────┘   └───────────────┘   └───────────────┘
```

### 5.2 GL Posting Flow

```
┌─────────────┐    ┌─────────────┐    ┌─────────────┐
│  Receivable │    │   Payable   │    │   Assets    │
│  Subledger  │    │  Subledger  │    │  Subledger  │
└──────┬──────┘    └──────┬──────┘    └──────┬──────┘
       │                  │                   │
       └──────────────────┼───────────────────┘
                          ▼
          ┌─────────────────────────────┐
          │   GLPostingCoordinator      │
          │   (Validates & Posts)       │
          └──────────────┬──────────────┘
                         ▼
          ┌─────────────────────────────┐
          │   JournalEntry Package     │
          │   (General Ledger)          │
          └─────────────────────────────┘
```

---

## 6. Dependency Management

### 6.1 Orchestrator Interface Segregation

Following Section 5 of ARCHITECTURE.md:

| Layer | Can Depend On | Cannot Depend On |
|-------|---------------|------------------|
| **Atomic Packages** | Common, PSR interfaces | Other atomic packages, Orchestrators, Adapters |
| **Orchestrators** | PSR interfaces, **Own interfaces only** | Atomic packages directly, Adapters, Frameworks |
| **Adapters** | Everything (Atomic packages, Orchestrator interfaces) | Nothing (they are the leaf) |

### 6.2 Package Dependencies

The FinanceOperations orchestrator coordinates these atomic packages:

| Package | Purpose | Key Interfaces Used |
|---------|---------|---------------------|
| `Nexus\Treasury` | Cash flow, bank management, liquidity | TreasuryManagerInterface, CashFlowForecasterInterface |
| `Nexus\CostAccounting` | Cost centers, product costing, cost allocation | CostAccountingManagerInterface, CostAllocationEngineInterface |
| `Nexus\FixedAssetDepreciation` | Depreciation methods, schedules | DepreciationManagerInterface, DepreciationCalculatorInterface |
| `Nexus\ChartOfAccount` | Chart of accounts management | AccountQueryInterface |
| `Nexus\JournalEntry` | Journal entry processing | LedgerQueryInterface, LedgerPersistInterface |
| `Nexus\Receivable` | Customer invoicing | InvoiceQueryInterface |
| `Nexus\Payable` | Vendor bills | BillQueryInterface |
| `Nexus\Assets` | Fixed asset management | AssetQueryInterface |
| `Nexus\Budget` | Budget planning | BudgetQueryInterface |
| `Nexus\Period` | Fiscal period management | PeriodManagerInterface |
| `Nexus\Currency` | Multi-currency | CurrencyManagerInterface |

---

## 7. Error Handling Patterns

### 7.1 Domain-Specific Exceptions

Each coordinator defines its own exception hierarchy:

```php
// Base exception for all finance operations
class CoordinationException extends \RuntimeException
{
    public function __construct(
        string $message,
        private string $coordinatorName,
        private array $context = [],
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
    }
}

// Specific exceptions
class CashFlowException extends CoordinationException {}
class CostAllocationException extends CoordinationException {}
class DepreciationCoordinationException extends CoordinationException {}
class GLReconciliationException extends CoordinationException {}
class BudgetTrackingException extends CoordinationException {}
```

### 7.2 Error Recovery

Workflows implement saga patterns for recoverable errors:

```php
interface FinanceWorkflowInterface
{
    /**
     * Get current workflow step.
     */
    public function getCurrentStep(): ?string;

    /**
     * Check if the workflow can be retried.
     */
    public function canRetry(): bool;

    /**
     * Retry from failed step.
     */
    public function retry(): WorkflowResult;
}
```

---

## 8. Extensibility Points

### 8.1 Custom DataProviders

New data sources can be added by implementing DataProvider interfaces:

```php
interface TreasuryDataProviderInterface
{
    public function getCashPosition(string $tenantId, string $bankAccountId): CashPositionData;
    public function getCashFlowForecast(string $tenantId, string $periodId): CashFlowForecastData;
}

// Implementation
final readonly class CustomTreasuryDataProvider implements TreasuryDataProviderInterface
{
    public function __construct(
        private TreasuryManagerInterface $treasury,
    ) {}

    public function getCashPosition(string $tenantId, string $bankAccountId): CashPositionData
    {
        // Custom aggregation logic
    }
}
```

### 8.2 Custom Rules

Cross-package validation rules can be added:

```php
final readonly class BudgetAvailableRule
{
    public function __construct(
        private BudgetQueryInterface $budgetQuery,
    ) {}

    public function check(string $tenantId, string $budgetId, float $amount): RuleResult
    {
        $available = $this->budgetQuery->getAvailableAmount($tenantId, $budgetId);
        return new RuleResult(
            passed: $available >= $amount,
            message: $available >= $amount 
                ? 'Budget available' 
                : "Insufficient budget: {$available} available, {$amount} required"
        );
    }
}
```

### 8.3 Event Listeners

The orchestrator can react to events from atomic packages:

```php
final readonly class OnDepreciationCalculated
{
    public function __construct(
        private GLPostingCoordinatorInterface $glPosting,
    ) {}

    public function handle(DepreciationCalculatedEvent $event): void
    {
        // Automatically post depreciation to GL
        $this->glPosting->postDepreciation($event->depreciationRun);
    }
}
```

---

## 9. Testing Approach

### 9.1 Unit Testing

- Test coordinators in isolation using mock interfaces
- Test DataProviders with mocked atomic package interfaces
- Test rules with sample data

### 9.2 Integration Testing

- Test coordinator orchestration with real atomic package implementations
- Test data flow between packages
- Test error handling and recovery

### 9.3 Contract Testing

- Define interface contracts between orchestrator and atomic packages
- Ensure atomic package adapters satisfy required interfaces

---

## 10. Relationship with AccountingOperations

The FinanceOperations and AccountingOperations orchestrators complement each other:

| Aspect | FinanceOperations | AccountingOperations |
|--------|------------------|----------------------|
| **Focus** | Day-to-day operations | Period-end processes |
| **Timing** | Continuous throughout period | End of period |
| **Examples** | Cash flow, cost allocation, depreciation runs | Period close, consolidation, statements |
| **Packages** | Treasury, CostAccounting, FixedAssetDepreciation | AccountPeriodClose, AccountConsolidation, FinancialStatements |
| **State** | Operational, frequent changes | Analytical, static after close |

Both orchestrators use the same foundational packages (ChartOfAccount, JournalEntry, Period) but for different purposes.
