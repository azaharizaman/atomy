# Nexus\Accounting

**Financial statement generation, period close, consolidation, and variance analysis for the Nexus ERP monorepo.**

---

## Table of Contents

- [Overview](#overview)
- [Features](#features)
- [Installation](#installation)
- [Architecture](#architecture)
- [Directory Structure](#directory-structure-ddd-layered-architecture)
- [Domain Layer Components](#domain-layer-components)
  - [Entities](#entities)
  - [Value Objects](#value-objects)
  - [Domain Events](#domain-events)
  - [Contracts (CQRS Repository Interfaces)](#contracts-cqrs-repository-interfaces)
  - [Domain Services](#domain-services)
  - [Policies](#policies)
  - [Exceptions](#exceptions)
- [Application Layer Components](#application-layer-components)
  - [DTOs (Data Transfer Objects)](#dtos-data-transfer-objects)
  - [Commands (Write Operations)](#commands-write-operations)
  - [Queries (Read Operations)](#queries-read-operations)
  - [Handlers](#handlers)
  - [Application Services](#application-services)
- [Infrastructure Layer Components](#infrastructure-layer-components)
  - [InMemory Repositories](#inmemory-repositories)
  - [Mappers](#mappers)
- [Architectural Rules](#architectural-rules)
- [Usage Examples](#usage-examples)
- [Required Dependencies](#required-dependencies)
- [Compliance Standards](#compliance-standards)
- [Export Formats](#export-formats)
- [Testing](#testing)
- [Contributing](#contributing)
- [License](#license)
- [Documentation](#-documentation)
- [Roadmap](#roadmap)
- [Support](#support)

---

## Overview

The Accounting package provides comprehensive financial reporting capabilities including balance sheet, income statement, and cash flow statement generation, along with period-end closing procedures, multi-entity consolidation, and budget variance analysis.

## Features

- **Financial Statement Generation**
  - Balance Sheet (Assets = Liabilities + Equity)
  - Income Statement (P&L with multi-level grouping)
  - Cash Flow Statement (Direct and Indirect methods)
  - Multi-period comparative statements

- **Period Close Operations**
  - Month-end close with validation
  - Year-end close with closing entries
  - Period reopening with audit trail
  - Trial balance verification

- **Consolidation Engine**
  - Full consolidation method
  - Proportional consolidation
  - Equity method
  - Intercompany eliminations
  - Non-controlling interest calculation

- **Variance Analysis**
  - Budget vs actual comparison
  - Significant variance filtering
  - Multi-period trend analysis
  - Variance summary reports

- **Compliance Support**
  - GAAP compliance templates
  - IFRS compliance templates
  - Custom compliance standards
  - Required disclosure tracking

## Installation

This package is part of the Nexus monorepo. Add it to your application's `composer.json`:

```bash
composer require nexus/accounting:"*@dev"
```

## Architecture

This package follows the **Nexus Monorepo Architecture**:

- **Pure PHP Logic**: No Laravel dependencies in the package
- **Contract-Driven**: All external dependencies defined as interfaces
- **Framework-Agnostic**: Can be used in any PHP application
- **Dependency Injection**: All services injected via constructor

## Directory Structure (DDD-Layered Architecture)

This package follows the **Complex Atomic Package** pattern with DDD layers:

```
src/
├── Domain/                 # THE TRUTH (Pure Business Logic)
│   ├── Contracts/          # Repository Interfaces (CQRS)
│   ├── Entities/           # Domain Entities
│   ├── ValueObjects/       # Immutable Value Objects
│   ├── Events/             # Domain Events
│   ├── Enums/              # Native PHP Enums
│   ├── Services/           # Domain Services
│   ├── Policies/           # Business Rules
│   └── Exceptions/         # Domain Exceptions
│
├── Application/            # THE USE CASES
│   ├── DTOs/               # Data Transfer Objects
│   ├── Commands/           # Write Operations
│   ├── Queries/            # Read Operations
│   ├── Handlers/           # Command/Query Handlers
│   └── Services/           # Application Services
│
└── Infrastructure/         # INTERNAL ADAPTERS
    ├── InMemory/           # In-memory repositories for testing
    └── Mappers/            # Domain-to-DTO mapping
```

---

## Domain Layer Components

> **Purpose:** Contains the core business logic, entities, value objects, events, contracts, services, and policies. This layer represents "THE TRUTH" - pure business logic with no framework dependencies.

### Entities

| Entity | Purpose | Key Properties | Status |
|--------|---------|----------------|--------|
| `FinancialStatement` | Base class for all financial statements | period, generatedAt, preparedBy | ✅ Completed |
| `BalanceSheet` | Assets = Liabilities + Equity statement | assets, liabilities, equity, sections | ✅ Completed |
| `IncomeStatement` | Revenue - Expenses = Net Income | revenue, expenses, netIncome, sections | ✅ Completed |
| `CashFlowStatement` | Operating, investing, financing activities | operatingCash, investingCash, financingCash | ✅ Completed |
| `TrialBalance` | Debit/Credit balance verification | accounts, totalDebits, totalCredits | 📋 Planned |
| `StatementSection` | Grouping of line items | name, lineItems, subtotal | ✅ Completed |
| `StatementLineItem` | Individual line in statement | accountCode, description, amount | ✅ Completed |

### Value Objects

| Value Object | Purpose | Properties | Status |
|--------------|---------|------------|--------|
| `Period` | Fiscal period representation | startDate, endDate, periodType | 📋 Planned |
| `PeriodType` | Enum for period types | MONTHLY, QUARTERLY, ANNUAL, CUSTOM | 📋 Planned |
| `Money` | Monetary value with currency | amount, currency | 📋 Planned |
| `AccountBalance` | Account with balance | accountId, accountCode, accountName, debit, credit, balance | 📋 Planned |
| `StatementMetadata` | Statement generation info | version, approvedBy, approvedAt, notes | 📋 Planned |
| `ConsolidationEntity` | Entity in consolidation | entityId, name, currency, ownershipPercentage | 📋 Planned |
| `ReportingPeriod` | Reporting period with comparatives | startDate, endDate, comparativeStartDate | ✅ Completed |
| `StatementSection` | Section grouping in statement | name, lineItems, subtotal, displayOrder | ✅ Completed |
| `LineItem` | Individual line item | accountCode, description, amount, indent | ✅ Completed |

### Domain Events

| Event | Trigger | Purpose | Status |
|-------|---------|---------|--------|
| `FinancialStatementGeneratedEvent` | Statement created | Notify subscribers of new statement | 📋 Planned |
| `PeriodClosedEvent` | Period closed | Trigger end-of-period processes | 📋 Planned |
| `ConsolidationCompletedEvent` | Consolidation done | Notify completion of multi-entity consolidation | 📋 Planned |
| `VarianceDetectedEvent` | Budget variance | Alert on significant variance | 📋 Planned |

### Contracts (CQRS Repository Interfaces)

| Interface | Purpose | Status |
|-----------|---------|--------|
| `AccountingPeriodQueryInterface` | Read accounting period data | 📋 Planned |
| `AccountingPeriodPersistInterface` | Write accounting period data | 📋 Planned |
| `FinancialStatementQueryInterface` | Query financial statements | 📋 Planned |
| `FinancialStatementPersistInterface` | Persist financial statements | 📋 Planned |
| `ConsolidationQueryInterface` | Query consolidation data | 📋 Planned |
| `ConsolidationPersistInterface` | Persist consolidation data | 📋 Planned |
| `FinancialStatementInterface` | Financial statement entity contract | ✅ Completed |
| `AccountInterface` | Account entity contract | ✅ Completed |

### Domain Services

| Service | Purpose | Status |
|---------|---------|--------|
| `BalanceSheetGenerator` | Generate balance sheet from trial balance | 📋 Planned |
| `IncomeStatementGenerator` | Generate income statement | 📋 Planned |
| `CashFlowStatementGenerator` | Generate cash flow statement | 📋 Planned |
| `TrialBalanceCalculator` | Calculate trial balance from GL | 📋 Planned |
| `FinancialRatioCalculator` | Calculate financial ratios | 📋 Planned |
| `IntercompanyEliminator` | Eliminate intercompany transactions | 📋 Planned |

### Policies

| Policy | Purpose | Status |
|--------|---------|--------|
| `PeriodClosePolicy` | Rules for closing accounting periods | 📋 Planned |
| `StatementApprovalPolicy` | Rules for statement approval workflow | 📋 Planned |
| `ConsolidationPolicy` | Rules for multi-entity consolidation | 📋 Planned |

### Exceptions

| Exception | Purpose | Status |
|-----------|---------|--------|
| `PeriodNotClosedException` | Thrown when period is not closed | 📋 Planned |
| `StatementGenerationException` | Thrown during statement generation errors | 📋 Planned |
| `ConsolidationException` | Thrown during consolidation errors | 📋 Planned |
| `ComplianceViolationException` | Thrown for compliance violations | 📋 Planned |
| `InvalidReportingPeriodException` | Thrown for invalid period data | 📋 Planned |
| `StatementVersionConflictException` | Thrown for version conflicts | 📋 Planned |

---

## Application Layer Components

> **Purpose:** Contains the use cases, DTOs, commands, queries, and handlers that orchestrate the Domain layer. This layer represents "THE USE CASES" - application-specific business logic that coordinates domain operations.

### DTOs (Data Transfer Objects)

| DTO | Purpose | Properties | Status |
|-----|---------|------------|--------|
| `BalanceSheetDTO` | Balance sheet data transfer | sections, totals, metadata | 📋 Planned |
| `IncomeStatementDTO` | Income statement data transfer | revenue, expenses, netIncome | 📋 Planned |
| `CashFlowStatementDTO` | Cash flow data transfer | operating, investing, financing | 📋 Planned |
| `PeriodCloseRequestDTO` | Period close request data | periodId, userId, options | 📋 Planned |
| `ConsolidationRequestDTO` | Consolidation request data | entityIds, method, options | 📋 Planned |

### Commands (Write Operations)

| Command | Purpose | Status |
|---------|---------|--------|
| `GenerateBalanceSheetCommand` | Generate a new balance sheet | 📋 Planned |
| `GenerateIncomeStatementCommand` | Generate a new income statement | 📋 Planned |
| `GenerateCashFlowStatementCommand` | Generate a new cash flow statement | 📋 Planned |
| `ClosePeriodCommand` | Close an accounting period | 📋 Planned |
| `ReopenPeriodCommand` | Reopen a closed period | 📋 Planned |
| `ConsolidateStatementsCommand` | Consolidate multi-entity statements | 📋 Planned |

### Queries (Read Operations)

| Query | Purpose | Status |
|-------|---------|--------|
| `GetBalanceSheetQuery` | Retrieve a balance sheet | 📋 Planned |
| `GetIncomeStatementQuery` | Retrieve an income statement | 📋 Planned |
| `GetCashFlowStatementQuery` | Retrieve a cash flow statement | 📋 Planned |
| `GetTrialBalanceQuery` | Retrieve trial balance data | 📋 Planned |
| `GetVarianceReportQuery` | Retrieve variance analysis | 📋 Planned |
| `GetConsolidatedStatementQuery` | Retrieve consolidated statement | 📋 Planned |

### Handlers

| Handler | Purpose | Status |
|---------|---------|--------|
| `GenerateBalanceSheetHandler` | Handles balance sheet generation command | 📋 Planned |
| `GenerateIncomeStatementHandler` | Handles income statement generation command | 📋 Planned |
| `ClosePeriodHandler` | Handles period close command | 📋 Planned |
| `ConsolidateStatementsHandler` | Handles consolidation command | 📋 Planned |

### Application Services

| Service | Purpose | Status |
|---------|---------|--------|
| `AccountingManager` | Main entry point for accounting operations | 📋 Planned |
| `StatementCoordinator` | Coordinates statement generation workflow | 📋 Planned |
| `PeriodCloseCoordinator` | Coordinates period close workflow | 📋 Planned |

---

## Infrastructure Layer Components

> **Purpose:** Contains internal adapters for testing and mapping between domain objects and external representations. This layer provides INTERNAL ADAPTERS - implementations used for testing and data transformation within the package.

### InMemory Repositories

| Repository | Purpose | Implements | Status |
|------------|---------|------------|--------|
| `InMemoryFinancialStatementRepository` | Testing financial statement persistence | `FinancialStatementQueryInterface`, `FinancialStatementPersistInterface` | 📋 Planned |
| `InMemoryAccountingPeriodRepository` | Testing period management | `AccountingPeriodQueryInterface`, `AccountingPeriodPersistInterface` | 📋 Planned |
| `InMemoryConsolidationRepository` | Testing consolidation data | `ConsolidationQueryInterface`, `ConsolidationPersistInterface` | 📋 Planned |

### Mappers

| Mapper | Purpose | Status |
|--------|---------|--------|
| `BalanceSheetMapper` | Maps BalanceSheet entity to BalanceSheetDTO | 📋 Planned |
| `IncomeStatementMapper` | Maps IncomeStatement entity to IncomeStatementDTO | 📋 Planned |
| `CashFlowStatementMapper` | Maps CashFlowStatement entity to CashFlowStatementDTO | 📋 Planned |
| `PeriodMapper` | Maps Period value object to array/JSON | 📋 Planned |
| `StatementMetadataMapper` | Maps metadata to/from external formats | 📋 Planned |

**InMemory Repositories are provided for:**

1. **Unit Testing:** Fast, isolated tests without database dependencies
2. **Integration Testing:** Test application layer without external infrastructure
3. **Development:** Quick prototyping without database setup
4. **Documentation:** Reference implementations of repository contracts

**Mappers are used for:**

1. **DTO Conversion:** Transform domain entities to DTOs for API responses
2. **Serialization:** Prepare domain objects for JSON/array serialization
3. **Deserialization:** Reconstruct domain objects from external data
4. **View Models:** Create presentation-specific representations

> **Note on External Adapters:** Database adapters (Eloquent, Doctrine) belong in the `adapters/` folder at the monorepo root, NOT in this Infrastructure layer.

---

## Architectural Rules

### Domain Layer Rules
1. **No Framework Dependencies:** The Domain layer MUST NOT use any framework-specific code
2. **Pure PHP 8.3+:** Use modern PHP features (readonly, enums, match expressions)
3. **Immutable Entities:** All entities should be immutable where possible
4. **Interface-Driven:** All external dependencies defined via interfaces
5. **Event-Driven:** State changes emit domain events
6. **CQRS Separation:** Read (Query) and Write (Persist) interfaces are separated

### Application Layer Rules
1. **Orchestration Only:** Application layer coordinates Domain layer, never bypasses it
2. **DTOs for Data Transfer:** Use DTOs to transfer data between layers
3. **Command/Query Separation:** Commands change state, Queries read state (CQRS)
4. **Handler Pattern:** Each command/query has a dedicated handler
5. **No Direct Persistence:** Use Domain repositories via dependency injection

### Infrastructure Layer Rules
1. **Internal Use Only:** This layer is for internal package use, not public API
2. **Test Support:** InMemory repositories are primarily for testing
3. **No Business Logic:** Mappers only transform data, no business rules
4. **Implement Domain Contracts:** InMemory repos implement Domain contracts exactly
5. **Stateless Mappers:** All mappers are stateless, pure transformations

## Usage Examples

### Generate a Balance Sheet

```php
use Nexus\Accounting\Domain\ValueObjects\Period;
use Nexus\Accounting\Domain\Contracts\FinancialStatementQueryInterface;
use Nexus\Accounting\Domain\Services\BalanceSheetGenerator;

$period = Period::forMonth(2025, 11);

$balanceSheet = $balanceSheetGenerator->generate(
    entityId: 'entity-123',
    period: $period,
    options: ['include_comparatives' => true]
);

echo "Total Assets: " . $balanceSheet->getTotalAssets();
echo "Total Equity: " . $balanceSheet->getTotalEquity();
echo "Balanced: " . ($balanceSheet->verifyBalance() ? 'Yes' : 'No');
```

### Close a Month-End Period

```php
use Nexus\Accounting\Domain\Services\PeriodCloseService;
use Nexus\Accounting\Domain\Policies\PeriodClosePolicy;

// Validate readiness using policy
$validation = $periodClosePolicy->validateReadiness('period-202511');

if ($validation['ready']) {
    $periodCloseService->closeMonth('period-202511');
} else {
    print_r($validation['issues']);
}
```

### Calculate Budget Variance

```php
use Nexus\Accounting\Domain\Services\VarianceCalculator;

$variance = $varianceCalculator->calculateAccountVariance(
    accountId: 'account-4000',
    period: $period
);

echo "Actual: " . $variance->getActualAmount();
echo "Budget: " . $variance->getBudgetAmount();
echo "Variance: " . $variance->formatVariance();
echo "Status: " . $variance->getStatus(isRevenueAccount: true);
```

### Consolidate Multi-Entity Statements

```php
use Nexus\Accounting\Domain\Enums\ConsolidationMethod;
use Nexus\Accounting\Domain\Services\ConsolidationEngine;

$consolidated = $consolidationEngine->consolidateStatements(
    entityIds: ['parent-1', 'subsidiary-1', 'subsidiary-2'],
    period: $period,
    method: ConsolidationMethod::FULL,
    options: ['calculate_nci' => true]
);

echo "Consolidated Total Assets: " . $consolidated->getTotalAssets();
```

## Required Dependencies

The Accounting package requires these contracts to be implemented by the consuming application:

### From `Nexus\Finance`
- `LedgerRepositoryInterface` - Read GL data, account balances
- `JournalEntryServiceInterface` - Create closing entries

### From `Nexus\Period`
- `PeriodManagerInterface` - Fiscal period validation, locking

### From `Nexus\Analytics`
- `BudgetRepositoryInterface` - Budget data for variance analysis

### From `Nexus\Setting`
- `SettingsManagerInterface` - Report templates, precision config

### From `Nexus\AuditLogger`
- `AuditLoggerInterface` - Log all operations

### PSR Standards
- `Psr\Log\LoggerInterface` - Logging (PSR-3)

## Compliance Standards

The package supports multiple accounting standards:

```php
use Nexus\Accounting\Domain\ValueObjects\ComplianceStandard;

$usGaap = ComplianceStandard::usGAAP('2024');
$ifrs = ComplianceStandard::ifrs('2024');
$custom = ComplianceStandard::custom('Malaysian FRS', '2024', 'MY');
```

## Export Formats

Financial statements can be exported to multiple formats:

```php
use Nexus\Accounting\Domain\Enums\StatementFormat;

$format = StatementFormat::PDF;      // application/pdf
$format = StatementFormat::EXCEL;    // .xlsx spreadsheet
$format = StatementFormat::CSV;      // Plain text CSV
$format = StatementFormat::JSON;     // JSON for APIs
```

## Testing

(Test suite to be implemented in Phase 5)

```bash
composer test
```

## Contributing

This package follows strict architectural guidelines:

1. **No Laravel dependencies** - Keep the package framework-agnostic
2. **Contract-driven design** - Define interfaces for all external needs
3. **Immutable value objects** - Use `readonly` properties
4. **Modern PHP 8.3+** - Use native enums, constructor promotion, match expressions
5. **Comprehensive exceptions** - Provide clear error messages

## License

MIT License. See [LICENSE](LICENSE) for details.

## 📖 Documentation

### Package Documentation
- **[Getting Started Guide](docs/getting-started.md)** - Quick start guide with prerequisites, concepts, and first integration
- **[API Reference](docs/api-reference.md)** - Complete documentation of all interfaces, value objects, and exceptions
- **[Integration Guide](docs/integration-guide.md)** - Laravel and Symfony integration examples
- **[Basic Usage Example](docs/examples/basic-usage.php)** - Simple usage patterns
- **[Advanced Usage Example](docs/examples/advanced-usage.php)** - Advanced scenarios and patterns

### Additional Resources
- `IMPLEMENTATION_SUMMARY.md` - Implementation progress and metrics
- `REQUIREMENTS.md` - Detailed requirements (139 requirements)
- `TEST_SUITE_SUMMARY.md` - Test coverage and results
- `VALUATION_MATRIX.md` - Package valuation metrics ($350K+ value)
- See root `ARCHITECTURE.md` for overall system architecture

---

## Roadmap

- [x] Phase 1: Foundation Layer (Domain Contracts, Value Objects, Enums, Exceptions)
- [x] Phase 2: Core Engines (Statement Generators, Period Close, Consolidation, Variance)
- [x] Phase 3: DDD Refactoring (Domain/Application/Infrastructure layers)
- [ ] Phase 4: Application Layer (DTOs, Commands, Queries, Handlers)
- [ ] Phase 5: Test Suite (185+ tests planned - December 2024)

## Support

For questions or issues, please refer to the main Nexus monorepo documentation.
