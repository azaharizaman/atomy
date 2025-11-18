# Nexus\Accounting

**Financial statement generation, period close, consolidation, and variance analysis for the Nexus ERP monorepo.**

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

## Directory Structure

```
src/
├── Contracts/              # 10 Interfaces
│   ├── FinancialStatementInterface.php
│   ├── BalanceSheetInterface.php
│   ├── IncomeStatementInterface.php
│   ├── CashFlowStatementInterface.php
│   ├── StatementBuilderInterface.php
│   ├── PeriodCloseServiceInterface.php
│   ├── ConsolidationEngineInterface.php
│   ├── ComplianceTemplateInterface.php
│   ├── StatementRepositoryInterface.php
│   └── ReportFormatterInterface.php
├── Core/
│   ├── ValueObjects/       # 8 Immutable objects
│   │   ├── ReportingPeriod.php
│   │   ├── StatementLineItem.php
│   │   ├── ComplianceStandard.php
│   │   ├── StatementSection.php
│   │   ├── StatementFormat.php
│   │   ├── ConsolidationRule.php
│   │   ├── VarianceAnalysis.php
│   │   └── SegmentIdentifier.php
│   ├── Enums/              # 4 Native PHP enums
│   │   ├── StatementType.php
│   │   ├── PeriodCloseStatus.php
│   │   ├── ConsolidationMethod.php
│   │   └── CashFlowMethod.php
│   └── Engine/             # 4 Core engines
│       ├── StatementBuilder.php
│       ├── PeriodCloseService.php
│       ├── ConsolidationEngine.php
│       ├── VarianceCalculator.php
│       └── Models/
│           ├── BalanceSheet.php
│           ├── IncomeStatement.php
│           └── CashFlowStatement.php
├── Exceptions/             # 6 Domain exceptions
│   ├── PeriodNotClosedException.php
│   ├── StatementGenerationException.php
│   ├── ConsolidationException.php
│   ├── ComplianceViolationException.php
│   ├── InvalidReportingPeriodException.php
│   └── StatementVersionConflictException.php
└── Services/               # (Phase 3 - Not yet implemented)
    └── AccountingManager.php
```

## Usage Examples

### Generate a Balance Sheet

```php
use Nexus\Accounting\Core\ValueObjects\ReportingPeriod;
use Nexus\Accounting\Contracts\StatementBuilderInterface;

$period = ReportingPeriod::forMonth(2025, 11);

$balanceSheet = $statementBuilder->buildBalanceSheet(
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
use Nexus\Accounting\Contracts\PeriodCloseServiceInterface;

// Validate readiness
$validation = $periodCloseService->validatePeriodReadiness('period-202511');

if ($validation['ready']) {
    $periodCloseService->closeMonth('period-202511');
} else {
    print_r($validation['issues']);
}
```

### Calculate Budget Variance

```php
use Nexus\Accounting\Core\Engine\VarianceCalculator;

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
use Nexus\Accounting\Core\Enums\ConsolidationMethod;

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
use Nexus\Accounting\Core\ValueObjects\ComplianceStandard;

$usGaap = ComplianceStandard::usGAAP('2024');
$ifrs = ComplianceStandard::ifrs('2024');
$custom = ComplianceStandard::custom('Malaysian FRS', '2024', 'MY');
```

## Export Formats

Financial statements can be exported to multiple formats:

```php
use Nexus\Accounting\Core\ValueObjects\StatementFormat;

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

## Roadmap

- [x] Phase 1: Foundation Layer (Contracts, Value Objects, Enums, Exceptions)
- [x] Phase 2: Core Engines (Statement Builder, Period Close, Consolidation, Variance)
- [ ] Phase 3: Service Layer (AccountingManager)
- [ ] Phase 4: Application Layer (Models, Migrations, Repositories)
- [ ] Phase 5: API Layer (Controllers, Routes)

## Support

For questions or issues, please refer to the main Nexus monorepo documentation.
