# API Reference: Statutory

Complete documentation for all public interfaces, services, value objects, and exceptions in the Nexus\Statutory package.

---

## Table of Contents

1. [Interfaces](#interfaces)
   - [TaxonomyReportGeneratorInterface](#taxonomyreportgeneratorinterface)
   - [PayrollStatutoryInterface](#payrollstatutoryinterface)
   - [ReportMetadataInterface](#reportmetadatainterface)
   - [StatutoryReportInterface](#statutoryreportinterface)
   - [StatutoryReportRepositoryInterface](#statutoryreportrepositoryinterface)
2. [Services](#services)
   - [StatutoryReportManager](#statutoryreportmanager)
3. [Value Objects](#value-objects)
   - [FilingFrequency](#filingfrequency)
   - [ReportFormat](#reportformat)
4. [Exceptions](#exceptions)

---

## Interfaces

### TaxonomyReportGeneratorInterface

**Location:** `src/Contracts/TaxonomyReportGeneratorInterface.php`

**Purpose:** Contract for generating statutory reports with taxonomy tags (e.g., XBRL for SSM, LHDN).

**Methods:**

#### generateReport()

```php
public function generateReport(
    string $tenantId,
    string $reportType,
    \DateTimeImmutable $startDate,
    \DateTimeImmutable $endDate,
    ReportFormat $format,
    array $options = []
): array;
```

**Description:** Generates a statutory report in the specified format with taxonomy tags.

**Parameters:**
- `$tenantId` (string) - Tenant identifier for multi-tenancy
- `$reportType` (string) - Type of report (`profit_loss`, `balance_sheet`, `trial_balance`)
- `$startDate` (DateTimeImmutable) - Report start date
- `$endDate` (DateTimeImmutable) - Report end date
- `$format` (ReportFormat) - Output format (XBRL, PDF, CSV, JSON)
- `$options` (array) - Optional parameters (`include_details`, `taxonomy_version`)

**Returns:** `array` - Generated report data structure

**Throws:**
- `ValidationException` - Schema validation failed
- `DataExtractionException` - Failed to extract financial data
- `InvalidReportTypeException` - Invalid report type requested

**Example:**
```php
$report = $generator->generateReport(
    tenantId: 'tenant-123',
    reportType: 'profit_loss',
    startDate: new \DateTimeImmutable('2025-01-01'),
    endDate: new \DateTimeImmutable('2025-12-31'),
    format: ReportFormat::XBRL,
    options: ['taxonomy_version' => 'v1.2.0']
);
```

---

#### getReportMetadata()

```php
public function getReportMetadata(string $reportType): ReportMetadataInterface;
```

**Description:** Returns metadata for a specific report type (schema ID, version, validation rules).

**Parameters:**
- `$reportType` (string) - Type of report

**Returns:** `ReportMetadataInterface` - Report metadata

**Example:**
```php
$metadata = $generator->getReportMetadata('profit_loss');
echo $metadata->getSchemaIdentifier(); // "SSM-FS-2023"
```

---

### PayrollStatutoryInterface

**Location:** `src/Contracts/PayrollStatutoryInterface.php`

**Purpose:** Contract for payroll statutory calculations (EPF, SOCSO, PCB, etc.).

**Methods:**

#### calculateDeductions()

```php
public function calculateDeductions(
    string $tenantId,
    string $employeeId,
    float $grossSalary,
    \DateTimeImmutable $payDate,
    array $employeeData = []
): array;
```

**Description:** Calculates statutory deductions for payroll.

**Parameters:**
- `$tenantId` (string) - Tenant identifier
- `$employeeId` (string) - Employee identifier
- `$grossSalary` (float) - Gross salary amount
- `$payDate` (DateTimeImmutable) - Payment date
- `$employeeData` (array) - Additional employee data (citizenship, tax exemption, etc.)

**Returns:** `array` - Deduction breakdown
```php
[
    'epf_employee' => 550.00,
    'epf_employer' => 600.00,
    'socso_employee' => 24.50,
    'socso_employer' => 56.50,
    'pcb' => 300.00,
    // ... other deductions
]
```

**Throws:**
- `CalculationException` - Calculation error
- `InvalidDeductionTypeException` - Invalid deduction type

---

#### getCountryCode()

```php
public function getCountryCode(): string;
```

**Description:** Returns the ISO country code for this adapter.

**Returns:** `string` - ISO 3166-1 alpha-3 country code (e.g., "MYS", "SGP")

---

### ReportMetadataInterface

**Location:** `src/Contracts/ReportMetadataInterface.php`

**Purpose:** Defines metadata for statutory reports (schema, validation, filing requirements).

**Methods:**

#### getSchemaIdentifier()

```php
public function getSchemaIdentifier(): string;
```

**Returns:** `string` - Schema identifier (e.g., "SSM-FS-2023", "LHDN-PCB-2024")

---

#### getSchemaVersion()

```php
public function getSchemaVersion(): string;
```

**Returns:** `string` - Schema version (e.g., "v1.2.0")

---

#### getMappingTemplate()

```php
public function getMappingTemplate(): array;
```

**Returns:** `array` - Taxonomy mapping template (list of valid tags)

**Example:**
```php
[
    'Assets.CurrentAssets.CashAndCashEquivalents',
    'Assets.CurrentAssets.TradeReceivables',
    'Liabilities.CurrentLiabilities.TradePayables',
    // ... more tags
]
```

---

#### getSubmissionFrequency()

```php
public function getSubmissionFrequency(): FilingFrequency;
```

**Returns:** `FilingFrequency` - Filing frequency enum

---

#### getReportingBody()

```php
public function getReportingBody(): string;
```

**Returns:** `string` - Reporting authority (e.g., "SSM", "LHDN", "KWSP")

---

#### getRecipientSystemURL()

```php
public function getRecipientSystemURL(): ?string;
```

**Returns:** `string|null` - Government portal URL for submission

---

#### getOutputFormat()

```php
public function getOutputFormat(): ReportFormat;
```

**Returns:** `ReportFormat` - Supported output format

---

#### getMimeType()

```php
public function getMimeType(): string;
```

**Returns:** `string` - MIME type (e.g., "application/xbrl+xml")

---

#### getValidationRules()

```php
public function getValidationRules(): array;
```

**Returns:** `array` - Schema validation rules

---

### StatutoryReportInterface

**Location:** `src/Contracts/StatutoryReportInterface.php`

**Purpose:** Entity interface for statutory reports.

**Methods:**

- `getId(): string`
- `getTenantId(): string`
- `getReportType(): string`
- `getStartDate(): \DateTimeImmutable`
- `getEndDate(): \DateTimeImmutable`
- `getFormat(): string`
- `getStatus(): string`
- `getFilePath(): ?string`
- `getMetadata(): array`

---

### StatutoryReportRepositoryInterface

**Location:** `src/Contracts/StatutoryReportRepositoryInterface.php`

**Purpose:** Repository interface for persisting statutory reports.

**Methods:**

- `findById(string $id): ?StatutoryReportInterface`
- `save(StatutoryReportInterface $report): void`
- `findByTenant(string $tenantId, ?string $reportType = null): array`

---

## Services

### StatutoryReportManager

**Location:** `src/Services/StatutoryReportManager.php`

**Purpose:** Main orchestrator for statutory report generation, validation, and management.

**Constructor Dependencies:**
- `TaxonomyReportGeneratorInterface` - Report generator adapter
- `StatutoryReportRepositoryInterface` - Repository for persistence
- `LoggerInterface` - PSR-3 logger

**Public Methods:**

#### generateReport()

```php
public function generateReport(
    string $tenantId,
    string $reportType,
    \DateTimeImmutable $startDate,
    \DateTimeImmutable $endDate,
    ReportFormat $format,
    array $options = []
): string;
```

**Returns:** `string` - Report ID (ULID)

---

#### validateReport()

```php
public function validateReport(string $reportId): array;
```

**Returns:** `array` - Validation results
```php
[
    'valid' => true,
    'errors' => [],
    'warnings' => []
]
```

---

#### getReport()

```php
public function getReport(string $reportId): StatutoryReportInterface;
```

**Returns:** `StatutoryReportInterface` - Report entity

**Throws:** `ReportNotFoundException`

---

## Value Objects

### FilingFrequency

**Location:** `src/ValueObjects/FilingFrequency.php`

**Purpose:** Enum for filing frequency.

**Cases:**
- `MONTHLY` - 12 filings per year
- `QUARTERLY` - 4 filings per year
- `SEMI_ANNUALLY` - 2 filings per year
- `ANNUALLY` - 1 filing per year
- `BIENNIAL` - Every 2 years
- `ON_DEMAND` - No scheduled filing

**Methods:**
- `getMonthInterval(): int`
- `getFilingsPerYear(): ?int`
- `isScheduled(): bool`

---

### ReportFormat

**Location:** `src/ValueObjects/ReportFormat.php`

**Purpose:** Enum for report output formats.

**Cases:**
- `JSON` - application/json
- `XML` - application/xml
- `XBRL` - application/xbrl+xml
- `CSV` - text/csv
- `PDF` - application/pdf
- `EXCEL` - application/vnd.openxmlformats-officedocument.spreadsheetml.sheet

**Methods:**
- `getMimeType(): string`
- `getFileExtension(): string`
- `isMachineReadable(): bool`
- `supportsDigitalSignature(): bool`

---

## Exceptions

All exceptions extend PHP's base `Exception` class and are located in `src/Exceptions/`.

1. **ValidationException** - Schema/data validation errors
2. **DataExtractionException** - Financial data extraction failures
3. **CalculationException** - Statutory calculation errors
4. **InvalidReportTypeException** - Invalid report type
5. **InvalidDeductionTypeException** - Invalid payroll deduction type
6. **ReportNotFoundException** - Report not found

**Factory Methods Example:**
```php
throw ValidationException::missingMandatoryTag('Assets.CurrentAssets.Cash');
throw DataExtractionException::failedToExtract('trial_balance', 'Connection timeout');
```

---

**For implementation examples, see:**
- [Getting Started](getting-started.md)
- [Integration Guide](integration-guide.md)
- [Basic Usage Example](examples/basic-usage.php)
- [Advanced Usage Example](examples/advanced-usage.php)
