# Nexus\FinancialStatements

**Framework-Agnostic Financial Statement Generation Engine**

[![PHP Version](https://img.shields.io/badge/php-%5E8.3-blue)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-MIT-green)](LICENSE)

## Overview

`Nexus\FinancialStatements` is a pure PHP package that provides the core engine for generating, validating, and formatting financial statements. It supports multiple compliance frameworks (GAAP, IFRS, local standards) and produces Balance Sheets, Income Statements, Cash Flow Statements, Statements of Changes in Equity, and Notes to Financial Statements.

This package is **framework-agnostic** and contains no database access, no HTTP controllers, and no framework-specific code. Consuming applications provide data through injected interfaces.

## Installation

```bash
composer require nexus/financial-statements
```

## Package Responsibilities

| Responsibility | Description |
|----------------|-------------|
| **Statement Generation** | Build complete financial statements from account balances |
| **Multi-Framework Compliance** | Support GAAP, IFRS, and custom compliance layouts |
| **Section Organization** | Group accounts into proper statement sections (Assets, Liabilities, etc.) |
| **Validation** | Ensure statements balance and meet compliance requirements |
| **Export Adapters** | Define contracts for PDF, Excel, HTML export (implementations in adapters) |

## Key Concepts

### Statement Types Supported

- **Balance Sheet** (Statement of Financial Position)
- **Income Statement** (Profit & Loss)
- **Cash Flow Statement** (Direct & Indirect methods)
- **Statement of Changes in Equity**
- **Notes to Financial Statements**
- **Trial Balance**

### Compliance Frameworks

The package supports multiple compliance frameworks through layout templates:

- **GAAP** (US Generally Accepted Accounting Principles)
- **IFRS** (International Financial Reporting Standards)
- **Custom** (Tenant-defined layouts)

---

## Architecture

```
src/
├── Contracts/           # Interfaces defining the public API
├── Entities/            # Statement domain objects
├── ValueObjects/        # Immutable data structures
├── Enums/               # Statement types, formats, categories
├── Services/            # Business logic (validation, grouping)
├── Layouts/             # Compliance-specific statement layouts
└── Exceptions/          # Domain-specific errors
```

---

## Contracts (Interfaces)

### Core Interfaces

#### `FinancialStatementInterface`

Base interface for all financial statement entities.

```php
interface FinancialStatementInterface
{
    public function getId(): string;
    public function getTenantId(): string;
    public function getPeriodId(): string;
    public function getType(): StatementType;
    public function getGeneratedAt(): \DateTimeImmutable;
    public function getSections(): array;
    public function getMetadata(): StatementMetadata;
}
```

#### `StatementBuilderInterface`

Builds financial statements from raw account data.

```php
interface StatementBuilderInterface
{
    /**
     * Build a financial statement of the specified type
     *
     * @param StatementType $type The type of statement to build
     * @param array<AccountBalance> $balances Account balances to include
     * @param StatementPeriod $period The reporting period
     * @param ComplianceFramework $framework Compliance framework to use
     * @return FinancialStatementInterface
     */
    public function build(
        StatementType $type,
        array $balances,
        StatementPeriod $period,
        ComplianceFramework $framework
    ): FinancialStatementInterface;
}
```

#### `StatementTemplateInterface`

Defines the structure and layout of a statement type.

```php
interface StatementTemplateInterface
{
    public function getStatementType(): StatementType;
    public function getFramework(): ComplianceFramework;
    public function getSectionDefinitions(): array;
    public function getLineItemOrder(): array;
    public function getSubtotalRules(): array;
}
```

#### `StatementValidatorInterface`

Validates statement integrity and compliance.

```php
interface StatementValidatorInterface
{
    /**
     * Validate a financial statement
     *
     * @param FinancialStatementInterface $statement
     * @return ValidationResult
     * @throws StatementImbalanceException
     */
    public function validate(FinancialStatementInterface $statement): ValidationResult;
    
    /**
     * Check if statement meets compliance requirements
     */
    public function checkCompliance(
        FinancialStatementInterface $statement,
        ComplianceFramework $framework
    ): ComplianceCheckResult;
}
```

#### `ComplianceTemplateInterface`

Provides compliance-specific formatting rules.

```php
interface ComplianceTemplateInterface
{
    public function getFramework(): ComplianceFramework;
    public function getRequiredSections(): array;
    public function getRequiredDisclosures(): array;
    public function getAccountMappings(): array;
}
```

#### `StatementDataProviderInterface`

Contract for consuming applications to provide account data.

```php
interface StatementDataProviderInterface
{
    /**
     * Get account balances for a period
     *
     * @return array<AccountBalance>
     */
    public function getAccountBalances(
        string $tenantId,
        string $periodId,
        \DateTimeImmutable $asOfDate
    ): array;
    
    /**
     * Get comparative period balances
     */
    public function getComparativeBalances(
        string $tenantId,
        string $periodId,
        int $periodsBack = 1
    ): array;
}
```

#### `StatementExportAdapterInterface`

Contract for export implementations (PDF, Excel, etc.).

```php
interface StatementExportAdapterInterface
{
    public function export(
        FinancialStatementInterface $statement,
        StatementFormat $format,
        array $options = []
    ): ExportResult;
    
    public function supportsFormat(StatementFormat $format): bool;
}
```

---

## Entities

### `BalanceSheet`

Represents a Statement of Financial Position.

- Assets = Liabilities + Equity (must balance)
- Supports current/non-current classification
- Comparative period support

### `IncomeStatement`

Represents a Profit & Loss statement.

- Revenue - Expenses = Net Income
- Supports single-step and multi-step formats
- Gross profit calculation

### `CashFlowStatement`

Represents a Statement of Cash Flows.

- Operating, Investing, Financing activities
- Supports Direct and Indirect methods
- Beginning + Net Change = Ending Cash

### `StatementOfChangesInEquity`

Tracks equity movements over a period.

- Opening equity balances
- Comprehensive income
- Dividends and distributions
- Share transactions

### `NotesToFinancialStatements`

Supplementary disclosures and accounting policies.

### `TrialBalance`

Pre-statement verification of account balances.

- Debits must equal Credits
- Used for period-end verification

### `StatementSection`

Groups related line items within a statement.

- Section name and order
- Subtotal calculations
- Nested sub-sections support

---

## Value Objects

### `LineItem`

```php
final readonly class LineItem
{
    public function __construct(
        public string $accountId,
        public string $accountName,
        public string $accountCode,
        public Money $amount,
        public Money $comparativeAmount,
        public int $displayOrder,
        public bool $isSubtotal = false
    ) {}
}
```

### `AccountBalance`

```php
final readonly class AccountBalance
{
    public function __construct(
        public string $accountId,
        public string $accountCode,
        public string $accountName,
        public AccountCategory $category,
        public Money $debitBalance,
        public Money $creditBalance,
        public Money $netBalance
    ) {}
}
```

### `StatementMetadata`

```php
final readonly class StatementMetadata
{
    public function __construct(
        public string $preparedBy,
        public \DateTimeImmutable $preparedAt,
        public ?string $approvedBy,
        public ?\DateTimeImmutable $approvedAt,
        public ComplianceFramework $framework,
        public string $currency,
        public array $notes = []
    ) {}
}
```

### `StatementPeriod`

```php
final readonly class StatementPeriod
{
    public function __construct(
        public string $periodId,
        public \DateTimeImmutable $startDate,
        public \DateTimeImmutable $endDate,
        public string $periodName,
        public bool $isClosed = false
    ) {}
}
```

### `ComplianceStandard`

```php
final readonly class ComplianceStandard
{
    public function __construct(
        public ComplianceFramework $framework,
        public string $version,
        public \DateTimeImmutable $effectiveDate,
        public array $requirements = []
    ) {}
}
```

---

## Enums

### `StatementType`

```php
enum StatementType: string
{
    case BALANCE_SHEET = 'balance_sheet';
    case INCOME_STATEMENT = 'income_statement';
    case CASH_FLOW = 'cash_flow';
    case CHANGES_IN_EQUITY = 'changes_in_equity';
    case NOTES = 'notes';
    case TRIAL_BALANCE = 'trial_balance';
}
```

### `StatementFormat`

```php
enum StatementFormat: string
{
    case PDF = 'pdf';
    case EXCEL = 'excel';
    case HTML = 'html';
    case JSON = 'json';
    case CSV = 'csv';
}
```

### `AccountCategory`

```php
enum AccountCategory: string
{
    case ASSET = 'asset';
    case LIABILITY = 'liability';
    case EQUITY = 'equity';
    case REVENUE = 'revenue';
    case EXPENSE = 'expense';
}
```

### `CashFlowMethod`

```php
enum CashFlowMethod: string
{
    case DIRECT = 'direct';
    case INDIRECT = 'indirect';
}
```

### `ComplianceFramework`

```php
enum ComplianceFramework: string
{
    case GAAP = 'gaap';
    case IFRS = 'ifrs';
    case LOCAL = 'local';
    case CUSTOM = 'custom';
}
```

---

## Services

### `StatementValidator`

Validates statement integrity:
- Balance Sheet: Assets = Liabilities + Equity
- Trial Balance: Debits = Credits
- Cash Flow: Beginning + Changes = Ending

### `SectionGrouper`

Groups account balances into statement sections based on account categories and template definitions.

### `ComplianceChecker`

Verifies statements meet compliance framework requirements:
- Required sections present
- Required disclosures included
- Proper account classifications

---

## Layouts

Pre-built compliance layouts:

- `GaapBalanceSheetLayout` - US GAAP balance sheet format
- `GaapIncomeStatementLayout` - US GAAP income statement format
- `IfrsBalanceSheetLayout` - IFRS balance sheet format
- `IfrsIncomeStatementLayout` - IFRS income statement format

---

## Exceptions

| Exception | When Thrown |
|-----------|-------------|
| `StatementImbalanceException` | Balance sheet doesn't balance (A ≠ L + E) |
| `InvalidLineItemException` | Line item data is invalid |
| `InvalidSectionException` | Section configuration is invalid |
| `TemplateNotFoundException` | Requested compliance template not found |

---

## Usage Example

```php
use Nexus\FinancialStatements\Contracts\StatementBuilderInterface;
use Nexus\FinancialStatements\Contracts\StatementDataProviderInterface;
use Nexus\FinancialStatements\Enums\StatementType;
use Nexus\FinancialStatements\Enums\ComplianceFramework;
use Nexus\FinancialStatements\ValueObjects\StatementPeriod;

final readonly class BalanceSheetGenerator
{
    public function __construct(
        private StatementBuilderInterface $builder,
        private StatementDataProviderInterface $dataProvider
    ) {}
    
    public function generate(string $tenantId, string $periodId): BalanceSheet
    {
        // Get account balances from consuming application
        $balances = $this->dataProvider->getAccountBalances(
            $tenantId,
            $periodId,
            new \DateTimeImmutable()
        );
        
        // Define the reporting period
        $period = new StatementPeriod(
            periodId: $periodId,
            startDate: new \DateTimeImmutable('2024-01-01'),
            endDate: new \DateTimeImmutable('2024-12-31'),
            periodName: 'FY 2024'
        );
        
        // Build the balance sheet
        return $this->builder->build(
            type: StatementType::BALANCE_SHEET,
            balances: $balances,
            period: $period,
            framework: ComplianceFramework::IFRS
        );
    }
}
```

---

## Integration with Other Packages

| Package | Integration |
|---------|-------------|
| `Nexus\Finance` | Provides account balances via `StatementDataProviderInterface` |
| `Nexus\Period` | Provides period information |
| `Nexus\AccountPeriodClose` | Triggers statement generation on period close |
| `Nexus\Export` | Implements `StatementExportAdapterInterface` |
| `Nexus\AuditLogger` | Logs statement generation events |

---

## Related Documentation

- [ARCHITECTURE.md](../../ARCHITECTURE.md) - Overall system architecture
- [CODING_GUIDELINES.md](../../CODING_GUIDELINES.md) - Coding standards
- [Nexus Packages Reference](../../docs/NEXUS_PACKAGES_REFERENCE.md) - All available packages

---

## License

MIT License - See [LICENSE](LICENSE) for details.
