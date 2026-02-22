# FinanceOperations Orchestrator Requirements

## 1. Functional Requirements

### 1.1 Tier 1: Small Business (SB) - Basic Features

#### Cash Flow Management
- **FO-CF-001**: View current cash position by bank account
- **FO-CF-002**: View cash balance summary across all accounts
- **FO-CF-003**: Basic cash flow forecast (7-day, 30-day)
- **FO-CF-004**: Simple bank reconciliation (manual matching)

#### Cost Management
- **FO-CM-001**: View cost centers and cost pools
- **FO-CM-002**: View cost allocation rules
- **FO-CM-003**: Manual cost allocation entry
- **FO-CM-004**: View product cost summary

#### Asset Depreciation
- **FO-AD-001**: Calculate depreciation for individual assets
- **FO-AD-002**: View depreciation schedules
- **FO-AD-003**: Post depreciation to general ledger
- **FO-AD-004**: View asset book values

#### GL Posting
- **FO-GL-001**: Post journal entries from subledgers to GL
- **FO-GL-002**: View subledger-to-GL reconciliation status
- **FO-GL-003**: Basic GL account validation

#### Budget Tracking
- **FO-BT-001**: View budget vs actual by cost center
- **FO-BT-002**: View budget utilization percentage
- **FO-BT-003**: Basic budget variance report

---

### 1.2 Tier 2: Medium Business (MB) - Enhanced Features

#### Cash Flow Management (Tier 2)
- **FO-CF-005**: Cash flow forecasting with scenarios
- **FO-CF-006**: Multi-currency cash position
- **FO-CF-007**: Bank reconciliation with automatic matching rules
- **FO-CF-008**: Cash concentration and sweeping
- **FO-CF-009**: Liquidity alerts and notifications

#### Cost Management (Tier 2)
- **FO-CM-005**: Automatic periodic cost allocation
- **FO-CM-006**: Activity-based costing (ABC) support
- **FO-CM-007**: Cost allocation rule management
- **FO-CM-008**: Product costing with bill of materials
- **FO-CM-009**: Cost variance analysis
- **FO-CM-010**: Cost center performance reporting

#### Asset Depreciation (Tier 2)
- **FO-AD-005**: Mass depreciation calculation run
- **FO-AD-006**: Multiple depreciation methods (straight-line, declining balance, sum-of-years)
- **FO-AD-007**: Depreciation schedule adjustments
- **FO-AD-008**: Partial asset depreciation
- **FO-AD-009**: Asset revaluation support
- **FO-AD-010**: Tax book vs book depreciation parallel tracking

#### GL Posting (Tier 2)
- **FO-GL-004**: Automatic posting from subledgers
- **FO-GL-005**: GL reconciliation with drill-down
- **FO-GL-006**: Inter-company transaction handling
- **FO-GL-007**: Reversing entries support
- **FO-GL-008**: Recurring journal entries

#### Budget Tracking (Tier 2)
- **FO-BT-004**: Budget revision management
- **FO-BT-005**: Budget threshold alerts
- **FO-BT-006**: Budget vs actual by project
- **FO-BT-007**: Rolling forecast support
- **FO-BT-008**: Budget commitment tracking

---

### 1.3 Tier 3: Large Enterprise (LE) - Advanced Features

#### Cash Flow Management (Tier 3)
- **FO-CF-010**: Treasury management with cash pooling
- **FO-CF-011**: Advanced cash flow modeling
- **FO-CF-012**: Bank relationship management
- **FO-CF-013**: Cash flow optimization recommendations
- **FO-CF-014**: Multi-entity cash consolidation
- **FO-CF-015**: Treasury analytics and KPIs

#### Cost Management (Tier 3)
- **FO-CM-011**: Complex cost allocation hierarchies
- **FO-CM-012**: Standard cost maintenance
- **FO-CM-013**: Cost rollup across organizations
- **FO-CM-014**: Cost simulation and what-if analysis
- **FO-CM-015**: Transfer pricing support
- **FO-CM-016**: Cost allocation audit trail

#### Asset Depreciation (Tier 3)
- **FO-AD-011**: Full depreciation method library (MACRS, annuity, units-of-production)
- **FO-AD-012**: Asset impairment handling
- **FO-AD-013**: Asset retirement and disposal
- **FO-AD-014**: Asset lease accounting support
- **FO-AD-015**: Multi-book depreciation
- **FO-AD-016**: Depreciation forecasting

#### GL Posting (Tier 3)
- **FO-GL-009**: Real-time GL posting
- **FO-GL-010**: Advanced validation and approval workflows
- **FO-GL-011**: Foreign currency translation
- **FO-GL-012**: Consolidation eliminations
- **FO-GL-013**: Detailed audit trail
- **FO-GL-014**: Compliance posting rules

#### Budget Tracking (Tier 3)
- **FO-BT-009**: Top-down and bottom-up budgeting
- **FO-BT-010**: Budget workflow and approval
- **FO-BT-011**: Zero-based budgeting support
- **FO-BT-012**: Budget vs actual by dimension (department, project, product)
- **FO-BT-013**: Rolling budget updates
- **FO-BT-014**: Budget scenario planning

---

## 2. Non-Functional Requirements

### 2.1 Performance

| Requirement | Target | Notes |
|-------------|--------|-------|
| **FO-NF-001**: Cash position query | < 500ms | For single account |
| **FO-NF-002**: Cash flow forecast generation | < 2s | For 12-month forecast |
| **FO-NF-003**: Cost allocation run | < 5s | For 10,000 transactions |
| **FO-NF-004**: Depreciation batch run | < 10s | For 1,000 assets |
| **FO-NF-005**: GL posting throughput | > 100 entries/sec | For bulk posting |
| **FO-NF-006**: Reconciliation check | < 1s | For subledger vs GL |

### 2.2 Scalability

| Requirement | Target |
|-------------|--------|
| **FO-NF-007**: Support concurrent coordinators | 10+ simultaneous operations |
| **FO-NF-008**: Support large data sets | 100,000+ transactions per batch |
| **FO-NF-009**: Horizontal scaling | Stateless coordinators |

### 2.3 Security

| Requirement | Description |
|-------------|-------------|
| **FO-NF-010**: Tenant isolation | All operations must respect tenant boundaries |
| **FO-NF-011**: Authorization | Role-based access control for all operations |
| **FO-NF-012**: Audit logging | All financial operations must be logged |
| **FO-NF-013**: Data validation | Input validation for all DTOs |

### 2.4 Reliability

| Requirement | Description |
|-------------|-------------|
| **FO-NF-014**: Transaction integrity | ACID transactions for financial operations |
| **FO-NF-015**: Idempotency | Operations can be safely retried |
| **FO-NF-016**: Error recovery | Graceful degradation with clear error messages |

---

## 3. Dependency Management Requirements

### 3.1 Interface Segregation

Per Section 5 of ARCHITECTURE.md, the orchestrator MUST:

1. **Define Own Interfaces**: All orchestration logic uses interfaces defined in `Contracts/`
2. **Use Atomic Interfaces via Adapter Pattern**: Atomic package interfaces are used but NOT directly depended upon
3. **No Direct Coupling**: Orchestrator has zero knowledge of atomic package implementations

```php
// Correct: Orchestrator defines its interface
interface CashFlowCoordinatorInterface
{
    public function getCashPosition(CashPositionRequest $request): CashPositionResult;
}

// Correct: Coordinator uses atomic interface internally
final readonly class CashFlowCoordinator implements CashFlowCoordinatorInterface
{
    public function __construct(
        private TreasuryManagerInterface $treasuryManager, // Atomic package interface
    ) {}
}
```

### 3.2 Dependency Inversion

All dependencies must follow DIP:
- High-level modules (Coordinators) depend on abstractions (Interfaces)
- Low-level modules (Atomic packages) implement abstractions
- Abstractions should not depend on details

### 3.3 Dependency List

| Package | Dependency Type | Usage |
|---------|----------------|-------|
| `nexus/treasury` | Required | Cash flow operations |
| `nexus/cost-accounting` | Required | Cost allocation |
| `nexus/fixed-asset-depreciation` | Required | Depreciation operations |
| `nexus/chart-of-account` | Required | GL account lookup |
| `nexus/journal-entry` | Required | Journal entry creation |
| `nexus/receivable` | Required | AR subledger |
| `nexus/payable` | Required | AP subledger |
| `nexus/assets` | Required | Asset data |
| `nexus/budget` | Required | Budget data |
| `nexus/period` | Required | Fiscal period validation |
| `nexus/currency` | Required | Multi-currency support |
| `psr/log` | Required | Logging interface |

---

## 4. Transaction Coordination Requirements

### 4.1 Atomic Transactions

All financial operations MUST be atomic:

```php
// Each subledger post must be wrapped in a transaction
public function postToGL(GLPostingRequest $request): GLPostingResult
{
    return $this->transactionManager->execute(function () use ($request) {
        // 1. Validate subledger is closed
        $this->validateSubledgerClosed($request);
        
        // 2. Create journal entry
        $journalEntry = $this->journalEntryPersist->create($entryData);
        
        // 3. Update subledger posting status
        $this->updatePostingStatus($request);
        
        return new GLPostingResult(success: true, entryId: $journalEntry->getId());
    });
}
```

### 4.2 Saga Pattern for Long-Running Operations

For operations spanning multiple packages, use saga pattern:

```php
interface FinanceWorkflowInterface
{
    public function execute(array $context): WorkflowResult;
    public function compensate(WorkflowResult $result): void;
    public function getCurrentStep(): ?string;
}
```

### 4.3 Two-Phase Commit

For critical multi-package operations:

1. **Prepare Phase**: All packages validate they can complete the operation
2. **Commit Phase**: All packages commit the transaction
3. **Rollback**: If any package fails, all are rolled back

---

## 5. Data Flow Requirements

### 5.1 Data Aggregation

DataProviders aggregate data from multiple atomic packages:

```
┌─────────────────────────────────────────────────────┐
│              DataProviders                          │
├─────────────────────────────────────────────────────┤
│ TreasuryDataProvider                                │
│   - getCashPosition() → Treasury + JournalEntry    │
│   - getForecast() → Treasury + Receivable + Payable│
├─────────────────────────────────────────────────────┤
│ CostAccountingDataProvider                          │
│   - getCostPools() → CostAccounting                 │
│   - getAllocatedCosts() → CostAccounting + GL      │
├─────────────────────────────────────────────────────┤
│ DepreciationDataProvider                           │
│   - getDepreciationRuns() → FixedAssetDepreciation │
│   - getBookValues() → Assets + FixedAssetDepreciation│
└─────────────────────────────────────────────────────┘
```

### 5.2 Data Freshness

| Operation | Data Freshness Requirement |
|-----------|---------------------------|
| Cash position | Real-time (up to 5 min latency) |
| Cost allocation | End-of-day or real-time |
| Depreciation | Per period close |
| GL posting | Real-time |
| Budget tracking | Real-time |

---

## 6. Interface Segregation Approach

### 6.1 Own Interfaces First

The orchestrator defines interfaces for all its operations:

```php
// In Contracts/
interface CashFlowCoordinatorInterface
{
    public function getCashPosition(CashPositionRequest $request): CashPositionResult;
    public function generateForecast(ForecastRequest $request): ForecastResult;
}

// NOT: using Nexus\Treasury\Contracts\TreasuryManagerInterface directly in consumers
```

### 6.2 Adapter Pattern for Integration

Atomic packages are accessed through adapters:

```php
// In DataProviders/
final readonly class TreasuryDataProvider implements TreasuryDataProviderInterface
{
    public function __construct(
        private TreasuryManagerInterface $treasuryManager, // From atomic package
    ) {}
    
    public function getCashPosition(string $tenantId, string $bankAccountId): CashPositionData
    {
        // Translate atomic package data to orchestrator DTO
        $position = $this->treasuryManager->getPosition($tenantId, $bankAccountId);
        return new CashPositionData(
            balance: $position->getBalance(),
            currency: $position->getCurrency(),
            asOfDate: $position->getAsOfDate()
        );
    }
}
```

---

## 7. CQRS Pattern Implementation

### 7.1 Command Side (Write Operations)

```php
// Coordinators handle commands
final readonly class CostAllocationCoordinator implements CostAllocationCoordinatorInterface
{
    public function allocateCosts(CostAllocationRequest $request): CostAllocationResult
    {
        // Validate
        $this->validateRequest($request);
        
        // Execute
        $allocation = $this->allocationService->allocate(
            $request->tenantId,
            $request->periodId,
            $request->amount,
            $request->targetCostCenter
        );
        
        // Return result
        return new CostAllocationResult(
            success: true,
            allocatedAmount: $allocation->getTotalAllocated(),
            entries: $allocation->getJournalEntries()
        );
    }
}
```

### 7.2 Query Side (Read Operations)

```php
// DataProviders handle queries
final readonly class CostAccountingDataProvider implements CostAccountingDataProviderInterface
{
    public function getCostCenterSummary(string $tenantId, string $costCenterId): CostCenterSummary
    {
        // Read from multiple sources
        $costCenter = $this->costCenterQuery->find($costCenterId);
        $actualCosts = $this->costQuery->getActualCosts($tenantId, $costCenterId);
        $budget = $this->budgetQuery->getBudget($tenantId, $costCenterId);
        
        return new CostCenterSummary(
            costCenter: $costCenter,
            actualCosts: $actualCosts,
            budget: $budget,
            variance: $budget->getAmount() - $actualCosts->getTotal()
        );
    }
}
```

---

## 8. Immutability Requirements

### 8.1 DTOs

All DTOs must be immutable:

```php
final readonly class CashPositionRequest
{
    public function __construct(
        public string $tenantId,
        public string $bankAccountId,
        public ?\DateTimeImmutable $asOfDate = null,
    ) {}
}
```

### 8.2 Service Classes

All service classes must be final and readonly:

```php
final readonly class CashPositionService
{
    public function __construct(
        private TreasuryDataProviderInterface $dataProvider,
    ) {}
    
    public function getPosition(string $tenantId, string $bankAccountId): CashPositionData
    {
        // Implementation
    }
}
```

### 8.3 Value Objects

Value objects should use readonly properties:

```php
final readonly class CashPositionData
{
    public function __construct(
        public Money $balance,
        public string $currency,
        public \DateTimeImmutable $asOfDate,
    ) {}
}
```

---

## 9. Domain Model Requirements

### 9.1 Entities

The orchestrator does NOT own entities - it coordinates atomic package entities. However, it may define:

- **Workflow State**: Tracks saga/step progress
- **Temporary Coordination Data**: Used during orchestration

### 9.2 Value Objects

| Value Object | Description |
|--------------|-------------|
| `CashPositionData` | Aggregated cash position across accounts |
| `CostAllocationData` | Cost allocation details |
| `DepreciationRunData` | Depreciation calculation results |
| `GLReconciliationData` | Subledger vs GL reconciliation status |
| `BudgetVarianceData` | Budget vs actual variance |

### 9.3 DTOs

Request/Response DTOs for all operations:

| DTO | Type | Purpose |
|-----|------|---------|
| `CashPositionRequest` | Request | Get cash position |
| `CashPositionResult` | Response | Cash position data |
| `CostAllocationRequest` | Request | Allocate costs |
| `CostAllocationResult` | Response | Allocation results |
| `DepreciationRunRequest` | Request | Run depreciation |
| `DepreciationRunResult` | Response | Depreciation results |
| `GLPostingRequest` | Request | Post to GL |
| `GLPostingResult` | Response | Posting results |

---

## 10. Testing Approach

### 10.1 Unit Testing

| Component | Test Strategy |
|-----------|---------------|
| Coordinators | Mock all dependencies, test orchestration logic |
| DataProviders | Mock atomic package interfaces, verify data transformation |
| Services | Mock data providers, test calculation logic |
| Rules | Test validation logic with various inputs |
| Workflows | Test state transitions, compensation logic |

### 10.2 Integration Testing

| Scenario | Description |
|----------|-------------|
| Full orchestration | Test complete flow through all coordinators |
| Error handling | Test rollback and compensation |
| Performance | Test with realistic data volumes |

### 10.3 Contract Testing

| Contract | Description |
|----------|-------------|
| Atomic package interfaces | Verify adapters satisfy required interfaces |
| Orchestrator interfaces | Verify consumers use correct interface methods |

---

## 11. Compliance Requirements

### 11.1 Financial Controls

- All postings must have audit trail
- Segregation of duties must be enforceable
- Approval workflows for significant amounts

### 11.2 Regulatory Support

- Multi-currency support for international operations
- Tax jurisdiction handling
- Local accounting standards compliance

### 11.3 Reporting

- Standard financial reports generation
- Custom report support via data providers
- Export capabilities

---

## 12. Future Extensibility

### 12.1 Planned Features

- Advanced treasury management
- Real-time financial consolidation
- AI-powered cash flow predictions

### 12.2 Extension Points

- Custom data providers
- Custom rules engine
- Workflow templates
- Event-driven automation
