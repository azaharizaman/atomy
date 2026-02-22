# FinanceOperations Orchestrator

[![PHP Version](https://img.shields.io/badge/php-%5E8.3-8892BF.svg)](https://php.net/)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

The **FinanceOperations** orchestrator provides cross-package workflow coordination for day-to-day financial operations within the Nexus ecosystem. It implements the Advanced Orchestrator Pattern with Saga-based workflows for distributed transaction management.

## Features

- **Saga Pattern Workflows**: Distributed transaction coordination with compensation logic
- **Cash Flow Management**: Cash position tracking, forecasting, and bank reconciliation
- **Cost Allocation**: Cost pool allocation, product costing, and periodic allocation
- **Depreciation Processing**: Fixed asset depreciation runs and schedule generation
- **GL Reconciliation**: Subledger-to-GL posting and consistency validation
- **Budget Tracking**: Budget availability checks, variance analysis, and threshold monitoring

## Architecture

This orchestrator follows the **Three-Layer Architecture** defined in ARCHITECTURE.md:

```
┌─────────────────────────────────────────────────────────┐
│                    Adapters (L3)                        │
│   Implements orchestrator interfaces using atomic pkgs  │
└─────────────────────────────────────────────────────────┘
                           ▲ implements
┌─────────────────────────────────────────────────────────┐
│                 FinanceOperations (L2)                  │
│   - Defines own interfaces in Contracts/                │
│   - Depends only on: php, psr/log, psr/event-dispatcher │
│   - Saga-based workflow coordination                    │
└─────────────────────────────────────────────────────────┘
                           ▲ uses via interfaces
┌─────────────────────────────────────────────────────────┐
│                Atomic Packages (L1)                     │
│   - Treasury, CostAccounting, FixedAssetDepreciation    │
│   - ChartOfAccount, JournalEntry, Receivable, Payable   │
│   - Assets, Budget                                      │
└─────────────────────────────────────────────────────────┘
```

### Interface Segregation

Following ARCHITECTURE.md Section 3.1, this orchestrator:
- Defines its own interfaces in `Contracts/`
- Does NOT depend on atomic package interfaces directly
- Can be published as a standalone composer package
- Allows swapping atomic package implementations via adapters

## Installation

```bash
composer require nexus/finance-operations
```

### Framework Integration

This package is **framework-agnostic** and depends only on PSR interfaces. The following examples show how to register the package's services in various frameworks. Adapt these examples to your application's dependency injection container.

#### Laravel

Create a service provider in your application to register FinanceOperations services:

```php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Nexus\FinanceOperations\Contracts\CashFlowCoordinatorInterface;
use Nexus\FinanceOperations\Contracts\CostAllocationCoordinatorInterface;
use Nexus\FinanceOperations\Contracts\DepreciationCoordinatorInterface;
use Nexus\FinanceOperations\Contracts\GLPostingCoordinatorInterface;
use Nexus\FinanceOperations\Contracts\BudgetTrackingCoordinatorInterface;
use Nexus\FinanceOperations\Contracts\TreasuryDataProviderInterface;
use Nexus\FinanceOperations\Contracts\CostAccountingDataProviderInterface;
use Nexus\FinanceOperations\Contracts\DepreciationDataProviderInterface;
use Nexus\FinanceOperations\Contracts\GLReconciliationProviderInterface;
use Nexus\FinanceOperations\Contracts\BudgetVarianceProviderInterface;
use Nexus\FinanceOperations\Coordinators\CashFlowCoordinator;
use Nexus\FinanceOperations\Coordinators\CostAllocationCoordinator;
use Nexus\FinanceOperations\Coordinators\DepreciationCoordinator;
use Nexus\FinanceOperations\Coordinators\GLPostingCoordinator;
use Nexus\FinanceOperations\Coordinators\BudgetTrackingCoordinator;
use Nexus\FinanceOperations\DataProviders\TreasuryDataProvider;
use Nexus\FinanceOperations\DataProviders\CostAccountingDataProvider;
use Nexus\FinanceOperations\DataProviders\DepreciationDataProvider;
use Nexus\FinanceOperations\DataProviders\GLReconciliationProvider;
use Nexus\FinanceOperations\DataProviders\BudgetVarianceProvider;

class FinanceOperationsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register data providers (singletons)
        $this->app->singleton(TreasuryDataProviderInterface::class, TreasuryDataProvider::class);
        $this->app->singleton(CostAccountingDataProviderInterface::class, CostAccountingDataProvider::class);
        $this->app->singleton(DepreciationDataProviderInterface::class, DepreciationDataProvider::class);
        $this->app->singleton(GLReconciliationProviderInterface::class, GLReconciliationProvider::class);
        $this->app->singleton(BudgetVarianceProviderInterface::class, BudgetVarianceProvider::class);

        // Register coordinators (singletons)
        $this->app->singleton(CashFlowCoordinatorInterface::class, CashFlowCoordinator::class);
        $this->app->singleton(CostAllocationCoordinatorInterface::class, CostAllocationCoordinator::class);
        $this->app->singleton(DepreciationCoordinatorInterface::class, DepreciationCoordinator::class);
        $this->app->singleton(GLPostingCoordinatorInterface::class, GLPostingCoordinator::class);
        $this->app->singleton(BudgetTrackingCoordinatorInterface::class, BudgetTrackingCoordinator::class);

        // Register services
        $services = [
            \Nexus\FinanceOperations\Services\CashPositionService::class,
            \Nexus\FinanceOperations\Services\CostAllocationService::class,
            \Nexus\FinanceOperations\Services\DepreciationRunService::class,
            \Nexus\FinanceOperations\Services\GLReconciliationService::class,
            \Nexus\FinanceOperations\Services\BudgetMonitoringService::class,
        ];

        foreach ($services as $service) {
            $this->app->singleton($service);
        }

        // Register rules
        $rules = [
            \Nexus\FinanceOperations\Rules\SubledgerClosedRule::class,
            \Nexus\FinanceOperations\Rules\PeriodOpenRule::class,
            \Nexus\FinanceOperations\Rules\BudgetAvailableRule::class,
            \Nexus\FinanceOperations\Rules\GLAccountMappingRule::class,
            \Nexus\FinanceOperations\Rules\CostCenterActiveRule::class,
        ];

        foreach ($rules as $rule) {
            $this->app->singleton($rule);
        }

        // Register listeners
        $listeners = [
            \Nexus\FinanceOperations\Listeners\OnDepreciationCalculated::class,
            \Nexus\FinanceOperations\Listeners\OnCostAllocated::class,
            \Nexus\FinanceOperations\Listeners\OnGLReconciliationCompleted::class,
            \Nexus\FinanceOperations\Listeners\OnBudgetThresholdExceeded::class,
        ];

        foreach ($listeners as $listener) {
            $this->app->singleton($listener);
        }
    }
}
```

Register in `config/app.php`:

```php
'providers' => [
    // ...
    App\Providers\FinanceOperationsServiceProvider::class,
],
```

#### Symfony

Register services in your `services.yaml`:

```yaml
services:
    # Data Providers
    Nexus\FinanceOperations\Contracts\TreasuryDataProviderInterface:
        class: Nexus\FinanceOperations\DataProviders\TreasuryDataProvider
    
    Nexus\FinanceOperations\Contracts\CostAccountingDataProviderInterface:
        class: Nexus\FinanceOperations\DataProviders\CostAccountingDataProvider
    
    Nexus\FinanceOperations\Contracts\DepreciationDataProviderInterface:
        class: Nexus\FinanceOperations\DataProviders\DepreciationDataProvider
    
    Nexus\FinanceOperations\Contracts\GLReconciliationProviderInterface:
        class: Nexus\FinanceOperations\DataProviders\GLReconciliationProvider
    
    Nexus\FinanceOperations\Contracts\BudgetVarianceProviderInterface:
        class: Nexus\FinanceOperations\DataProviders\BudgetVarianceProvider
    
    # Coordinators
    Nexus\FinanceOperations\Contracts\CashFlowCoordinatorInterface:
        class: Nexus\FinanceOperations\Coordinators\CashFlowCoordinator
    
    Nexus\FinanceOperations\Contracts\CostAllocationCoordinatorInterface:
        class: Nexus\FinanceOperations\Coordinators\CostAllocationCoordinator
    
    Nexus\FinanceOperations\Contracts\DepreciationCoordinatorInterface:
        class: Nexus\FinanceOperations\Coordinators\DepreciationCoordinator
    
    Nexus\FinanceOperations\Contracts\GLPostingCoordinatorInterface:
        class: Nexus\FinanceOperations\Coordinators\GLPostingCoordinator
    
    Nexus\FinanceOperations\Contracts\BudgetTrackingCoordinatorInterface:
        class: Nexus\FinanceOperations\Coordinators\BudgetTrackingCoordinator
    
    # Services
    Nexus\FinanceOperations\Services\CashPositionService:
        shared: true
    
    # ... other services, rules, and listeners
```

#### Service Binding Summary

| Interface | Purpose |
|-----------|---------|
| `CashFlowCoordinatorInterface` | Cash flow operations coordination |
| `CostAllocationCoordinatorInterface` | Cost allocation coordination |
| `DepreciationCoordinatorInterface` | Depreciation operations coordination |
| `GLPostingCoordinatorInterface` | GL posting coordination |
| `BudgetTrackingCoordinatorInterface` | Budget tracking coordination |
| `TreasuryDataProviderInterface` | Treasury data aggregation |
| `CostAccountingDataProviderInterface` | Cost accounting data aggregation |
| `DepreciationDataProviderInterface` | Depreciation data aggregation |
| `GLReconciliationProviderInterface` | GL reconciliation data aggregation |
| `BudgetVarianceProviderInterface` | Budget variance data aggregation |

---

## Directory Structure

```
src/
├── Contracts/           # Interface definitions
│   ├── FinanceCoordinatorInterface.php
│   ├── CashFlowCoordinatorInterface.php
│   ├── CostAllocationCoordinatorInterface.php
│   ├── DepreciationCoordinatorInterface.php
│   ├── GLPostingCoordinatorInterface.php
│   ├── BudgetTrackingCoordinatorInterface.php
│   ├── TreasuryDataProviderInterface.php
│   ├── CostAccountingDataProviderInterface.php
│   ├── DepreciationDataProviderInterface.php
│   ├── GLReconciliationProviderInterface.php
│   ├── BudgetVarianceProviderInterface.php
│   ├── FinanceWorkflowInterface.php
│   ├── WorkflowStepInterface.php
│   └── RuleInterface.php
├── Coordinators/        # Traffic management
│   ├── CashFlowCoordinator.php
│   ├── CostAllocationCoordinator.php
│   ├── DepreciationCoordinator.php
│   ├── GLPostingCoordinator.php
│   └── BudgetTrackingCoordinator.php
├── DataProviders/       # Context aggregation
│   ├── TreasuryDataProvider.php
│   ├── CostAccountingDataProvider.php
│   ├── DepreciationDataProvider.php
│   ├── GLReconciliationProvider.php
│   └── BudgetVarianceProvider.php
├── DTOs/                # Data Transfer Objects
│   ├── CashFlow/
│   ├── CostAllocation/
│   ├── Depreciation/
│   ├── GLPosting/
│   ├── BudgetTracking/
│   └── ... (shared DTOs)
├── Services/            # Orchestration services
│   ├── CashPositionService.php
│   ├── CostAllocationService.php
│   ├── DepreciationRunService.php
│   ├── GLReconciliationService.php
│   └── BudgetMonitoringService.php
├── Rules/               # Business constraints
│   ├── SubledgerClosedRule.php
│   ├── PeriodOpenRule.php
│   ├── BudgetAvailableRule.php
│   ├── GLAccountMappingRule.php
│   └── CostCenterActiveRule.php
├── Workflows/           # Saga implementations
│   ├── AbstractFinanceWorkflow.php
│   ├── DepreciationRun/
│   ├── CostAllocation/
│   └── CashReconciliation/
├── Listeners/           # Event listeners
│   ├── OnDepreciationCalculated.php
│   ├── OnCostAllocated.php
│   ├── OnGLReconciliationCompleted.php
│   └── OnBudgetThresholdExceeded.php
└── Exceptions/          # Domain exceptions
    ├── CoordinationException.php
    ├── CashFlowException.php
    ├── CostAllocationException.php
    ├── DepreciationCoordinationException.php
    ├── GLReconciliationException.php
    └── BudgetTrackingException.php
```

---

## Contracts (Interfaces)

### Core Orchestrator Interfaces

#### `FinanceCoordinatorInterface`

Base interface for all finance operation coordinators.

```php
<?php

namespace Nexus\FinanceOperations\Contracts;

/**
 * Base contract for finance operation coordinators.
 *
 * This interface follows the Interface Segregation Principle (ISP) by defining
 * only the core methods that all finance coordinators must implement.
 *
 * @see ARCHITECTURE.md Section 5: Orchestrator Interface Segregation
 * @since 1.0.0
 */
interface FinanceCoordinatorInterface
{
    /**
     * Get the coordinator name for identification and logging.
     */
    public function getName(): string;

    /**
     * Check if required data is available for the coordinator's operations.
     *
     * @param string $tenantId The tenant identifier
     * @param string $periodId The accounting period identifier
     * @return bool True if all required data is available
     */
    public function hasRequiredData(string $tenantId, string $periodId): bool;

    /**
     * Get the list of operations this coordinator supports.
     *
     * @return array<string> List of supported operation names
     */
    public function getSupportedOperations(): array;
}
```

#### `CashFlowCoordinatorInterface`

Coordinates treasury operations including cash position, forecasting, and bank reconciliation.

```php
<?php

namespace Nexus\FinanceOperations\Contracts;

use Nexus\FinanceOperations\DTOs\CashPositionRequest;
use Nexus\FinanceOperations\DTOs\CashPositionResult;
use Nexus\FinanceOperations\DTOs\CashFlowForecastRequest;
use Nexus\FinanceOperations\DTOs\CashFlowForecastResult;
use Nexus\FinanceOperations\DTOs\BankReconciliationRequest;
use Nexus\FinanceOperations\DTOs\BankReconciliationResult;

/**
 * Contract for cash flow coordination operations.
 *
 * @since 1.0.0
 */
interface CashFlowCoordinatorInterface extends FinanceCoordinatorInterface
{
    /**
     * Get current cash position for a bank account.
     */
    public function getCashPosition(CashPositionRequest $request): CashPositionResult;

    /**
     * Generate cash flow forecast for a period.
     */
    public function generateForecast(CashFlowForecastRequest $request): CashFlowForecastResult;

    /**
     * Reconcile bank statements with internal records.
     */
    public function reconcileBankAccount(BankReconciliationRequest $request): BankReconciliationResult;
}
```

#### `CostAllocationCoordinatorInterface`

Coordinates cost center management and cost allocation operations.

```php
<?php

namespace Nexus\FinanceOperations\Contracts;

use Nexus\FinanceOperations\DTOs\CostAllocationRequest;
use Nexus\FinanceOperations\DTOs\CostAllocationResult;
use Nexus\FinanceOperations\DTOs\ProductCostRequest;
use Nexus\FinanceOperations\DTOs\ProductCostResult;
use Nexus\FinanceOperations\DTOs\PeriodicAllocationRequest;
use Nexus\FinanceOperations\DTOs\PeriodicAllocationResult;

/**
 * Contract for cost allocation coordination operations.
 *
 * @since 1.0.0
 */
interface CostAllocationCoordinatorInterface extends FinanceCoordinatorInterface
{
    /**
     * Allocate costs from cost pools to cost centers.
     */
    public function allocateCosts(CostAllocationRequest $request): CostAllocationResult;

    /**
     * Calculate product costs including material, labor, and overhead.
     */
    public function calculateProductCost(ProductCostRequest $request): ProductCostResult;

    /**
     * Run periodic cost allocation for a period.
     */
    public function runPeriodicAllocation(PeriodicAllocationRequest $request): PeriodicAllocationResult;
}
```

#### `DepreciationCoordinatorInterface`

Manages asset depreciation lifecycle operations.

```php
<?php

namespace Nexus\FinanceOperations\Contracts;

use Nexus\FinanceOperations\DTOs\DepreciationRunRequest;
use Nexus\FinanceOperations\DTOs\DepreciationRunResult;
use Nexus\FinanceOperations\DTOs\DepreciationScheduleRequest;
use Nexus\FinanceOperations\DTOs\DepreciationScheduleResult;
use Nexus\FinanceOperations\DTOs\RevaluationRequest;
use Nexus\FinanceOperations\DTOs\RevaluationResult;

/**
 * Contract for depreciation coordination operations.
 *
 * @since 1.0.0
 */
interface DepreciationCoordinatorInterface extends FinanceCoordinatorInterface
{
    /**
     * Run depreciation calculation for a period.
     */
    public function runDepreciation(DepreciationRunRequest $request): DepreciationRunResult;

    /**
     * Generate depreciation schedules for assets.
     */
    public function generateSchedules(DepreciationScheduleRequest $request): DepreciationScheduleResult;

    /**
     * Process asset revaluation for fair value adjustments.
     */
    public function processRevaluation(RevaluationRequest $request): RevaluationResult;
}
```

#### `GLPostingCoordinatorInterface`

Ensures subledger-to-GL consistency through posting and reconciliation.

```php
<?php

namespace Nexus\FinanceOperations\Contracts;

use Nexus\FinanceOperations\DTOs\GLPostingRequest;
use Nexus\FinanceOperations\DTOs\GLPostingResult;
use Nexus\FinanceOperations\DTOs\GLReconciliationRequest;
use Nexus\FinanceOperations\DTOs\GLReconciliationResult;
use Nexus\FinanceOperations\DTOs\ConsistencyCheckRequest;
use Nexus\FinanceOperations\DTOs\ConsistencyCheckResult;

/**
 * Contract for GL posting coordination operations.
 *
 * @since 1.0.0
 */
interface GLPostingCoordinatorInterface extends FinanceCoordinatorInterface
{
    /**
     * Post subledger transactions to the general ledger.
     */
    public function postToGL(GLPostingRequest $request): GLPostingResult;

    /**
     * Reconcile subledger balances with GL balances.
     */
    public function reconcileWithGL(GLReconciliationRequest $request): GLReconciliationResult;

    /**
     * Validate posting consistency across subledgers and GL.
     */
    public function validateConsistency(ConsistencyCheckRequest $request): ConsistencyCheckResult;
}
```

#### `BudgetTrackingCoordinatorInterface`

Handles budget monitoring and variance analysis operations.

```php
<?php

namespace Nexus\FinanceOperations\Contracts;

use Nexus\FinanceOperations\DTOs\BudgetCheckRequest;
use Nexus\FinanceOperations\DTOs\BudgetCheckResult;
use Nexus\FinanceOperations\DTOs\BudgetVarianceRequest;
use Nexus\FinanceOperations\DTOs\BudgetVarianceResult;
use Nexus\FinanceOperations\DTOs\BudgetThresholdRequest;
use Nexus\FinanceOperations\DTOs\BudgetThresholdResult;

/**
 * Contract for budget tracking coordination operations.
 *
 * @since 1.0.0
 */
interface BudgetTrackingCoordinatorInterface extends FinanceCoordinatorInterface
{
    /**
     * Check budget availability before committing funds.
     */
    public function checkBudgetAvailable(BudgetCheckRequest $request): BudgetCheckResult;

    /**
     * Calculate budget variances for a period.
     */
    public function calculateVariances(BudgetVarianceRequest $request): BudgetVarianceResult;

    /**
     * Check budget thresholds and generate alerts.
     */
    public function checkThresholds(BudgetThresholdRequest $request): BudgetThresholdResult;
}
```

### Data Provider Interfaces

#### `TreasuryDataProviderInterface`

```php
<?php

namespace Nexus\FinanceOperations\Contracts;

/**
 * Contract for treasury data provider.
 *
 * @since 1.0.0
 */
interface TreasuryDataProviderInterface
{
    /**
     * Get cash position for a bank account.
     *
     * @return array<string, mixed> Cash position data
     */
    public function getCashPosition(string $tenantId, string $bankAccountId): array;

    /**
     * Get cash flow forecast data for a period.
     *
     * @return array<string, mixed> Cash flow forecast data
     */
    public function getCashFlowForecast(string $tenantId, string $periodId): array;

    /**
     * Get bank reconciliation data for a bank account.
     *
     * @return array<string, mixed> Bank reconciliation data
     */
    public function getBankReconciliationData(string $tenantId, string $bankAccountId): array;

    /**
     * Get all bank accounts for a tenant.
     *
     * @return array<int, array<string, mixed>> List of bank accounts
     */
    public function getBankAccounts(string $tenantId): array;
}
```

#### `CostAccountingDataProviderInterface`

```php
<?php

namespace Nexus\FinanceOperations\Contracts;

/**
 * Contract for cost accounting data provider.
 *
 * @since 1.0.0
 */
interface CostAccountingDataProviderInterface
{
    /**
     * Get cost pool summary for allocation.
     */
    public function getCostPoolSummary(string $tenantId, string $poolId): array;

    /**
     * Get allocated costs for a period.
     */
    public function getAllocatedCosts(string $tenantId, string $periodId): array;

    /**
     * Get product cost data for costing.
     */
    public function getProductCostData(string $tenantId, string $productId): array;

    /**
     * Get cost center summary for analysis.
     */
    public function getCostCenterSummary(string $tenantId, string $costCenterId): array;
}
```

#### `DepreciationDataProviderInterface`

```php
<?php

namespace Nexus\FinanceOperations\Contracts;

/**
 * Contract for depreciation data provider.
 *
 * @since 1.0.0
 */
interface DepreciationDataProviderInterface
{
    /**
     * Get depreciation run summary for a period.
     */
    public function getDepreciationRunSummary(string $tenantId, string $periodId): array;

    /**
     * Get asset book values for depreciation calculation.
     */
    public function getAssetBookValues(string $tenantId, array $assetIds): array;

    /**
     * Get depreciation schedules for an asset.
     */
    public function getDepreciationSchedules(string $tenantId, string $assetId): array;
}
```

#### `GLReconciliationProviderInterface`

```php
<?php

namespace Nexus\FinanceOperations\Contracts;

/**
 * Contract for GL reconciliation data provider.
 *
 * @since 1.0.0
 */
interface GLReconciliationProviderInterface
{
    /**
     * Get subledger balance for reconciliation.
     */
    public function getSubledgerBalance(string $tenantId, string $periodId, string $subledgerType): array;

    /**
     * Get GL balance for reconciliation.
     */
    public function getGLBalance(string $tenantId, string $periodId, string $accountId): array;

    /**
     * Get reconciliation discrepancies for a period.
     */
    public function getDiscrepancies(string $tenantId, string $periodId): array;

    /**
     * Get reconciliation status for a period.
     */
    public function getReconciliationStatus(string $tenantId, string $periodId): array;
}
```

#### `BudgetVarianceProviderInterface`

```php
<?php

namespace Nexus\FinanceOperations\Contracts;

/**
 * Contract for budget variance data provider.
 *
 * @since 1.0.0
 */
interface BudgetVarianceProviderInterface
{
    /**
     * Get budget data for a period.
     */
    public function getBudgetData(string $tenantId, string $periodId, ?string $budgetVersionId = null): array;

    /**
     * Get actual data for a period.
     */
    public function getActualData(string $tenantId, string $periodId): array;

    /**
     * Get variance analysis for a period.
     */
    public function getVarianceAnalysis(string $tenantId, string $periodId, ?string $budgetVersionId = null): array;
}
```

### Workflow Interfaces

#### `FinanceWorkflowInterface`

```php
<?php

namespace Nexus\FinanceOperations\Contracts;

use Nexus\FinanceOperations\DTOs\WorkflowResult;

/**
 * Contract for finance workflow execution following the Saga pattern.
 *
 * @since 1.0.0
 */
interface FinanceWorkflowInterface
{
    /**
     * Get the workflow name for identification and logging.
     */
    public function getName(): string;

    /**
     * Check if the workflow can be started with given context.
     *
     * @param array<string, mixed> $context The workflow context
     */
    public function canStart(array $context): bool;

    /**
     * Execute the workflow with given context.
     */
    public function execute(array $context): WorkflowResult;

    /**
     * Compensate (rollback) a completed workflow.
     */
    public function compensate(WorkflowResult $result): void;

    /**
     * Get the current workflow step name.
     */
    public function getCurrentStep(): ?string;

    /**
     * Check if the workflow can be retried after failure.
     */
    public function canRetry(): bool;

    /**
     * Check if the workflow is complete.
     */
    public function isComplete(): bool;

    /**
     * Get workflow execution logs.
     *
     * @return array<int, array<string, mixed>> Execution log entries
     */
    public function getExecutionLog(): array;
}
```

#### `WorkflowStepInterface`

```php
<?php

namespace Nexus\FinanceOperations\Contracts;

use Nexus\FinanceOperations\DTOs\WorkflowStepContext;
use Nexus\FinanceOperations\DTOs\WorkflowStepResult;

/**
 * Contract for individual workflow steps with compensation support.
 *
 * @since 1.0.0
 */
interface WorkflowStepInterface
{
    /**
     * Get the step name for identification and logging.
     */
    public function getName(): string;

    /**
     * Execute the forward action of this step.
     */
    public function execute(WorkflowStepContext $context): WorkflowStepResult;

    /**
     * Execute the compensation (rollback) action of this step.
     */
    public function compensate(WorkflowStepContext $context): WorkflowStepResult;
}
```

### Rule Interface

#### `RuleInterface`

```php
<?php

namespace Nexus\FinanceOperations\Contracts;

use Nexus\FinanceOperations\DTOs\RuleResult;

/**
 * Contract for finance business rules validation.
 *
 * @since 1.0.0
 */
interface RuleInterface
{
    /**
     * Get the rule name for identification and logging.
     */
    public function getName(): string;

    /**
     * Check if the rule passes for the given context.
     */
    public function check(object $context): RuleResult;
}
```

---

## Coordinators

Stateless coordinators that orchestrate calls across packages.

### `CashFlowCoordinator`

Coordinates treasury and cash flow operations.

```php
<?php

namespace Nexus\FinanceOperations\Coordinators;

use Nexus\FinanceOperations\Contracts\CashFlowCoordinatorInterface;
use Nexus\FinanceOperations\Contracts\TreasuryDataProviderInterface;
use Nexus\FinanceOperations\Services\CashPositionService;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;

/**
 * Coordinator for cash flow operations.
 *
 * @since 1.0.0
 */
final readonly class CashFlowCoordinator implements CashFlowCoordinatorInterface
{
    public function __construct(
        private CashPositionService $cashPositionService,
        private TreasuryDataProviderInterface $treasuryDataProvider,
        private ?EventDispatcherInterface $eventDispatcher = null,
        private LoggerInterface $logger = new NullLogger(),
    ) {}
}
```

### `CostAllocationCoordinator`

Coordinates cost accounting operations.

```php
<?php

namespace Nexus\FinanceOperations\Coordinators;

use Nexus\FinanceOperations\Contracts\CostAllocationCoordinatorInterface;
use Nexus\FinanceOperations\Contracts\CostAccountingDataProviderInterface;
use Nexus\FinanceOperations\Services\CostAllocationService;
use Nexus\FinanceOperations\Rules\CostCenterActiveRule;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;

/**
 * Coordinator for cost allocation operations.
 *
 * @since 1.0.0
 */
final readonly class CostAllocationCoordinator implements CostAllocationCoordinatorInterface
{
    public function __construct(
        private CostAllocationService $costAllocationService,
        private CostAccountingDataProviderInterface $costDataProvider,
        private CostCenterActiveRule $costCenterActiveRule,
        private ?EventDispatcherInterface $eventDispatcher = null,
        private LoggerInterface $logger = new NullLogger(),
    ) {}
}
```

### `DepreciationCoordinator`

Coordinates asset depreciation operations.

```php
<?php

namespace Nexus\FinanceOperations\Coordinators;

use Nexus\FinanceOperations\Contracts\DepreciationCoordinatorInterface;
use Nexus\FinanceOperations\Contracts\DepreciationDataProviderInterface;
use Nexus\FinanceOperations\Services\DepreciationRunService;
use Nexus\FinanceOperations\Rules\PeriodOpenRule;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;

/**
 * Coordinator for depreciation operations.
 *
 * @since 1.0.0
 */
final readonly class DepreciationCoordinator implements DepreciationCoordinatorInterface
{
    public function __construct(
        private DepreciationRunService $depreciationService,
        private DepreciationDataProviderInterface $depreciationDataProvider,
        private PeriodOpenRule $periodOpenRule,
        private ?EventDispatcherInterface $eventDispatcher = null,
        private LoggerInterface $logger = new NullLogger(),
    ) {}
}
```

### `GLPostingCoordinator`

Coordinates subledger-to-GL posting.

```php
<?php

namespace Nexus\FinanceOperations\Coordinators;

use Nexus\FinanceOperations\Contracts\GLPostingCoordinatorInterface;
use Nexus\FinanceOperations\Contracts\GLReconciliationProviderInterface;
use Nexus\FinanceOperations\Services\GLReconciliationService;
use Nexus\FinanceOperations\Rules\SubledgerClosedRule;
use Nexus\FinanceOperations\Rules\GLAccountMappingRule;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;

/**
 * Coordinator for GL posting operations.
 *
 * @since 1.0.0
 */
final readonly class GLPostingCoordinator implements GLPostingCoordinatorInterface
{
    public function __construct(
        private GLReconciliationService $reconciliationService,
        private GLReconciliationProviderInterface $reconciliationProvider,
        private SubledgerClosedRule $subledgerClosedRule,
        private GLAccountMappingRule $accountMappingRule,
        private ?EventDispatcherInterface $eventDispatcher = null,
        private LoggerInterface $logger = new NullLogger(),
    ) {}
}
```

### `BudgetTrackingCoordinator`

Coordinates budget monitoring operations.

```php
<?php

namespace Nexus\FinanceOperations\Coordinators;

use Nexus\FinanceOperations\Contracts\BudgetTrackingCoordinatorInterface;
use Nexus\FinanceOperations\Contracts\BudgetVarianceProviderInterface;
use Nexus\FinanceOperations\Services\BudgetMonitoringService;
use Nexus\FinanceOperations\Rules\BudgetAvailableRule;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;

/**
 * Coordinator for budget tracking operations.
 *
 * @since 1.0.0
 */
final readonly class BudgetTrackingCoordinator implements BudgetTrackingCoordinatorInterface
{
    public function __construct(
        private BudgetMonitoringService $budgetService,
        private BudgetVarianceProviderInterface $budgetDataProvider,
        private BudgetAvailableRule $budgetAvailableRule,
        private ?EventDispatcherInterface $eventDispatcher = null,
        private LoggerInterface $logger = new NullLogger(),
    ) {}
}
```

### Coordinator Summary

| Coordinator | Purpose |
|-------------|---------|
| `CashFlowCoordinator` | Cash position, forecasting, bank reconciliation |
| `CostAllocationCoordinator` | Cost pool allocation, product costing |
| `DepreciationCoordinator` | Depreciation runs, schedule generation |
| `GLPostingCoordinator` | Subledger-to-GL posting, reconciliation |
| `BudgetTrackingCoordinator` | Budget availability, variance analysis |

---

## DTOs (Data Transfer Objects)

### Cash Flow DTOs

#### `CashPositionRequest`

```php
<?php

namespace Nexus\FinanceOperations\DTOs\CashFlow;

/**
 * Request DTO for cash position operations.
 *
 * @since 1.0.0
 */
final readonly class CashPositionRequest
{
    /**
     * @param string $tenantId Tenant identifier
     * @param string|null $bankAccountId Specific bank account ID, or null for all accounts
     * @param \DateTimeImmutable|null $asOfDate Date for position calculation, defaults to now
     * @param string $currency Currency for consolidated reporting (default: USD)
     */
    public function __construct(
        public string $tenantId,
        public ?string $bankAccountId = null,
        public ?\DateTimeImmutable $asOfDate = null,
        public string $currency = 'USD',
    ) {}
}
```

#### `CashPositionResult`

```php
<?php

namespace Nexus\FinanceOperations\DTOs\CashFlow;

/**
 * Result DTO for cash position operations.
 *
 * @since 1.0.0
 */
final readonly class CashPositionResult
{
    /**
     * @param bool $success Whether the operation succeeded
     * @param string $tenantId Tenant identifier
     * @param array<int, array{accountId: string, accountName: string, balance: string, currency: string}> $positions Array of cash position data per account
     * @param string|null $consolidatedBalance Consolidated balance in reporting currency
     * @param string $currency Reporting currency code
     * @param \DateTimeImmutable $asOfDate Date of the position
     * @param string|null $error Error message if operation failed
     */
    public function __construct(
        public bool $success,
        public string $tenantId,
        public array $positions = [],
        public ?string $consolidatedBalance = null,
        public string $currency = 'USD',
        public \DateTimeImmutable $asOfDate = new \DateTimeImmutable(),
        public ?string $error = null,
    ) {}
}
```

### Cost Allocation DTOs

#### `CostAllocationRequest`

```php
<?php

namespace Nexus\FinanceOperations\DTOs\CostAllocation;

/**
 * Request DTO for cost allocation operations.
 *
 * @since 1.0.0
 */
final readonly class CostAllocationRequest
{
    /**
     * @param string $tenantId Tenant identifier
     * @param string $periodId Accounting period for allocation
     * @param string $sourceCostPoolId Source cost pool to allocate from
     * @param array<string> $targetCostCenterIds Target cost center IDs
     * @param string $allocationMethod Allocation method: 'proportional', 'equal', 'manual'
     * @param array<string, mixed> $options Additional allocation options
     */
    public function __construct(
        public string $tenantId,
        public string $periodId,
        public string $sourceCostPoolId,
        public array $targetCostCenterIds = [],
        public string $allocationMethod = 'proportional',
        public array $options = [],
    ) {}
}
```

### Depreciation DTOs

#### `DepreciationRunRequest`

```php
<?php

namespace Nexus\FinanceOperations\DTOs\Depreciation;

/**
 * Request DTO for depreciation run operations.
 *
 * @since 1.0.0
 */
final readonly class DepreciationRunRequest
{
    /**
     * @param string $tenantId Tenant identifier
     * @param string $periodId Accounting period for depreciation run
     * @param array<string> $assetIds Specific assets to process, empty for all active assets
     * @param bool $postToGL Whether to post depreciation entries to GL
     * @param bool $validateOnly Run validation only without posting
     */
    public function __construct(
        public string $tenantId,
        public string $periodId,
        public array $assetIds = [],
        public bool $postToGL = true,
        public bool $validateOnly = false,
    ) {}
}
```

### GL Posting DTOs

#### `GLPostingRequest`

```php
<?php

namespace Nexus\FinanceOperations\DTOs\GLPosting;

/**
 * Request DTO for GL posting operations.
 *
 * @since 1.0.0
 */
final readonly class GLPostingRequest
{
    /**
     * @param string $tenantId Tenant identifier
     * @param string $periodId Accounting period for posting
     * @param string $subledgerType Subledger type: 'receivable', 'payable', 'asset', etc.
     * @param array<string, mixed> $options Additional posting options
     */
    public function __construct(
        public string $tenantId,
        public string $periodId,
        public string $subledgerType,
        public array $options = [],
    ) {}
}
```

### Budget Tracking DTOs

#### `BudgetCheckRequest`

```php
<?php

namespace Nexus\FinanceOperations\DTOs\BudgetTracking;

/**
 * Request DTO for budget availability check.
 *
 * @since 1.0.0
 */
final readonly class BudgetCheckRequest
{
    /**
     * @param string $tenantId Tenant identifier
     * @param string $budgetId Budget identifier
     * @param string $amount Amount to check availability for
     * @param string|null $costCenterId Optional cost center for departmental budgets
     */
    public function __construct(
        public string $tenantId,
        public string $budgetId,
        public string $amount,
        public ?string $costCenterId = null,
    ) {}
}
```

---

## DataProviders

Aggregate data from multiple packages to support coordinators.

### `TreasuryDataProvider`

```php
<?php

namespace Nexus\FinanceOperations\DataProviders;

use Nexus\FinanceOperations\Contracts\TreasuryDataProviderInterface;

/**
 * Data provider for treasury operations.
 *
 * Aggregates data from:
 * - Treasury package (bank accounts, cash positions)
 * - JournalEntry package (cash transactions)
 *
 * @since 1.0.0
 */
final readonly class TreasuryDataProvider implements TreasuryDataProviderInterface
{
    public function __construct(
        private object $treasuryManager,    // Treasury package
        private object $journalEntry,       // JournalEntry package
    ) {}
}
```

### `CostAccountingDataProvider`

```php
<?php

namespace Nexus\FinanceOperations\DataProviders;

use Nexus\FinanceOperations\Contracts\CostAccountingDataProviderInterface;

/**
 * Data provider for cost accounting operations.
 *
 * Aggregates data from:
 * - CostAccounting package (cost pools, cost centers)
 * - JournalEntry package (GL balances)
 *
 * @since 1.0.0
 */
final readonly class CostAccountingDataProvider implements CostAccountingDataProviderInterface
{
    public function __construct(
        private object $costManager,    // CostAccounting package
        private object $glManager,      // JournalEntry package
    ) {}
}
```

### `DepreciationDataProvider`

```php
<?php

namespace Nexus\FinanceOperations\DataProviders;

use Nexus\FinanceOperations\Contracts\DepreciationDataProviderInterface;

/**
 * Data provider for depreciation operations.
 *
 * Aggregates data from:
 * - FixedAssetDepreciation package (depreciation methods, schedules)
 * - Assets package (asset register, book values)
 *
 * @since 1.0.0
 */
final readonly class DepreciationDataProvider implements DepreciationDataProviderInterface
{
    public function __construct(
        private object $depreciationManager,    // FixedAssetDepreciation package
        private object $assetQuery,             // Assets package
    ) {}
}
```

### `GLReconciliationProvider`

```php
<?php

namespace Nexus\FinanceOperations\DataProviders;

use Nexus\FinanceOperations\Contracts\GLReconciliationProviderInterface;

/**
 * Data provider for GL reconciliation operations.
 *
 * Aggregates data from:
 * - Receivable package (AR subledger)
 * - Payable package (AP subledger)
 * - Assets package (Fixed asset subledger)
 * - JournalEntry package (GL balances)
 *
 * @since 1.0.0
 */
final readonly class GLReconciliationProvider implements GLReconciliationProviderInterface
{
    public function __construct(
        private object $receivableQuery,    // Receivable package
        private object $payableQuery,       // Payable package
        private object $assetQuery,         // Assets package
        private object $glQuery,            // JournalEntry package
    ) {}
}
```

### `BudgetVarianceProvider`

```php
<?php

namespace Nexus\FinanceOperations\DataProviders;

use Nexus\FinanceOperations\Contracts\BudgetVarianceProviderInterface;

/**
 * Data provider for budget variance operations.
 *
 * Aggregates data from:
 * - Budget package (budget amounts, versions)
 * - JournalEntry package (actual balances)
 *
 * @since 1.0.0
 */
final readonly class BudgetVarianceProvider implements BudgetVarianceProviderInterface
{
    public function __construct(
        private object $budgetQuery,     // Budget package
        private object $glQuery,         // JournalEntry package
    ) {}
}
```

### DataProvider Summary

| DataProvider | Aggregates |
|--------------|-----------|
| `TreasuryDataProvider` | Treasury, JournalEntry |
| `CostAccountingDataProvider` | CostAccounting, JournalEntry |
| `DepreciationDataProvider` | FixedAssetDepreciation, Assets |
| `GLReconciliationProvider` | Receivable, Payable, Assets, JournalEntry |
| `BudgetVarianceProvider` | Budget, JournalEntry |

---

## Services

Orchestration services that handle the "heavy lifting" - calculations and cross-boundary logic.

### `CashPositionService`

```php
<?php

namespace Nexus\FinanceOperations\Services;

use Nexus\FinanceOperations\Contracts\TreasuryDataProviderInterface;
use Nexus\FinanceOperations\DTOs\CashFlow\CashPositionRequest;
use Nexus\FinanceOperations\DTOs\CashFlow\CashPositionResult;

/**
 * Service for cash position calculations and forecasting.
 *
 * @since 1.0.0
 */
final readonly class CashPositionService
{
    public function __construct(
        private TreasuryDataProviderInterface $dataProvider,
        private ?object $currencyConverter = null,
        private LoggerInterface $logger = new NullLogger(),
    ) {}

    /**
     * Get cash position for one or all bank accounts.
     */
    public function getPosition(CashPositionRequest $request): CashPositionResult;
}
```

### `CostAllocationService`

```php
<?php

namespace Nexus\FinanceOperations\Services;

use Nexus\FinanceOperations\Contracts\CostAccountingDataProviderInterface;
use Nexus\FinanceOperations\DTOs\CostAllocation\CostAllocationRequest;
use Nexus\FinanceOperations\DTOs\CostAllocation\CostAllocationResult;

/**
 * Service for cost allocation and product costing operations.
 *
 * @since 1.0.0
 */
final readonly class CostAllocationService
{
    public function __construct(
        private CostAccountingDataProviderInterface $dataProvider,
        private LoggerInterface $logger = new NullLogger(),
    ) {}

    /**
     * Allocate costs from a pool to cost centers.
     */
    public function allocate(CostAllocationRequest $request): CostAllocationResult;
}
```

### `DepreciationRunService`

```php
<?php

namespace Nexus\FinanceOperations\Services;

use Nexus\FinanceOperations\Contracts\DepreciationDataProviderInterface;
use Nexus\FinanceOperations\DTOs\Depreciation\DepreciationRunRequest;
use Nexus\FinanceOperations\DTOs\Depreciation\DepreciationRunResult;

/**
 * Service for depreciation run operations.
 *
 * @since 1.0.0
 */
final readonly class DepreciationRunService
{
    public function __construct(
        private DepreciationDataProviderInterface $dataProvider,
        private LoggerInterface $logger = new NullLogger(),
    ) {}

    /**
     * Execute depreciation run for a period.
     */
    public function executeRun(DepreciationRunRequest $request): DepreciationRunResult;
}
```

### `GLReconciliationService`

```php
<?php

namespace Nexus\FinanceOperations\Services;

use Nexus\FinanceOperations\Contracts\GLReconciliationProviderInterface;
use Nexus\FinanceOperations\DTOs\GLPosting\GLPostingRequest;
use Nexus\FinanceOperations\DTOs\GLPosting\GLPostingResult;

/**
 * Service for GL reconciliation operations.
 *
 * @since 1.0.0
 */
final readonly class GLReconciliationService
{
    public function __construct(
        private GLReconciliationProviderInterface $dataProvider,
        private LoggerInterface $logger = new NullLogger(),
    ) {}

    /**
     * Post subledger transactions to GL.
     */
    public function postToGL(GLPostingRequest $request): GLPostingResult;
}
```

### `BudgetMonitoringService`

```php
<?php

namespace Nexus\FinanceOperations\Services;

use Nexus\FinanceOperations\Contracts\BudgetVarianceProviderInterface;
use Nexus\FinanceOperations\DTOs\BudgetTracking\BudgetCheckRequest;
use Nexus\FinanceOperations\DTOs\BudgetTracking\BudgetCheckResult;

/**
 * Service for budget monitoring operations.
 *
 * @since 1.0.0
 */
final readonly class BudgetMonitoringService
{
    public function __construct(
        private BudgetVarianceProviderInterface $dataProvider,
        private LoggerInterface $logger = new NullLogger(),
    ) {}

    /**
     * Check budget availability.
     */
    public function checkBudget(BudgetCheckRequest $request): BudgetCheckResult;
}
```

### Service Summary

| Service | Purpose |
|---------|---------|
| `CashPositionService` | Cash position aggregation, multi-currency consolidation |
| `CostAllocationService` | Cost allocation calculations, product cost rollups |
| `DepreciationRunService` | Depreciation calculation execution, schedule generation |
| `GLReconciliationService` | Subledger-to-GL posting, reconciliation matching |
| `BudgetMonitoringService` | Budget availability checks, variance calculations |

---

## Rules

Cross-package validation rules that enforce business constraints.

### `SubledgerClosedRule`

Validates that a subledger is closed before posting to GL.

```php
<?php

namespace Nexus\FinanceOperations\Rules;

use Nexus\FinanceOperations\Contracts\RuleInterface;
use Nexus\FinanceOperations\DTOs\RuleResult;

/**
 * Rule to validate that a subledger is closed before posting to GL.
 *
 * @since 1.0.0
 */
final readonly class SubledgerClosedRule implements RuleInterface
{
    public function __construct(
        private object $periodManager,
    ) {}

    /**
     * Check if the subledger is closed for the period.
     *
     * @param object $context Context containing tenantId, periodId, and subledgerType
     */
    public function check(object $context): RuleResult;
}
```

### `PeriodOpenRule`

Validates that an accounting period is open for posting.

```php
<?php

namespace Nexus\FinanceOperations\Rules;

use Nexus\FinanceOperations\Contracts\RuleInterface;
use Nexus\FinanceOperations\DTOs\RuleResult;

/**
 * Rule to validate that an accounting period is open for posting.
 *
 * @since 1.0.0
 */
final readonly class PeriodOpenRule implements RuleInterface
{
    public function __construct(
        private object $periodManager,
    ) {}

    /**
     * Check if the period is open for posting.
     *
     * @param object $context Context containing tenantId and periodId
     */
    public function check(object $context): RuleResult;
}
```

### `BudgetAvailableRule`

Validates budget availability for cost allocation.

```php
<?php

namespace Nexus\FinanceOperations\Rules;

use Nexus\FinanceOperations\Contracts\RuleInterface;
use Nexus\FinanceOperations\DTOs\RuleResult;

/**
 * Rule to validate that sufficient budget is available.
 *
 * @since 1.0.0
 */
final readonly class BudgetAvailableRule implements RuleInterface
{
    public function __construct(
        private object $budgetQuery,
        private bool $strictMode = true,
    ) {}

    /**
     * Check if budget is available for the requested amount.
     *
     * @param object $context Context containing tenantId, budgetId, amount, and optional costCenterId
     */
    public function check(object $context): RuleResult;
}
```

### `GLAccountMappingRule`

Validates GL account mappings for posting.

```php
<?php

namespace Nexus\FinanceOperations\Rules;

use Nexus\FinanceOperations\Contracts\RuleInterface;
use Nexus\FinanceOperations\DTOs\RuleResult;

/**
 * Rule to validate GL account mappings for posting.
 *
 * @since 1.0.0
 */
final readonly class GLAccountMappingRule implements RuleInterface
{
    public function __construct(
        private object $accountMappingQuery,
    ) {}

    /**
     * Check if valid GL account mapping exists.
     */
    public function check(object $context): RuleResult;
}
```

### `CostCenterActiveRule`

Validates that cost centers are active for allocation.

```php
<?php

namespace Nexus\FinanceOperations\Rules;

use Nexus\FinanceOperations\Contracts\RuleInterface;
use Nexus\FinanceOperations\DTOs\RuleResult;

/**
 * Rule to validate that cost centers are active for allocation.
 *
 * @since 1.0.0
 */
final readonly class CostCenterActiveRule implements RuleInterface
{
    public function __construct(
        private object $costCenterQuery,
    ) {}

    /**
     * Check if all cost centers are active.
     *
     * @param object $context Context containing tenantId and costCenterIds
     */
    public function check(object $context): RuleResult;
}
```

### Rule Summary

| Rule | Validates |
|------|-----------|
| `SubledgerClosedRule` | Subledger closure before GL posting |
| `PeriodOpenRule` | Period open status for transactions |
| `BudgetAvailableRule` | Budget availability for commitments |
| `GLAccountMappingRule` | Valid GL account mappings |
| `CostCenterActiveRule` | Cost center active status |

---

## Workflows

Stateful workflow processes implementing the Saga pattern with compensation logic.

### `DepreciationRunWorkflow` (4 Steps)

Manages the complete depreciation run process.

**Steps:**
1. `CalculateDepreciationStep` - Compute depreciation for all eligible assets
2. `ValidateDepreciationStep` - Validate calculations against business rules
3. `PostToGLStep` - Post depreciation entries to General Ledger
4. `UpdateAssetRegisterStep` - Update asset register with new book values

**States:**
- `INITIATED` - Depreciation run started
- `CALCULATING` - Computing depreciation amounts
- `VALIDATING` - Validating calculations
- `POSTING` - Posting to General Ledger
- `UPDATING_REGISTER` - Updating asset register
- `COMPLETED` - Depreciation run completed successfully
- `FAILED` - Workflow failed, compensation triggered
- `COMPENSATED` - Workflow rolled back

```php
<?php

namespace Nexus\FinanceOperations\Workflows\DepreciationRun;

use Nexus\FinanceOperations\Workflows\AbstractFinanceWorkflow;

/**
 * DepreciationRunWorkflow - Saga for fixed asset depreciation processing.
 *
 * @since 1.0.0
 */
final readonly class DepreciationRunWorkflow extends AbstractFinanceWorkflow
{
    public const STATE_INITIATED = 'INITIATED';
    public const STATE_CALCULATING = 'CALCULATING';
    public const STATE_VALIDATING = 'VALIDATING';
    public const STATE_POSTING = 'POSTING';
    public const STATE_UPDATING_REGISTER = 'UPDATING_REGISTER';
    public const STATE_COMPLETED = 'COMPLETED';
    public const STATE_FAILED = 'FAILED';
    public const STATE_COMPENSATED = 'COMPENSATED';

    public function __construct(
        array $steps = [],
        ?object $storage = null,
        ?LoggerInterface $logger = null,
    ) {
        $workflowSteps = $steps ?: [
            new CalculateDepreciationStep($logger),
            new ValidateDepreciationStep($logger),
            new PostToGLStep($logger),
            new UpdateAssetRegisterStep($logger),
        ];

        parent::__construct(
            steps: $workflowSteps,
            storage: $storage,
            logger: $logger ?? new NullLogger(),
        );
    }
}
```

### `CostAllocationWorkflow` (3 Steps)

Manages the complete cost allocation process.

**Steps:**
1. `GatherCostsStep` - Collect cost data from various sources
2. `ApplyAllocationRulesStep` - Apply allocation rules based on cost drivers
3. `PostAllocatedCostsStep` - Post allocated costs to General Ledger

**States:**
- `INITIATED` - Cost allocation started
- `GATHERING` - Collecting cost data from sources
- `ALLOCATING` - Applying allocation rules
- `POSTING` - Posting allocated costs to GL
- `COMPLETED` - Cost allocation completed successfully
- `FAILED` - Workflow failed, compensation triggered
- `COMPENSATED` - Workflow rolled back

```php
<?php

namespace Nexus\FinanceOperations\Workflows\CostAllocation;

use Nexus\FinanceOperations\Workflows\AbstractFinanceWorkflow;

/**
 * CostAllocationWorkflow - Saga for cost allocation processing.
 *
 * @since 1.0.0
 */
final readonly class CostAllocationWorkflow extends AbstractFinanceWorkflow
{
    public const STATE_INITIATED = 'INITIATED';
    public const STATE_GATHERING = 'GATHERING';
    public const STATE_ALLOCATING = 'ALLOCATING';
    public const STATE_POSTING = 'POSTING';
    public const STATE_COMPLETED = 'COMPLETED';
    public const STATE_FAILED = 'FAILED';
    public const STATE_COMPENSATED = 'COMPENSATED';

    public function __construct(
        array $steps = [],
        ?object $storage = null,
        ?LoggerInterface $logger = null,
    ) {
        $workflowSteps = $steps ?: [
            new GatherCostsStep($logger),
            new ApplyAllocationRulesStep($logger),
            new PostAllocatedCostsStep($logger),
        ];

        parent::__construct(
            steps: $workflowSteps,
            storage: $storage,
            logger: $logger ?? new NullLogger(),
        );
    }
}
```

### `CashReconciliationWorkflow` (3 Steps)

Manages the complete cash/bank reconciliation process.

**Steps:**
1. `MatchTransactionsStep` - Match bank transactions with book records
2. `IdentifyDiscrepanciesStep` - Analyze differences between bank and book
3. `CreateAdjustingEntriesStep` - Create adjusting entries for differences

**States:**
- `INITIATED` - Reconciliation started
- `MATCHING` - Matching transactions in progress
- `IDENTIFYING_DISCREPANCIES` - Analyzing differences
- `CREATING_ADJUSTMENTS` - Creating adjusting entries
- `COMPLETED` - Reconciliation completed successfully
- `FAILED` - Workflow failed, compensation triggered
- `COMPENSATED` - Workflow rolled back

```php
<?php

namespace Nexus\FinanceOperations\Workflows\CashReconciliation;

use Nexus\FinanceOperations\Workflows\AbstractFinanceWorkflow;

/**
 * CashReconciliationWorkflow - Saga for cash/bank reconciliation processing.
 *
 * @since 1.0.0
 */
final readonly class CashReconciliationWorkflow extends AbstractFinanceWorkflow
{
    public const STATE_INITIATED = 'INITIATED';
    public const STATE_MATCHING = 'MATCHING';
    public const STATE_IDENTIFYING_DISCREPANCIES = 'IDENTIFYING_DISCREPANCIES';
    public const STATE_CREATING_ADJUSTMENTS = 'CREATING_ADJUSTMENTS';
    public const STATE_COMPLETED = 'COMPLETED';
    public const STATE_FAILED = 'FAILED';
    public const STATE_COMPENSATED = 'COMPENSATED';

    public function __construct(
        array $steps = [],
        ?object $storage = null,
        ?LoggerInterface $logger = null,
    ) {
        $workflowSteps = $steps ?: [
            new MatchTransactionsStep($logger),
            new IdentifyDiscrepanciesStep($logger),
            new CreateAdjustingEntriesStep($logger),
        ];

        parent::__construct(
            steps: $workflowSteps,
            storage: $storage,
            logger: $logger ?? new NullLogger(),
        );
    }
}
```

### Workflow Summary

| Workflow | Steps | Purpose |
|----------|-------|---------|
| `DepreciationRunWorkflow` | 4 | Fixed asset depreciation processing |
| `CostAllocationWorkflow` | 3 | Cost pool allocation processing |
| `CashReconciliationWorkflow` | 3 | Bank reconciliation processing |

---

## Listeners

Event listeners that react to events from atomic packages.

### `OnDepreciationCalculated`

Automatically posts depreciation to GL when calculated.

```php
<?php

namespace Nexus\FinanceOperations\Listeners;

use Nexus\FinanceOperations\Contracts\GLPostingCoordinatorInterface;
use Nexus\FinanceOperations\DTOs\GLPostingRequest;
use Psr\Log\LoggerInterface;

/**
 * Listener for depreciation calculation events.
 *
 * @since 1.0.0
 */
final readonly class OnDepreciationCalculated
{
    public function __construct(
        private GLPostingCoordinatorInterface $glPostingCoordinator,
        private LoggerInterface $logger = new NullLogger(),
    ) {}

    /**
     * Handle the depreciation calculated event.
     *
     * @param object $event The depreciation event containing:
     *                      - tenantId: string
     *                      - periodId: string
     *                      - runId: string
     *                      - totalDepreciation: string
     *                      - assetCount: int
     */
    public function handle(object $event): void;
}
```

### `OnCostAllocated`

Handles post-allocation activities.

```php
<?php

namespace Nexus\FinanceOperations\Listeners;

use Psr\Log\LoggerInterface;

/**
 * Listener for cost allocation events.
 *
 * @since 1.0.0
 */
final readonly class OnCostAllocated
{
    public function __construct(
        private ?object $notificationService = null,
        private ?object $budgetService = null,
        private LoggerInterface $logger = new NullLogger(),
    ) {}

    /**
     * Handle the cost allocated event.
     *
     * @param object $event The allocation event containing:
     *                      - tenantId: string
     *                      - periodId: string
     *                      - allocationId: string
     *                      - costCenterIds: array
     *                      - totalAllocated: string
     */
    public function handle(object $event): void;
}
```

### `OnGLReconciliationCompleted`

Handles post-reconciliation activities.

```php
<?php

namespace Nexus\FinanceOperations\Listeners;

use Psr\Log\LoggerInterface;

/**
 * Listener for GL reconciliation completion events.
 *
 * @since 1.0.0
 */
final readonly class OnGLReconciliationCompleted
{
    public function __construct(
        private ?object $notificationService = null,
        private ?object $taskService = null,
        private LoggerInterface $logger = new NullLogger(),
    ) {}

    /**
     * Handle the GL reconciliation completed event.
     *
     * @param object $event The reconciliation event containing:
     *                      - tenantId: string
     *                      - periodId: string
     *                      - subledgerType: string
     *                      - isReconciled: bool
     *                      - discrepancies: array
     */
    public function handle(object $event): void;
}
```

### `OnBudgetThresholdExceeded`

Handles budget threshold alert activities.

```php
<?php

namespace Nexus\FinanceOperations\Listeners;

use Psr\Log\LoggerInterface;

/**
 * Listener for budget threshold exceeded events.
 *
 * @since 1.0.0
 */
final readonly class OnBudgetThresholdExceeded
{
    public function __construct(
        private ?object $notificationService = null,
        private ?object $workflowService = null,
        private LoggerInterface $logger = new NullLogger(),
    ) {}

    /**
     * Handle the budget threshold exceeded event.
     *
     * @param object $event The threshold event containing:
     *                      - tenantId: string
     *                      - budgetId: string
     *                      - threshold: float
     *                      - actualPercent: float
     *                      - costCenterId: string|null
     */
    public function handle(object $event): void;
}
```

### Listener Summary

| Listener | Trigger | Action |
|----------|---------|--------|
| `OnDepreciationCalculated` | Depreciation run completed | Post to GL |
| `OnCostAllocated` | Cost allocation completed | Update budget, send notifications |
| `OnGLReconciliationCompleted` | GL reconciliation completed | Create adjustment tasks, notify |
| `OnBudgetThresholdExceeded` | Budget threshold reached | Send alerts, create approval workflow |

---

## Exceptions

Domain-specific exception hierarchy for finance operations.

### `CoordinationException`

Base exception for all FinanceOperations coordination errors.

```php
<?php

namespace Nexus\FinanceOperations\Exceptions;

use RuntimeException;
use Throwable;

/**
 * Base exception for all FinanceOperations coordination errors.
 *
 * @since 1.0.0
 */
class CoordinationException extends RuntimeException
{
    public function __construct(
        string $message,
        private readonly string $coordinatorName = '',
        private readonly array $context = [],
        ?Throwable $previous = null
    ) {}

    public function getCoordinatorName(): string;
    public function getContext(): array;

    public static function missingRequiredData(
        string $coordinatorName,
        string $dataType,
        string $tenantId
    ): self;

    public static function coordinationFailed(
        string $coordinatorName,
        string $operation,
        string $reason,
        array $context = []
    ): self;
}
```

### `CashFlowException`

Exception for cash flow coordination errors.

```php
<?php

namespace Nexus\FinanceOperations\Exceptions;

/**
 * Exception for cash flow coordination errors.
 *
 * @since 1.0.0
 */
final class CashFlowException extends CoordinationException
{
    public static function cashPositionRetrievalFailed(
        string $tenantId,
        string $bankAccountId,
        string $reason,
        ?Throwable $previous = null
    ): self;

    public static function forecastGenerationFailed(
        string $tenantId,
        string $periodId,
        string $reason,
        ?Throwable $previous = null
    ): self;

    public static function reconciliationFailed(
        string $tenantId,
        string $bankAccountId,
        int $unmatchedCount,
        string $reason
    ): self;

    public static function insufficientCash(
        string $tenantId,
        string $requiredAmount,
        string $availableAmount
    ): self;
}
```

### `CostAllocationException`

Exception for cost allocation coordination errors.

```php
<?php

namespace Nexus\FinanceOperations\Exceptions;

/**
 * Exception for cost allocation coordination errors.
 *
 * @since 1.0.0
 */
final class CostAllocationException extends CoordinationException
{
    public static function allocationFailed(
        string $tenantId,
        string $costPoolId,
        string $reason,
        ?Throwable $previous = null
    ): self;

    public static function invalidAllocationMethod(
        string $tenantId,
        string $method,
        array $validMethods
    ): self;

    public static function productCostingFailed(
        string $tenantId,
        string $productId,
        string $reason,
        ?Throwable $previous = null
    ): self;

    public static function inactiveCostCenter(
        string $tenantId,
        string $costCenterId
    ): self;
}
```

### `DepreciationCoordinationException`

Exception for depreciation coordination errors.

```php
<?php

namespace Nexus\FinanceOperations\Exceptions;

/**
 * Exception for depreciation coordination errors.
 *
 * @since 1.0.0
 */
final class DepreciationCoordinationException extends CoordinationException
{
    public static function runFailed(
        string $tenantId,
        string $periodId,
        string $reason,
        ?Throwable $previous = null
    ): self;

    public static function assetNotFound(
        string $tenantId,
        string $assetId
    ): self;

    public static function invalidDepreciationMethod(
        string $tenantId,
        string $assetId,
        string $method
    ): self;

    public static function assetFullyDepreciated(
        string $tenantId,
        string $assetId
    ): self;
}
```

### `GLReconciliationException`

Exception for GL reconciliation coordination errors.

```php
<?php

namespace Nexus\FinanceOperations\Exceptions;

/**
 * Exception for GL reconciliation coordination errors.
 *
 * @since 1.0.0
 */
final class GLReconciliationException extends CoordinationException
{
    public static function postingFailed(
        string $tenantId,
        string $subledgerType,
        string $reason,
        ?Throwable $previous = null
    ): self;

    public static function reconciliationMismatch(
        string $tenantId,
        string $subledgerType,
        string $subledgerBalance,
        string $glBalance,
        string $variance
    ): self;

    public static function periodNotOpen(
        string $tenantId,
        string $periodId
    ): self;

    public static function subledgerNotClosed(
        string $tenantId,
        string $periodId,
        string $subledgerType
    ): self;
}
```

### `BudgetTrackingException`

Exception for budget tracking coordination errors.

```php
<?php

namespace Nexus\FinanceOperations\Exceptions;

/**
 * Exception for budget tracking coordination errors.
 *
 * @since 1.0.0
 */
final class BudgetTrackingException extends CoordinationException
{
    public static function budgetNotFound(
        string $tenantId,
        string $budgetId
    ): self;

    public static function budgetExceeded(
        string $tenantId,
        string $budgetId,
        string $requested,
        string $available
    ): self;

    public static function varianceCalculationFailed(
        string $tenantId,
        string $periodId,
        string $reason,
        ?Throwable $previous = null
    ): self;

    public static function inactiveBudget(
        string $tenantId,
        string $budgetId
    ): self;

    public static function thresholdAlertFailed(
        string $tenantId,
        string $budgetId,
        float $threshold,
        string $reason
    ): self;

    public static function revisionNotAllowed(
        string $tenantId,
        string $budgetId,
        string $reason
    ): self;
}
```

### Exception Summary

| Exception | Purpose |
|-----------|---------|
| `CoordinationException` | Base exception for all coordination errors |
| `CashFlowException` | Cash position, forecast, reconciliation errors |
| `CostAllocationException` | Cost allocation, product costing errors |
| `DepreciationCoordinationException` | Depreciation run, schedule errors |
| `GLReconciliationException` | GL posting, reconciliation errors |
| `BudgetTrackingException` | Budget availability, variance errors |

---

## Usage Examples

### Cash Position Query

```php
<?php

use Nexus\FinanceOperations\Contracts\CashFlowCoordinatorInterface;
use Nexus\FinanceOperations\DTOs\CashFlow\CashPositionRequest;

class TreasuryController
{
    public function __construct(
        private CashFlowCoordinatorInterface $cashFlowCoordinator
    ) {}

    public function cashPosition(Request $request): Response
    {
        $cashRequest = new CashPositionRequest(
            tenantId: $request->tenant_id,
            bankAccountId: $request->bank_account_id,
            asOfDate: new \DateTimeImmutable(),
            currency: 'USD'
        );

        $result = $this->cashFlowCoordinator->getCashPosition($cashRequest);

        if (!$result->success) {
            return response()->json(['error' => $result->error], 400);
        }

        return response()->json([
            'positions' => $result->positions,
            'consolidated_balance' => $result->consolidatedBalance,
            'currency' => $result->currency,
        ]);
    }
}
```

### Cost Allocation Execution

```php
<?php

use Nexus\FinanceOperations\Contracts\CostAllocationCoordinatorInterface;
use Nexus\FinanceOperations\DTOs\CostAllocation\CostAllocationRequest;

class CostAccountingController
{
    public function __construct(
        private CostAllocationCoordinatorInterface $costCoordinator
    ) {}

    public function allocateCosts(Request $request): Response
    {
        $allocationRequest = new CostAllocationRequest(
            tenantId: $request->tenant_id,
            periodId: $request->period_id,
            sourceCostPoolId: $request->source_pool_id,
            targetCostCenterIds: $request->target_cost_centers,
            allocationMethod: 'proportional',
            options: []
        );

        $result = $this->costCoordinator->allocateCosts($allocationRequest);

        if (!$result->success) {
            return response()->json(['error' => $result->error], 400);
        }

        return response()->json([
            'allocation_id' => $result->allocationId,
            'allocated_amount' => $result->totalAllocated,
            'allocations' => $result->allocations,
        ]);
    }
}
```

### Depreciation Run Workflow

```php
<?php

use Nexus\FinanceOperations\Workflows\DepreciationRun\DepreciationRunWorkflow;
use Nexus\FinanceOperations\DTOs\WorkflowResult;

class DepreciationController
{
    public function __construct(
        private DepreciationRunWorkflow $depreciationWorkflow
    ) {}

    public function runDepreciation(Request $request): Response
    {
        $context = [
            'tenant_id' => $request->tenant_id,
            'period_id' => $request->period_id,
            'asset_ids' => $request->asset_ids ?? [],
            'post_to_gl' => true,
        ];

        if (!$this->depreciationWorkflow->canStart($context)) {
            return response()->json(['error' => 'Workflow cannot be started'], 400);
        }

        $result = $this->depreciationWorkflow->execute($context);

        if (!$result->isSuccess()) {
            // Optionally compensate on failure
            $this->depreciationWorkflow->compensate($result);

            return response()->json([
                'error' => $result->getErrorMessage(),
                'step' => $this->depreciationWorkflow->getCurrentStep(),
            ], 400);
        }

        return response()->json([
            'run_id' => $result->getData('run_id'),
            'assets_processed' => $result->getData('assets_processed'),
            'total_depreciation' => $result->getData('total_depreciation'),
        ]);
    }
}
```

### Budget Availability Check

```php
<?php

use Nexus\FinanceOperations\Contracts\BudgetTrackingCoordinatorInterface;
use Nexus\FinanceOperations\DTOs\BudgetTracking\BudgetCheckRequest;

class BudgetController
{
    public function __construct(
        private BudgetTrackingCoordinatorInterface $budgetCoordinator
    ) {}

    public function checkAvailability(Request $request): Response
    {
        $checkRequest = new BudgetCheckRequest(
            tenantId: $request->tenant_id,
            budgetId: $request->budget_id,
            amount: $request->amount,
            costCenterId: $request->cost_center_id
        );

        $result = $this->budgetCoordinator->checkBudgetAvailable($checkRequest);

        return response()->json([
            'available' => $result->isAvailable,
            'available_amount' => $result->availableAmount,
            'utilized_percent' => $result->utilizedPercent,
        ]);
    }
}
```

### GL Reconciliation

```php
<?php

use Nexus\FinanceOperations\Contracts\GLPostingCoordinatorInterface;
use Nexus\FinanceOperations\DTOs\GLPosting\GLReconciliationRequest;

class GLController
{
    public function __construct(
        private GLPostingCoordinatorInterface $glCoordinator
    ) {}

    public function reconcile(Request $request): Response
    {
        $reconRequest = new GLReconciliationRequest(
            tenantId: $request->tenant_id,
            periodId: $request->period_id,
            subledgerType: $request->subledger_type // 'receivable', 'payable', 'asset'
        );

        $result = $this->glCoordinator->reconcileWithGL($reconRequest);

        if (!$result->isReconciled) {
            return response()->json([
                'reconciled' => false,
                'discrepancies' => $result->discrepancies,
                'variance' => $result->variance,
            ], 400);
        }

        return response()->json([
            'reconciled' => true,
            'subledger_balance' => $result->subledgerBalance,
            'gl_balance' => $result->glBalance,
        ]);
    }
}
```

---

## Relationship with AccountingOperations

| Aspect | FinanceOperations | AccountingOperations |
|--------|------------------|----------------------|
| **Focus** | Day-to-day operations | Period-end processes |
| **Timing** | Continuous throughout period | End of period |
| **Examples** | Cash flow, cost allocation, depreciation runs | Period close, consolidation, statements |
| **Workflows** | Operational Sagas | Close Sagas |
| **Users** | Operations staff, accountants | Controllers, CFOs |

---

## Testing

```bash
# Run unit tests
composer test

# Run with coverage
composer test-coverage

# Run specific test file
vendor/bin/phpunit tests/Coordinators/CashFlowCoordinatorTest.php
```

### Example Test

```php
<?php

use Nexus\FinanceOperations\Coordinators\CashFlowCoordinator;
use Nexus\FinanceOperations\DTOs\CashFlow\CashPositionRequest;
use PHPUnit\Framework\TestCase;

class CashFlowCoordinatorTest extends TestCase
{
    public function testGetCashPositionReturnsValidResult(): void
    {
        $mockService = $this->createMock(CashPositionService::class);
        $mockService->method('getPosition')
            ->willReturn(new CashPositionResult(
                success: true,
                tenantId: 'tenant-1',
                positions: [
                    ['accountId' => 'bank-1', 'accountName' => 'Main Account', 'balance' => '10000.00', 'currency' => 'USD']
                ],
                consolidatedBalance: '10000.00',
                currency: 'USD'
            ));

        $coordinator = new CashFlowCoordinator(
            cashPositionService: $mockService,
            treasuryDataProvider: $this->createMock(TreasuryDataProviderInterface::class)
        );

        $request = new CashPositionRequest(
            tenantId: 'tenant-1',
            bankAccountId: 'bank-1'
        );

        $result = $coordinator->getCashPosition($request);

        $this->assertTrue($result->success);
        $this->assertEquals('10000.00', $result->consolidatedBalance);
    }
}
```

---

## Documentation

- [Architecture Guidelines](../../ARCHITECTURE.md) - Nexus architectural standards
- [Implementation Summary](IMPLEMENTATION_SUMMARY.md) - Detailed implementation status

## Contributing

Please see [CONTRIBUTING.md](../../CONTRIBUTING.md) for details.

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.

---

**Nexus** - Enterprise Resource Planning for the Modern Age
