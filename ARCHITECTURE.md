# Nexus Architectural Guidelines & Coding Standards

This document serves as the single source of truth for the architectural integrity and development standards within the Nexus monorepo. **Adherence to these rules is mandatory.**

---

## Table of Contents

1. [The Three-Layer Architecture](#1-the-three-layer-architecture)
2. [Atomic Packages Catalog](#2-atomic-packages-catalog)
3. [Package Atomicity Principles](#3-package-atomicity-principles)
4. [The Advanced Orchestrator Pattern](#4-the-advanced-orchestrator-pattern)
5. [Orchestrator Interface Segregation](#5-orchestrator-interface-segregation)
6. [Coding Standards (PHP 8.3+)](#6-coding-standards-php-83)
7. [Security & Authorization](#7-security--authorization)
8. [Requirements Composition Guidelines](#8-requirements-composition-guidelines)
9. [Decision Matrix: Where Does Logic Go?](#9-decision-matrix-where-does-logic-go)
10. [Correct vs Incorrect Execution Examples](#10-correct-vs-incorrect-execution-examples)

---

## 1. The Three-Layer Architecture

Nexus separates concerns into three distinct vertical layers to ensure portability, testability, and scalability.

### Layer 1: Atomic Packages (`packages/`)
- **Purpose**: Pure business logic and domain "truth".
- **Rules**: 
    - Pure PHP 8.3+ only.
    - Zero framework dependencies.
    - No database logic or migrations.
    - Define all external needs (storage, config) via interfaces (`Contracts/`).
- **Structure**: Flat (`src/Services`, `src/Contracts`) or DDD (`src/Domain`, `src/Application`).

### Layer 2: Orchestrators (`orchestrators/`)
- **Purpose**: Cross-package workflow coordination.
- **Rules**:
    - Pure PHP (framework-agnostic).
    - Coordinates interactions between multiple Layer 1 packages.
    - Implements the "Advanced Orchestrator Pattern" (see Section 4).
- **Structure**: `Coordinators/`, `DataProviders/`, `Rules/`, `Workflows/`.

### Layer 3: Adapters (`adapters/`)
- **Purpose**: Framework-specific implementations (e.g., Laravel, Symfony).
- **Contains**: Eloquent models, migrations, service providers, controllers, and jobs.
- **Role**: Concrete implementation of interfaces defined in Layers 1 and 2.

### Architecture Flow Diagram

```
┌─────────────────────────────────────────────────────────┐
│                    Adapters (L3)                        │
│   Implements orchestrator interfaces using atomic pkgs  │
│   Contains: Eloquent models, migrations, controllers     │
└─────────────────────────────────────────────────────────┘
                           ▲ implements
┌─────────────────────────────────────────────────────────┐
│                 Orchestrators (L2)                      │
│   - Defines own interfaces in Contracts/              │
│   - Depends only on: php, psr/log, psr/event-dispatcher │
│   - Coordinates multi-package workflows                │
│   - Publishable as standalone composer package         │
└─────────────────────────────────────────────────────────┘
                           ▲ uses via interfaces
┌─────────────────────────────────────────────────────────┐
│                Atomic Packages (L1)                     │
│   - Pure business logic, framework-agnostic            │
│   - Publishable on their own (Common + PSR only)       │
│   - No knowledge of orchestrator requirements           │
│   - Owns the "truth" of the domain                     │
└─────────────────────────────────────────────────────────┘
```

---

## 2. Atomic Packages Catalog

This section provides a comprehensive reference of all 54 atomic packages organized by functional domain. Each package is self-contained and can be independently published.

### 2.1 Foundation Layer (9 packages)

Core infrastructure and multi-tenancy support - these packages are used by almost all other packages.

| Package | Namespace | Purpose | Key Interfaces |
|---------|-----------|---------|----------------|
| **Tenant** | `Nexus\Tenant` | Multi-tenancy context and isolation | `TenantContextInterface`, `TenantRepositoryInterface` |
| **Sequencing** | `Nexus\Sequencing` | Auto-numbering with patterns | `SequenceManagerInterface`, `SequenceGeneratorInterface` |
| **Period** | `Nexus\Period` | Fiscal period management | `PeriodManagerInterface`, `FiscalCalendarInterface` |
| **Uom** | `Nexus\Uom` | Unit of measurement conversions | `UomManagerInterface`, `UomConverterInterface` |
| **AuditLogger** | `Nexus\AuditLogger` | Timeline feeds and audit trails | `AuditLogManagerInterface`, `TimelineProviderInterface` |
| **EventStream** | `Nexus\EventStream` | Event sourcing for critical domains | `EventStoreInterface`, `EventPublisherInterface` |
| **Setting** | `Nexus\Setting` | Application settings management | `SettingManagerInterface`, `SettingRepositoryInterface` |
| **Monitoring** | `Nexus\Monitoring` | Observability and telemetry | `MonitorInterface`, `MetricsCollectorInterface` |
| **FeatureFlags** | `Nexus\FeatureFlags` | Feature flag management | `FeatureFlagManagerInterface`, `FeatureToggleInterface` |

### 2.2 Identity & Security (4 packages)

Authentication, authorization, and security-related functionality.

| Package | Namespace | Purpose | Key Interfaces |
|---------|-----------|---------|----------------|
| **Identity** | `Nexus\Identity` | Authentication, RBAC, MFA | `UserManagerInterface`, `PermissionCheckerInterface`, `MfaEnrollmentInterface` |
| **SSO** | `Nexus\SSO` | Single Sign-On federation | `SsoProviderInterface`, `SamlHandlerInterface` |
| **Crypto** | `Nexus\Crypto` | Cryptographic operations | `HasherInterface`, `EncryptionServiceInterface` |
| **Audit** | `Nexus\Audit` | Advanced audit capabilities | `AuditTrailInterface`, `ComplianceReporterInterface` |

### 2.3 Financial Management (8 packages)

Core accounting and finance functionality.

| Package | Namespace | Purpose | Key Interfaces |
|---------|-----------|---------|----------------|
| **Finance** | `Nexus\Finance` | General ledger | `GeneralLedgerManagerInterface`, `JournalEntryManagerInterface`, `ChartOfAccountInterface` |
| **Accounting** | `Nexus\Accounting` | Financial statements | `StatementBuilderInterface`, `BalanceSheetBuilderInterface` |
| **AccountConsolidation** | `Nexus\AccountConsolidation` | Multi-entity consolidation | `ConsolidationEngineInterface`, `IntercompanyEliminationInterface` |
| **AccountPeriodClose** | `Nexus\AccountPeriodClose` | Close accounting periods | `PeriodCloseManagerInterface`, `ClosingEntryGeneratorInterface` |
| **AccountVarianceAnalysis** | `Nexus\AccountVarianceAnalysis` | Analyze variances | `VarianceCalculatorInterface`, `TrendAnalyzerInterface` |
| **FinancialRatios** | `Nexus\FinancialRatios` | Calculate financial ratios | `RatioCalculatorInterface`, `DuPontAnalyzerInterface` |
| **FinancialStatements** | `Nexus\FinancialStatements` | Generate statements | `StatementGeneratorInterface`, `FinancialStatementBuilderInterface` |
| **Receivable** | `Nexus\Receivable` | Customer invoicing | `InvoiceManagerInterface`, `ReceivableAgingInterface`, `CreditNoteManagerInterface` |
| **Payable** | `Nexus\Payable` | Vendor bills | `BillManagerInterface`, `PayablePaymentInterface` |
| **CashManagement** | `Nexus\CashManagement` | Bank reconciliation | `BankReconciliationInterface`, `CashFlowAnalyzerInterface` |
| **Budget** | `Nexus\Budget` | Budget planning | `BudgetManagerInterface`, `BudgetVersionInterface` |
| **Assets** | `Nexus\Assets` | Fixed asset management | `AssetManagerInterface`, `DepreciationCalculatorInterface` |
| **Tax** | `Nexus\Tax` | Tax calculations and compliance | `TaxCalculatorInterface`, `TaxReturnInterface` |
| **JournalEntry** | `Nexus\JournalEntry` | Journal entry processing | `JournalEntryProcessorInterface`, `PostingStrategyInterface` |

### 2.4 Sales & Operations (6 packages)

Sales, inventory, procurement, and manufacturing.

| Package | Namespace | Purpose | Key Interfaces |
|---------|-----------|---------|----------------|
| **Sales** | `Nexus\Sales` | Quotation-to-order lifecycle | `SalesOrderManagerInterface`, `QuoteManagerInterface`, `PricingEngineInterface` |
| **Inventory** | `Nexus\Inventory` | Stock management with lot/serial | `StockManagerInterface`, `LotManagerInterface`, `InventoryValuationInterface` |
| **Warehouse** | `Nexus\Warehouse` | Warehouse operations | `WarehouseManagerInterface`, `LocationManagerInterface`, `PickPackInterface` |
| **Procurement** | `Nexus\Procurement` | Purchase management | `PurchaseOrderManagerInterface`, `SupplierManagerInterface`, `PurchasePricingInterface` |
| **Manufacturing** | `Nexus\Manufacturing` | MRP II, BOMs, work orders | `BomManagerInterface`, `WorkOrderManagerInterface`, `MrpEngineInterface` |
| **Product** | `Nexus\Product` | Product catalog | `ProductManagerInterface`, `ProductVariantInterface`, `CategoryManagerInterface` |

### 2.5 Human Resources (3 packages)

HR, payroll, and statutory compliance.

| Package | Namespace | Purpose | Key Interfaces |
|---------|-----------|---------|----------------|
| **HRM** | `Nexus\HRM` | Leave, attendance, performance | `LeaveManagerInterface`, `AttendanceManagerInterface`, `PerformanceReviewInterface` |
| **Payroll** | `Nexus\Payroll` | Payroll processing | `PayrollManagerInterface`, `SalaryCalculatorInterface`, `PaySlipGeneratorInterface` |
| **PayrollMysStatutory** | `Nexus\PayrollMysStatutory` | Malaysian statutory | `StatutoryManagerInterface`, `EpfoManagerInterface`, `TaxCalcManagerInterface` |

### 2.6 Customer & Partner (2 packages)

Customer relationship and field service management.

| Package | Namespace | Purpose | Key Interfaces |
|---------|-----------|---------|----------------|
| **Party** | `Nexus\Party` | Customers, vendors, employees | `PartyManagerInterface`, `ContactManagerInterface`, `AddressManagerInterface` |
| **FieldService** | `Nexus\FieldService` | Work orders and service | `FieldServiceManagerInterface`, `WorkOrderInterface`, `TechnicianScheduleInterface` |

### 2.7 Integration & Automation (9 packages)

Integration hub, workflow automation, and processing.

| Package | Namespace | Purpose | Key Interfaces |
|---------|-----------|---------|----------------|
| **Connector** | `Nexus\Connector` | Integration hub | `IntegrationHubInterface`, `ConnectorInterface`, `DataMapperInterface` |
| **Workflow** | `Nexus\Workflow` | Process automation | `WorkflowEngineInterface`, `WorkflowDefinitionInterface` |
| **Notifier** | `Nexus\Notifier` | Multi-channel notifications | `NotificationManagerInterface`, `ChannelInterface`, `TemplateInterface` |
| **Scheduler** | `Nexus\Scheduler` | Task scheduling | `SchedulerInterface`, `TaskRunnerInterface`, `CronExpressionInterface` |
| **DataProcessor** | `Nexus\DataProcessor` | OCR and ETL | `DataProcessorInterface`, `OcrEngineInterface`, `TransformRuleInterface` |
| **MachineLearning** | `Nexus\MachineLearning` | ML orchestration | `MlModelInterface`, `PredictionEngineInterface`, `TrainingJobInterface` |
| **ProcurementML** | `Nexus\ProcurementML` | Procurement ML features | `PricePredictionInterface`, `SupplierRecommendationInterface` |
| **Geo** | `Nexus\Geo` | Geocoding and geofencing | `GeoCoderInterface`, `GeofenceManagerInterface` |
| **Routing** | `Nexus\Routing` | Route optimization | `RouteOptimizerInterface`, `RouteCalculatorInterface` |

### 2.8 Reporting & Data (6 packages)

Reporting, export, import, and analytics.

| Package | Namespace | Purpose | Key Interfaces |
|---------|-----------|---------|----------------|
| **Reporting** | `Nexus\Reporting` | Report engine | `ReportBuilderInterface`, `ReportRendererInterface` |
| **Export** | `Nexus\Export` | Multi-format export | `ExportManagerInterface`, `ExporterInterface`, `FormatConverterInterface` |
| **Import** | `Nexus\Import` | Data import with validation | `ImportManagerInterface`, `ValidationRuleInterface`, `DataParserInterface` |
| **Analytics** | `Nexus\Analytics` | Business intelligence | `AnalyticsEngineInterface`, `DataWarehouseInterface`, `DashboardInterface` |
| **Currency** | `Nexus\Currency` | Multi-currency management | `CurrencyManagerInterface`, `ExchangeRateInterface` |
| **Document** | `Nexus\Document` | Document management | `DocumentManagerInterface`, `VersionControlInterface`, `ContentProcessorInterface` |

### 2.9 Compliance & Governance (3 packages)

Compliance, statutory reporting, and organizational structure.

| Package | Namespace | Purpose | Key Interfaces |
|---------|-----------|---------|----------------|
| **Compliance** | `Nexus\Compliance` | Process enforcement | `ComplianceRuleInterface`, `PolicyEvaluatorInterface` |
| **Statutory** | `Nexus\Statutory` | Reporting compliance | `StatutoryReportInterface`, `RegulatoryFilingInterface` |
| **Backoffice** | `Nexus\Backoffice` | Company structure | `CompanyManagerInterface`, `DepartmentManagerInterface`, `OfficeManagerInterface` |

### 2.10 Content & Storage (3 packages)

Content management and storage abstraction.

| Package | Namespace | Purpose | Key Interfaces |
|---------|-----------|---------|----------------|
| **Content** | `Nexus\Content` | Content management | `ContentManagerInterface`, `ContentVersionInterface` |
| **Storage** | `Nexus\Storage` | File storage abstraction | `StorageManagerInterface`, `FileSystemInterface` |
| **Messaging** | `Nexus\Messaging` | Message queue abstraction | `MessageProducerInterface`, `MessageConsumerInterface` |

### 2.11 CRM (1 package)

Customer relationship management.

| Package | Namespace | Purpose | Key Interfaces |
|---------|-----------|---------|----------------|
| **CRM** | `Nexus\CRM` | Lead, opportunity, pipeline | `LeadManagerInterface`, `OpportunityManagerInterface`, `PipelineManagerInterface` |

### 2.12 Payment (Multiple packages)

Payment processing and management.

| Package | Namespace | Purpose | Key Interfaces |
|---------|-----------|---------|----------------|
| **Payment** | `Nexus\Payment` | Payment processing | `PaymentManagerInterface`, `PaymentGatewayInterface` |
| **PaymentBank** | `Nexus\PaymentBank` | Bank payment processing | `BankPaymentInterface`, `BankTransferInterface` |
| **PaymentGateway** | `Nexus\PaymentGateway` | Gateway integration | `GatewayConnectorInterface`, `PaymentProcessorInterface` |
| **PaymentRails** | `Nexus\PaymentRails` | Payment rails | `PaymentRailInterface`, `RailConnectorInterface` |
| **PaymentRecurring** | `Nexus\PaymentRecurring` | Recurring payments | `RecurringPaymentInterface`, `SubscriptionManagerInterface` |
| **PaymentWallet** | `Nexus\PaymentWallet` | Digital wallet | `WalletManagerInterface`, `WalletBalanceInterface` |

### 2.13 Compliance & Risk (Multiple packages)

Compliance, risk management, and verification.

| Package | Namespace | Purpose | Key Interfaces |
|---------|-----------|---------|----------------|
| **AmlCompliance** | `Nexus\AmlCompliance` | Anti-money laundering | `AmlCheckerInterface`, `SanctionsScreeningInterface` |
| **KycVerification** | `Nexus\KycVerification` | KYC verification | `KycVerifierInterface`, `DocumentVerificationInterface` |
| **Sanctions** | `Nexus\Sanctions` | Sanctions screening | `SanctionsListInterface`, `ScreeningServiceInterface` |
| **GDPR** | `Nexus\GDPR` | Data privacy (GDPR) | `DataPrivacyInterface`, `RightToErasureInterface` |
| **PDPA** | `Nexus\PDPA` | Data protection (PDPA) | `PdpaComplianceInterface`, `ConsentManagerInterface` |
| **DataPrivacy** | `Nexus\DataPrivacy` | General data privacy | `PrivacyManagerInterface`, `DataClassificationInterface` |
| **QualityControl** | `Nexus\QualityControl` | QC management | `QcManagerInterface`, `InspectionInterface`, `DefectTrackerInterface` |
| **PerformanceReview** | `Nexus\PerformanceReview` | Performance reviews | `ReviewManagerInterface`, `RatingInterface`, `GoalTrackerInterface` |

### 2.14 Shared (1 package)

Common utilities used across all packages.

| Package | Namespace | Purpose | Key Interfaces |
|---------|-----------|---------|----------------|
| **Common** | `Nexus\Common` | Common VOs, Contracts, Traits | Various utility interfaces |

---

## 3. Package Atomicity Principles

An **Atomic Package** is a self-contained unit addressing ONE business domain.

### Mandatory Metadata
Every package MUST include:
- `README.md`: Usage examples and interface definitions.
- `composer.json`: Requiring PHP ^8.3 and proper namespaces.
- `IMPLEMENTATION_SUMMARY.md`: Progress and checklist.
- `VALUATION_MATRIX.md`: Metrics on complexity and coverage.

### Anti-Pattern: God Packages
Avoid packages that handle multiple unrelated domains. If a package description needs more than two "and" conjunctions, it should likely be split (e.g., "Finance" split into "Receivable", "Payable", "GeneralLedger").

---

## 4. The Advanced Orchestrator Pattern

Used in `orchestrators/` to prevent "God Class" coordinators.

| Component | Responsibility | Rule |
| :--- | :--- | :--- |
| **Coordinators** | Traffic management. | Directs flow, executes no logic or fetching. |
| **DataProviders** | Aggregation. | Fetches context from multiple packages into a Context DTO. |
| **Rules** | Validation. | Single-class business constraints (e.g., `PeriodOpenRule`). |
| **Services** | Calculations. | Heavy lifting and cross-boundary logic. |
| **Workflows** | Statefulness. | Manages long-running Sagas and state-machine transitions. |

### 4.1 Orchestrator Directory Structure

```
orchestrators/AccountingOperations/
├── composer.json
├── README.md
├── src/
│   ├── Contracts/              # Orchestrator-defined interfaces
│   │   ├── AccountingWorkflowInterface.php
│   │   ├── AccountingCoordinatorInterface.php
│   │   ├── PeriodCloseManagerInterface.php
│   │   ├── StatementDataProviderInterface.php
│   │   └── ...
│   │
│   ├── Coordinators/           # Stateless workflow directors
│   │   ├── PeriodCloseCoordinator.php
│   │   ├── StatementGenerationCoordinator.php
│   │   ├── ConsolidationCoordinator.php
│   │   └── ...
│   │
│   ├── DataProviders/         # Cross-package data aggregation
│   │   ├── FinanceDataProvider.php
│   │   ├── BudgetDataProvider.php
│   │   ├── ConsolidationDataProvider.php
│   │   └── ...
│   │
│   ├── Services/              # Orchestration services
│   │   ├── FinanceStatementBuilder.php
│   │   └── ...
│   │
│   ├── Rules/                 # Cross-package validation
│   │   ├── CloseRead.php
│   │   └── ...
│   │
│inessRule   ├── Workflows/            # Stateful processes (Sagas)
│   │   ├── PeriodClose/
│   │   │   ├── PeriodCloseWorkflow.php
│   │   │   ├── Steps/
│   │   │   └── States/
│   │   └── ...
│   │
│   ├── DTOs/                  # Request/Response objects
│   │   ├── PeriodCloseRequest.php
│   │   ├── PeriodCloseResult.php
│   │   └── ...
│   │
│   └── Exceptions/           # Process-specific exceptions
│       └── PeriodCloseFailedException.php
│
└── tests/
    ├── Unit/
    └── Feature/
```

### 4.2 Current Orchestrators

| Orchestrator | Packages Coordinated | Key Workflows |
|-------------|---------------------|---------------|
| **IdentityOperations** | Identity, Tenant, AuditLogger | User lifecycle, MFA, session management |
| **SupplyChainOperations** | Inventory, Procurement, Sales, Warehouse, Receivable | Dropship, RMA, landed cost, ATP |
| **AccountingOperations** | Finance, Accounting, AccountConsolidation, AccountPeriodClose, FinancialRatios | Period close, consolidation, statements, ratios |
| **HumanResourceOperations** | HRM, Payroll, PayrollMysStatutory | Employee lifecycle, payroll processing |
| **SalesOperations** | Sales, CRM, Inventory, Receivable | Quote-to-cash, opportunity management |
| **ProcurementOperations** | Procurement, Payable, Inventory | Purchase-to-pay, supplier management |
| **ComplianceOperations** | Compliance, Audit, AML, GDPR, PDPA | Compliance monitoring, audit trails |
| **CRMOperations** | CRM, Party, Sales, Notifications | Lead management, pipeline, activities |

---

## 5. Orchestrator Interface Segregation (CRITICAL)

**Orchestrators MUST define their own interfaces and NOT depend on atomic package interfaces directly.**

See [ORCHESTRATOR_INTERFACE_SEGREGATION.md](docs/ORCHESTRATOR_INTERFACE_SEGREGATION.md) for complete guidelines.

### Why Interface Segregation Matters

- **Enables** orchestrators to be published independently
- **Allows** swapping atomic package implementations via adapters
- **Maintains** SOLID compliance (ISP, DIP)
- **Keeps** atomic packages truly atomic

### Dependency Rules by Layer

| Layer | Can Depend On | Cannot Depend On |
|-------|---------------|------------------|
| **Atomic Packages** | Common, PSR interfaces | Other atomic packages, Orchestrators, Adapters |
| **Orchestrators** | PSR interfaces, **Own interfaces only** | Atomic packages directly, Adapters, Frameworks |
| **Adapters** | Everything (Atomic packages, Orchestrator interfaces) | Nothing (they are the leaf) |

---

## 6. Coding Standards (PHP 8.3+)

### Core Requirements

1. **Strict Types**: `declare(strict_types=1);` is mandatory in every file.
2. **Framework Agnosticism**: No `use Illuminate\*` or `use Symfony\*` in Layers 1 or 2.
3. **Dependency Injection**: Constructor injection only. Depend on **Interfaces**, never concrete classes.
4. **Immutability**:
    - **Service Classes**: `final readonly class`.
    - **Value Objects**: Per-property `readonly` on promoted properties.

### REPOSITORY DESIGN (CQRS)

Every domain repository should be split into:
- `{Entity}QueryInterface`: For side-effect-free read operations.
- `{Entity}PersistInterface`: For write operations.

### Error Handling

- Never throw generic `\Exception`.
- Use domain-specific exceptions (e.g., `TenantNotFoundException`).
- Use "Throw in Expressions" for cleaner null-coalescing handler.

---

## 7. Security & Authorization

### The Rule of Identity

All authorization MUST flow through **`Nexus\Identity`**. Do not implement custom Auth logic.

- **RBAC**: Use `PermissionCheckerInterface` for simple capability checks.
- **ABAC**: Use `PolicyEvaluatorInterface` for context-aware rules (e.g., "User can edit if they are the creator").
- **Implementation**: Concrete policy evaluations (e.g., in a Laravel closure) reside in the `adapters/` layer.

---

## 8. Requirements Composition Guidelines

These guidelines help avoid duplicate functionality when composing requirements across packages.

### 8.1 Package Ownership Matrix

Before adding new functionality, consult this matrix to determine which package should own it:

| Business Capability | Primary Package | Supporting Packages |
|-------------------|-----------------|---------------------|
| User Authentication | Identity | Tenant, AuditLogger |
| User Authorization | Identity | - |
| Multi-tenancy | Tenant | All packages |
| Fiscal Periods | Period | Finance, Accounting, Budget |
| Party (Customer/Vendor) | Party | Sales, Procurement, Receivable, Payable |
| Product Catalog | Product | Inventory, Sales, Procurement |
| Stock Management | Inventory | Warehouse, Product |
| General Ledger | Finance | JournalEntry, ChartOfAccount |
| Invoicing | Receivable | Finance, Party |
| Purchase Bills | Payable | Finance, Party |
| Fixed Assets | Assets | Finance |
| Tax Calculation | Tax | Finance, Receivable, Payable |
| Budget Management | Budget | Finance |
| Bank Reconciliation | CashManagement | Finance |
| Leave Management | HRM | - |
| Payroll Processing | Payroll | HRM, Tax |
| Document Storage | Document | Storage |

### 8.2 Cross-Cutting Concerns

When a requirement spans multiple packages, it belongs in an **Orchestrator**:

| Requirement | Orchestrator |
|------------|--------------|
| Close accounting period | AccountingOperations |
| Generate consolidated financials | AccountingOperations |
| Process dropship order | SupplyChainOperations |
| Handle RMA workflow | SupplyChainOperations |
| Onboard new employee | HumanResourceOperations |
| Process payroll run | HumanResourceOperations |
| Process sales order | SalesOperations |
| Screen against sanctions | ComplianceOperations |

### 8.3 Duplicate Functionality Prevention

**Rule**: If functionality exists in an atomic package, **do not duplicate** it in an orchestrator. Instead:

1. **Use** the atomic package interface via adapter
2. **Extend** with orchestrator-specific interfaces if needed
3. **Compose** multiple packages in a DataProvider if data from multiple sources is needed

**Example of what NOT to do**:
```
❌ WRONG: Creating a new "UserManager" in IdentityOperations that duplicates Identity package functionality
```

**Example of what TO do**:
```
✅ CORRECT: IdentityOperations defines its own interface and uses an adapter to implement via Identity package
```

---

## 9. Decision Matrix: Where Does Logic Go?

Use this matrix to determine where code should live:

| Question | Answer | Location |
|----------|--------|----------|
| Is this pure business logic for a single domain? | Yes | Atomic Package (`packages/`) |
| Does this coordinate multiple atomic packages? | Yes | Orchestrator (`orchestrators/`) |
| Does this contain database/Eloquent code? | Yes | Adapter (`adapters/`) |
| Is this framework-specific (Laravel/Symfony)? | Yes | Adapter (`adapters/`) |
| Does this validate business rules spanning packages? | Yes | Orchestrator Rules (`orchestrators/*/Rules/`) |
| Does this aggregate data from multiple packages? | Yes | Orchestrator DataProviders (`orchestrators/*/DataProviders/`) |
| Does this manage a stateful multi-step process? | Yes | Orchestrator Workflows (`orchestrators/*/Workflows/`) |
| Can this be published to Packagist independently? | Yes | Atomic Package or Orchestrator |

---

## 10. Correct vs Incorrect Execution Examples

This section provides concrete examples of architectural violations and their corrections.

### 10.1 Direct Atomic Package Import in Orchestrator

**❌ WRONG: Orchestrator directly depends on atomic package interface**

```php
// orchestrators/SupplyChainOperations/src/Coordinators/ReplenishmentCoordinator.php
<?php

declare(strict_types=1);

namespace Nexus\SupplyChainOperations\Coordinators;

use Nexus\Inventory\Contracts\StockManagerInterface; // ❌ FORBIDDEN!

final readonly class ReplenishmentCoordinator
{
    public function __construct(
        private StockManagerInterface $stockManager // Tightly coupled!
    ) {}
    
    public function execute(ReplenishmentRequest $request): ReplenishmentResult
    {
        // Uses atomic package directly - breaks publishability!
        $currentStock = $this->stockManager->getCurrentStock(
            $request->productId,
            $request->warehouseId
        );
        
        // ...
    }
}
```

**Problems:**
- Orchestrator now depends on Inventory package
- Cannot be published standalone
- Changes to Inventory package break this orchestrator
- Violates Interface Segregation Principle

---

**✅ CORRECT: Orchestrator defines its own interface, adapter implements it**

```php
// orchestrators/SupplyChainOperations/src/Contracts/SupplyChainStockManagerInterface.php
<?php

declare(strict_types=1);

namespace Nexus\SupplyChainOperations\Contracts;

interface SupplyChainStockManagerInterface
{
    public function getCurrentStock(string $productId, string $warehouseId): float;
    public function adjustStock(string $productId, string $warehouseId, float $qty, string $reason): void;
    public function getReorderPoint(string $productId, string $warehouseId): float;
}

// orchestrators/SupplyChainOperations/src/Coordinators/ReplenishmentCoordinator.php
<?php

declare(strict_types=1);

namespace Nexus\SupplyChainOperations\Coordinators;

use Nexus\SupplyChainOperations\Contracts\SupplyChainStockManagerInterface; // ✅ Own interface!

final readonly class ReplenishmentCoordinator
{
    public function __construct(
        private SupplyChainStockManagerInterface $stockManager // ✅ Decoupled!
    ) {}
    
    public function execute(ReplenishmentRequest $request): ReplenishmentResult
    {
        // Uses orchestrator's own interface
        $currentStock = $this->stockManager->getCurrentStock(
            $request->productId,
            $request->warehouseId
        );
        
        // ...
    }
}

// adapters/Laravel/SupplyChainOperations/src/Adapters/SupplyChainStockManagerAdapter.php
<?php

declare(strict_types=1);

namespace Nexus\SupplyChainOperationsAdapter\Adapters;

use Nexus\SupplyChainOperations\Contracts\SupplyChainStockManagerInterface;
use Nexus\Inventory\Contracts\StockManagerInterface;

final readonly class SupplyChainStockManagerAdapter implements SupplyChainStockManagerInterface
{
    public function __construct(
        private StockManagerInterface $inventoryStockManager
    ) {}
    
    public function getCurrentStock(string $productId, string $warehouseId): float
    {
        return $this->inventoryStockManager->getCurrentStock($productId, $warehouseId);
    }
    
    public function adjustStock(string $productId, string $warehouseId, float $qty, string $reason): void
    {
        $this->inventoryStockManager->adjustStock($productId, $warehouseId, $qty, $reason);
    }
    
    public function getReorderPoint(string $productId, string $warehouseId): float
    {
        // Adapter composes logic from multiple packages
        return $this->inventoryStockManager->getReorderPoint($productId) 
             * $this->inventoryStockManager->getSafetyStock($warehouseId);
    }
}
```

---

### 10.2 Framework Coupling in Atomic Package

**❌ WRONG: Atomic package uses framework code**

```php
// packages/Inventory/src/Services/StockManager.php
<?php

declare(strict_types=1);

namespace Nexus\Inventory\Services;

use Illuminate\Support\Facades\DB; // ❌ FORBIDDEN!
use Illuminate\Support\Facades\Log; // ❌ FORBIDDEN!

final class StockManager
{
    public function receiveStock(string $productId, float $quantity): void
    {
        // Using Laravel facade - not framework-agnostic!
        DB::table('inventory_stock')->insert([...]);
        Log::info('Stock received', ['product' => $productId]);
        
        // Using env() - not portable!
        $warehouseId = env('DEFAULT_WAREHOUSE_ID');
    }
}
```

**Problems:**
- Package can only be used in Laravel
- Cannot be published to Packagist
- Tied to specific environment
- Violates framework-agnosticism

---

**✅ CORRECT: Atomic package defines interfaces for external dependencies**

```php
// packages/Inventory/src/Contracts/StockRepositoryInterface.php
<?php

declare(strict_types=1);

namespace Nexus\Inventory\Contracts;

interface StockRepositoryInterface
{
    public function find(string $productId, string $warehouseId): ?Stock;
    public function save(Stock $stock): void;
    public function findByProduct(string $productId): array;
}

// packages/Inventory/src/Services/StockManager.php
<?php

declare(strict_types=1);

namespace Nexus\Inventory\Services;

use Nexus\Inventory\Contracts\StockRepositoryInterface;
use Nexus\Inventory\Contracts\AuditLoggerInterface;
use Psr\Log\LoggerInterface;

final readonly class StockManager
{
    public function __construct(
        private StockRepositoryInterface $repository,    // ✅ Injected via interface
        private ?AuditLoggerInterface $auditLogger,       // ✅ Optional audit
        private LoggerInterface $logger                   // ✅ PSR interface
    ) {}
    
    public function receiveStock(string $productId, string $warehouseId, float $quantity): Stock
    {
        $stock = $this->repository->find($productId, $warehouseId)
            ?? new Stock($productId, $warehouseId, 0.0);
        
        $stock->receive($quantity);
        $this->repository->save($stock);
        
        $this->logger->info('Stock received', [
            'product' => $productId,
            'warehouse' => $warehouseId,
            'quantity' => $quantity
        ]);
        
        return $stock;
    }
}
```

---

### 10.3 God Package Anti-Pattern

**❌ WRONG: Single package handles multiple unrelated domains**

```php
// packages/Enterprise/src/Services/EverythingManager.php - ❌ GOD PACKAGE!
<?php

declare(strict_types=1);

namespace Nexus\Enterprise\Services;

// This package does:
// - User management
// - Invoice processing
// - Inventory tracking
// - HR functions
// - Report generation
// - Email sending
// - File storage
// ... (4000+ lines of code)

final class EverythingManager
{
    public function createUser(...): User { ... }
    public function createInvoice(...): Invoice { ... }
    public function receiveStock(...): void { ... }
    public function processPayroll(...): void { ... }
    public function generateReport(...): void { ... }
    public function sendEmail(...): void { ... }
    public function storeFile(...): void { ... }
    // ... 200 more methods
}
```

**Problems:**
- Impossible to test
- Multiple reasons to change (violates SRP)
- Cannot be reused partially
- Becomes a maintenance nightmare

---

**✅ CORRECT: Split into focused atomic packages**

```
✅ packages/Identity/src/        - User management
✅ packages/Receivable/src/      - Invoice processing  
✅ packages/Inventory/src/      - Stock tracking
✅ packages/Payroll/src/         - Payroll processing
✅ packages/Reporting/src/      - Report generation
✅ packages/Notifier/src/       - Email sending
✅ packages/Storage/src/        - File storage
```

Each package:
- Has a single responsibility
- Can be tested in isolation
- Can be published independently
- Has clear boundaries

---

### 10.4 Stateful Service Anti-Pattern

**❌ WRONG: Service stores state in class properties**

```php
// packages/CircuitBreaker/src/Services/CircuitBreaker.php
<?php

declare(strict_types=1);

namespace Nexus\CircuitBreaker\Services;

final class CircuitBreaker
{
    private array $states = [];      // ❌ Lost when instance destroyed!
    private array $failureCounts = []; // ❌ Not persistent!
    
    public function recordFailure(string $connectionId): void
    {
        $this->failureCounts[$connectionId] ??= 0;
        $this->failureCounts[$connectionId]++;
        
        if ($this->failureCounts[$connectionId] >= 3) {
            $this->states[$connectionId] = 'open';
        }
    }
    
    public function isOpen(string $connectionId): bool
    {
        return ($this->states[$connectionId] ?? 'closed') === 'open';
    }
}
```

**Problems:**
- State lost when object is destroyed
- Not shareable across requests
- Cannot survive process restarts
- Violates stateless architecture

---

**✅ CORRECT: Externalize state via storage interface**

```php
// packages/CircuitBreaker/src/Contracts/CircuitBreakerStorageInterface.php
<?php

declare(strict_types=1);

namespace Nexus\CircuitBreaker\Contracts;

interface CircuitBreakerStorageInterface
{
    public function getState(string $connectionId): string;
    public function setState(string $connectionId, string $state): void;
    public function getFailureCount(string $connectionId): int;
    public function incrementFailureCount(string $connectionId): void;
    public function resetFailures(string $connectionId): void;
}

// packages/CircuitBreaker/src/Services/CircuitBreaker.php
<?php

declare(strict_types=1);

namespace Nexus\CircuitBreaker\Services;

use Nexus\CircuitBreaker\Contracts\CircuitBreakerStorageInterface;
use Psr\Log\LoggerInterface;

final readonly class CircuitBreaker
{
    private const FAILURE_THRESHOLD = 3;
    
    public function __construct(
        private CircuitBreakerStorageInterface $storage,
        private LoggerInterface $logger
    ) {}
    
    public function recordFailure(string $connectionId): void
    {
        $this->storage->incrementFailureCount($connectionId);
        $failureCount = $this->storage->getFailureCount($connectionId);
        
        if ($failureCount >= self::FAILURE_THRESHOLD) {
            $this->storage->setState($connectionId, 'open');
            $this->logger->warning('Circuit breaker opened', ['connection' => $connectionId]);
        }
    }
    
    public function isOpen(string $connectionId): bool
    {
        return $this->storage->getState($connectionId) === 'open';
    }
}
```

---

### 10.5 Missing Interface Segregation

**❌ WRONG: Fat interface forces implementations to implement unused methods**

```php
// packages/Document/src/Contracts/DocumentManagerInterface.php
<?php

declare(strict_types=1);

namespace Nexus\Document\Contracts;

interface DocumentManagerInterface
{
    public function upload(File $file): Document;           // Used by Upload workflow
    public function download(string $documentId): File;    // Used by Download workflow
    public function convertToPdf(string $documentId): void; // Used by PDF workflow - NOT ALL IMPLEMENTATIONS NEED THIS!
    public function extractText(string $documentId): string; // Used by OCR workflow - NOT ALL IMPLEMENTATIONS NEED THIS!
    public function applyWatermark(string $documentId, string $watermark): void; // Used by Compliance - NOT ALL NEED THIS!
    public function sendForSignature(string $documentId, array $signers): void; // Used by Signing - NOT ALL NEED THIS!
    public function archive(string $documentId): void;      // Used by Archive workflow
    // ... 30 more methods
}
```

**Problems:**
- Violates Interface Segregation Principle
- Implementations forced to implement unused methods
- Hard to swap implementations
- Package becomes bloated with features

---

**✅ CORRECT: Segregate interfaces by use case**

```php
// Core interface - common to all implementations
// packages/Document/src/Contracts/DocumentManagerInterface.php
<?php

declare(strict_types=1);

namespace Nexus\Document\Contracts;

interface DocumentManagerInterface
{
    public function find(string $id): ?Document;
    public function save(Document $document): void;
    public function delete(string $id): void;
}

// Upload-specific interface
// packages/Document/src/Contracts/UploadManagerInterface.php
<?php

declare(strict_types=1);

namespace Nexus\Document\Contracts;

interface UploadManagerInterface
{
    public function upload(File $file, UploadOptions $options): Document;
    public function validate(File $file): ValidationResult;
}

// PDF conversion interface
// packages/Document/src/Contracts/PdfConverterInterface.php
<?php

declare(strict_types=1);

namespace Nexus\Document\Contracts;

interface PdfConverterInterface
{
    public function convertToPdf(string $documentId): Document;
    public function supportsFormat(string $format): bool;
}

// Text extraction interface
// packages/Document/src/Contracts/TextExtractorInterface.php
<?php

declare(strict_types=1);

namespace Nexus\Document\Contracts;

interface TextExtractorInterface
{
    public function extractText(string $documentId): string;
    public function supportsFormat(string $format): bool;
}

// Usage in orchestrator - depends only on what it needs
// orchestrators/DocumentOperations/src/Coordinators/OcrWorkflowCoordinator.php
<?php

declare(strict_types=1);

namespace Nexus\DocumentOperations\Coordinators;

use Nexus\DocumentOperations\Contracts\OcrProcessorInterface;

final readonly class OcrWorkflowCoordinator
{
    public function __construct(
        private DocumentManagerInterface $documentManager,      // Core interface
        private TextExtractorInterface $textExtractor,           // OCR-specific
        private IndexManagerInterface $indexManager              // Search-specific
    ) {}
}
```

---

### 10.6 Circular Dependencies

**❌ WRONG: Package A depends on B, B depends on A**

```
packages/Finance/
└── composer.json
    └── requires: nexus/accounting

packages/Accounting/
└── composer.json  
    └── requires: nexus/finance

❌ CIRCULAR DEPENDENCY!
```

---

**✅ CORRECT: Extract shared logic to Common package**

```
packages/Common/
└── src/
    └── Contracts/
        └── JournalEntryInterface.php   // Shared interface

packages/Finance/
└── composer.json
    └── requires: nexus/common

packages/Accounting/
└── composer.json
    └── requires: nexus/common (NOT finance!)

✅ NO CIRCULAR DEPENDENCY!
```

Or use event-driven communication:

```php
// packages/Finance dispatches event, doesn't call Accounting
// packages/Finance/src/Events/EntryPostedEvent.php
<?php

declare(strict_types=1);

namespace Nexus\Finance\Events;

final class EntryPostedEvent
{
    public function __construct(
        public readonly string $entryId,
        public readonly string $tenantId,
        public readonly array $amounts
    ) {}
}

// packages/Accounting listens to event
// packages/Accounting/src/Listeners/EntryPostedListener.php
<?php

declare(strict_types=1);

namespace Nexus\Accounting\Listeners;

use Nexus\Finance\Events\EntryPostedEvent;

final readonly class EntryPostedListener
{
    public function __construct(
        private AccountingEntryRepositoryInterface $repository
    ) {}
    
    public function handle(EntryPostedEvent $event): void
    {
        // React to Finance event - no direct dependency
        $this->repository->createFromEntry($event->entryId);
    }
}
```

---

### 10.7 Generic Exception Usage

**❌ WRONG: Throwing generic exceptions**

```php
// packages/Tenant/src/Services/TenantManager.php
<?php

declare(strict_types=1);

namespace Nexus\Tenant\Services;

final class TenantManager
{
    public function switchTenant(string $tenantId): void
    {
        $tenant = $this->repository->find($tenantId);
        
        if ($tenant === null) {
            throw new \Exception("Tenant not found"); // ❌ Too generic!
        }
        
        if (!$tenant->isActive()) {
            throw new \Exception("Tenant is not active"); // ❌ Loses context!
        }
        
        if (!$tenant->hasFeature('multitenancy')) {
            throw new \RuntimeException("Feature not available"); // ❌ Wrong type!
        }
    }
}
```

**Problems:**
- Caller cannot catch specific failures
- No domain-specific error handling
- Stack traces don't indicate business context

---

**✅ CORRECT: Domain-specific exceptions**

```php
// packages/Tenant/src/Exceptions/TenantNotFoundException.php
<?php

declare(strict_types=1);

namespace Nexus\Tenant\Exceptions;

use RuntimeException;

final class TenantNotFoundException extends RuntimeException
{
    public function __construct(
        public readonly string $tenantId
    ) {
        parent::__construct("Tenant not found: {$tenantId}");
    }
}

// packages/Tenant/src/Exceptions/TenantInactiveException.php
<?php

declare(strict_types=1);

namespace Nexus\Tenant\Exceptions;

use RuntimeException;

final class TenantInactiveException extends RuntimeException
{
    public function __construct(
        public readonly string $tenantId,
        public readonly \DateTimeImmutable $inactiveSince
    ) {
        parent::__construct("Tenant {$tenantId} is inactive since {$inactiveSince->format('Y-m-d')}");
    }
}

// packages/Tenant/src/Exceptions/TenantFeatureNotAvailableException.php
<?php

declare(strict_types=1);

namespace Nexus\Tenant\Exceptions;

use RuntimeException;

final class TenantFeatureNotAvailableException extends RuntimeException
{
    public function __construct(
        public readonly string $tenantId,
        public readonly string $feature
    ) {
        parent::__construct("Feature '{$feature}' is not available for tenant {$tenantId}");
    }
}

// Usage
// packages/Tenant/src/Services/TenantManager.php
<?php

declare(strict_types=1);

namespace Nexus\Tenant\Services;

use Nexus\Tenant\Exceptions\TenantNotFoundException;
use Nexus\Tenant\Exceptions\TenantInactiveException;
use Nexus\Tenant\Exceptions\TenantFeatureNotAvailableException;

final readonly class TenantManager
{
    public function switchTenant(string $tenantId): void
    {
        $tenant = $this->repository->find($tenantId)
            ?? throw new TenantNotFoundException($tenantId);
        
        if (!$tenant->isActive()) {
            throw new TenantInactiveException($tenantId, $tenant->getInactiveSince());
        }
        
        if (!$tenant->hasFeature('multitenancy')) {
            throw new TenantFeatureNotAvailableException($tenantId, 'multitenancy');
        }
        
        // ...
    }
}
```

---

## Quick Reference Card

```
┌─────────────────────────────────────────────────────────────────────┐
│                    NEXUS ARCHITECTURE QUICK REF                    │
├─────────────────────────────────────────────────────────────────────┤
│ LAYER 1: ATOMIC PACKAGES                                            │
│   ✅ Pure PHP 8.3+, Framework-agnostic                             │
│   ✅ Define interfaces in Contracts/                                │
│   ✅ Own the "truth" of their domain                                │
│   ❌ NO framework code, NO database access                         │
│                                                                     │
│ LAYER 2: ORCHESTRATORS                                              │
│   ✅ Define OWN interfaces (NOT atomic package interfaces)         │
│   ✅ Pure PHP, coordinate multiple packages                        │
│   ✅ Use Coordinators, DataProviders, Rules, Workflows              │
│   ❌ NO direct atomic package imports                              │
│   ❌ NO framework code                                             │
│                                                                     │
│ LAYER 3: ADAPTERS                                                  │
│   ✅ Implement orchestrator interfaces                             │
│   ✅ Use atomic packages                                            │
│   ✅ Framework-specific code (Eloquent, controllers)               │
│   ✅ Bridge between orchestrator and atomic packages               │
├─────────────────────────────────────────────────────────────────────┤
│ KEY PRINCIPLES                                                      │
│   1. Orchestrators define their own interfaces                    │
│   2. Adapters implement orchestrator interfaces using atomic pkgs  │
│   3. Atomic packages are framework-agnostic                        │
│   4. Use dependency injection, never concrete classes            │
│   5. Split repositories into Query/Persist interfaces             │
│   6. Use domain-specific exceptions                                │
│   7. Keep services stateless (externalize state)                  │
└─────────────────────────────────────────────────────────────────────┘
```

---

**Last Updated:** 2026-02-19  
**Maintained By:** Nexus Architecture Team  
**Total Packages:** 54 (53 atomic + Common)  
**Total Orchestrators:** 8
