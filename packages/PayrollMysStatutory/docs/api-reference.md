# API Reference: Reporting

**Package:** `Nexus\Reporting`  
**Namespace:** `Nexus\Reporting`  
**Last Updated:** 2025-11-30

---

## Table of Contents

1. [Interfaces](#interfaces)
2. [Services](#services)
3. [Value Objects](#value-objects)
4. [Enums](#enums)
5. [Exceptions](#exceptions)
6. [Usage Patterns](#usage-patterns)

---

## Interfaces

### ReportDefinitionInterface

**Location:** `src/Contracts/ReportDefinitionInterface.php`

**Purpose:** Defines the contract for report template entities. A report definition contains the query configuration, template reference, format, and scheduling settings.

**Methods:**

#### getId()

```php
public function getId(): string;
```

**Description:** Returns the unique identifier (ULID) of the report definition.

**Returns:** `string` - 26-character ULID

---

#### getName()

```php
public function getName(): string;
```

**Description:** Returns the human-readable name of the report.

**Returns:** `string` - Report name

---

#### getTenantId()

```php
public function getTenantId(): string;
```

**Description:** Returns the tenant identifier for multi-tenant isolation.

**Returns:** `string` - Tenant ULID

---

#### getQueryId()

```php
public function getQueryId(): string;
```

**Description:** Returns the ID of the Analytics query that provides data for this report.

**Returns:** `string` - Analytics query ULID

---

#### getTemplateId()

```php
public function getTemplateId(): ?string;
```

**Description:** Returns the optional template ID for custom report formatting.

**Returns:** `?string` - Template ULID or null for default template

---

#### getFormat()

```php
public function getFormat(): ReportFormat;
```

**Description:** Returns the output format for the report.

**Returns:** `ReportFormat` - One of: PDF, EXCEL, CSV, JSON, HTML

---

#### getParameters()

```php
public function getParameters(): array;
```

**Description:** Returns the default parameters for the Analytics query.

**Returns:** `array` - Associative array of parameter name => value

---

#### getSchedule()

```php
public function getSchedule(): ?ReportSchedule;
```

**Description:** Returns the schedule configuration for automated report generation.

**Returns:** `?ReportSchedule` - Schedule value object or null if not scheduled

---

#### getRetentionTier()

```php
public function getRetentionTier(): RetentionTier;
```

**Description:** Returns the current retention tier for generated reports.

**Returns:** `RetentionTier` - One of: ACTIVE, ARCHIVED, PURGED

---

#### isActive()

```php
public function isActive(): bool;
```

**Description:** Returns whether the report definition is active and can be generated.

**Returns:** `bool` - True if active

---

#### getCreatedBy()

```php
public function getCreatedBy(): string;
```

**Description:** Returns the ID of the user who created this report definition.

**Returns:** `string` - User ULID

---

#### getCreatedAt()

```php
public function getCreatedAt(): \DateTimeImmutable;
```

**Description:** Returns the creation timestamp.

**Returns:** `\DateTimeImmutable` - Creation date/time

---

### ReportRepositoryInterface

**Location:** `src/Contracts/ReportRepositoryInterface.php`

**Purpose:** Defines persistence operations for report definitions and generated reports.

**Methods:**

#### findById()

```php
public function findById(string $id): ReportDefinitionInterface;
```

**Description:** Retrieves a report definition by its unique identifier.

**Parameters:**
- `$id` (string) - Report definition ULID

**Returns:** `ReportDefinitionInterface` - The report definition

**Throws:**
- `ReportNotFoundException` - When report definition not found

---

#### save()

```php
public function save(ReportDefinitionInterface $definition): void;
```

**Description:** Persists a new report definition.

**Parameters:**
- `$definition` (ReportDefinitionInterface) - The report definition to save

---

#### update()

```php
public function update(ReportDefinitionInterface $definition): void;
```

**Description:** Updates an existing report definition.

**Parameters:**
- `$definition` (ReportDefinitionInterface) - The report definition to update

---

#### archive()

```php
public function archive(string $id): void;
```

**Description:** Archives a report definition (soft delete).

**Parameters:**
- `$id` (string) - Report definition ULID

---

#### findGeneratedReportById()

```php
public function findGeneratedReportById(string $id): array;
```

**Description:** Retrieves metadata for a generated report file.

**Parameters:**
- `$id` (string) - Generated report ULID

**Returns:** `array` - Report metadata including file path, format, generation time

**Throws:**
- `ReportNotFoundException` - When generated report not found

---

### ReportGeneratorInterface

**Location:** `src/Contracts/ReportGeneratorInterface.php`

**Purpose:** Defines the contract for report generation orchestration. Coordinates between Analytics (query execution) and Export (file rendering).

**Methods:**

#### generate()

```php
public function generate(
    ReportDefinitionInterface $definition,
    array $parameters = []
): ReportResult;
```

**Description:** Generates a report from a definition with optional parameter overrides.

**Parameters:**
- `$definition` (ReportDefinitionInterface) - The report definition
- `$parameters` (array) - Optional parameter overrides for the query

**Returns:** `ReportResult` - Result containing file path, format, size, and metadata

**Throws:**
- `ReportGenerationException` - When generation fails (query, export, or storage)
- `UnauthorizedReportException` - When user lacks permission to execute query

---

#### generateFromQuery()

```php
public function generateFromQuery(
    string $queryId,
    ReportFormat $format,
    array $parameters = [],
    ?string $templateId = null
): ReportResult;
```

**Description:** Generates an ad-hoc report directly from a query without a saved definition.

**Parameters:**
- `$queryId` (string) - Analytics query ULID
- `$format` (ReportFormat) - Output format
- `$parameters` (array) - Query parameters
- `$templateId` (?string) - Optional template ULID

**Returns:** `ReportResult` - Generation result

**Throws:**
- `ReportGenerationException` - When generation fails
- `UnauthorizedReportException` - When user lacks query permission

---

#### previewReport()

```php
public function previewReport(
    ReportDefinitionInterface $definition,
    int $rowLimit = 100
): string;
```

**Description:** Generates a preview of the report with limited data for quick validation.

**Parameters:**
- `$definition` (ReportDefinitionInterface) - The report definition
- `$rowLimit` (int) - Maximum rows to include in preview (default: 100)

**Returns:** `string` - Path to preview file

**Throws:**
- `ReportGenerationException` - When preview generation fails

---

#### generateBatch()

```php
public function generateBatch(
    array $definitions,
    int $concurrencyLimit = 5
): array;
```

**Description:** Generates multiple reports in parallel with concurrency control.

**Parameters:**
- `$definitions` (array) - Array of ReportDefinitionInterface objects
- `$concurrencyLimit` (int) - Maximum concurrent generations (default: 5)

**Returns:** `array<string, ReportResult>` - Map of definition ID to result

**Throws:**
- `ReportGenerationException` - When batch generation fails critically

---

### ReportDistributorInterface

**Location:** `src/Contracts/ReportDistributorInterface.php`

**Purpose:** Defines the contract for report distribution via multiple channels using the Notifier package.

**Methods:**

#### distribute()

```php
public function distribute(
    ReportResult $report,
    array $recipients,
    string $channel = 'email'
): DistributionResult;
```

**Description:** Distributes a generated report to recipients via specified channel.

**Parameters:**
- `$report` (ReportResult) - The generated report
- `$recipients` (array) - Array of recipient identifiers (emails, user IDs)
- `$channel` (string) - Distribution channel: 'email', 'slack', 'in_app', 'webhook'

**Returns:** `DistributionResult` - Distribution outcome with success/failure counts

**Throws:**
- `ReportDistributionException` - When distribution fails

---

#### scheduleDistribution()

```php
public function scheduleDistribution(
    ReportDefinitionInterface $definition,
    array $recipients,
    ReportSchedule $schedule,
    string $channel = 'email'
): string;
```

**Description:** Sets up recurring distribution for a scheduled report.

**Parameters:**
- `$definition` (ReportDefinitionInterface) - The report definition
- `$recipients` (array) - Distribution recipient list
- `$schedule` (ReportSchedule) - Distribution schedule
- `$channel` (string) - Distribution channel

**Returns:** `string` - Scheduler job ID

---

#### trackDelivery()

```php
public function trackDelivery(string $notificationId): DistributionStatus;
```

**Description:** Checks the delivery status of a distributed report.

**Parameters:**
- `$notificationId` (string) - Notification ID from DistributionResult

**Returns:** `DistributionStatus` - Current delivery status

---

#### retryFailedDistributions()

```php
public function retryFailedDistributions(string $reportId): DistributionResult;
```

**Description:** Retries all failed distributions for a report.

**Parameters:**
- `$reportId` (string) - Generated report ULID

**Returns:** `DistributionResult` - Retry outcome

**Throws:**
- `ReportDistributionException` - When retry fails
- `ReportNotFoundException` - When report file no longer exists

---

### ReportRetentionInterface

**Location:** `src/Contracts/ReportRetentionInterface.php`

**Purpose:** Defines the contract for report lifecycle and retention management with 3-tier storage.

**Methods:**

#### applyRetentionPolicy()

```php
public function applyRetentionPolicy(string $reportId): RetentionTier;
```

**Description:** Evaluates and applies retention policy to transition report between tiers.

**Parameters:**
- `$reportId` (string) - Generated report ULID

**Returns:** `RetentionTier` - Current tier after evaluation

---

#### archiveReport()

```php
public function archiveReport(string $reportId): void;
```

**Description:** Manually transitions a report from ACTIVE to ARCHIVED tier.

**Parameters:**
- `$reportId` (string) - Generated report ULID

**Throws:**
- `ReportNotFoundException` - When report not found

---

#### purgeReport()

```php
public function purgeReport(string $reportId): void;
```

**Description:** Permanently deletes a report from storage. Only allowed for ARCHIVED reports.

**Parameters:**
- `$reportId` (string) - Generated report ULID

**Throws:**
- `ReportNotFoundException` - When report not found
- `ReportingException` - When trying to purge non-archived report

---

#### getRetentionStatistics()

```php
public function getRetentionStatistics(): array;
```

**Description:** Returns aggregate statistics about report storage across tiers.

**Returns:** `array` - Statistics including counts and sizes per tier

**Example Return:**
```php
[
    'active' => ['count' => 150, 'size_bytes' => 1073741824],
    'archived' => ['count' => 500, 'size_bytes' => 5368709120],
    'total_reports' => 650,
    'total_size_bytes' => 6442450944,
]
```

---

#### checkTransitionStatus()

```php
public function checkTransitionStatus(string $reportId): array;
```

**Description:** Returns transition eligibility and timing information for a report.

**Parameters:**
- `$reportId` (string) - Generated report ULID

**Returns:** `array` - Transition status and dates

**Example Return:**
```php
[
    'current_tier' => RetentionTier::ACTIVE,
    'eligible_for_archive' => true,
    'archive_after' => DateTimeImmutable('2024-03-01'),
    'eligible_for_purge' => false,
    'purge_after' => null,
]
```

---

### ReportTemplateInterface

**Location:** `src/Contracts/ReportTemplateInterface.php`

**Purpose:** Defines the contract for report templates used by the Export package.

**Methods:**

#### getId()

```php
public function getId(): string;
```

**Description:** Returns the template unique identifier.

**Returns:** `string` - Template ULID

---

#### getName()

```php
public function getName(): string;
```

**Description:** Returns the template name.

**Returns:** `string` - Template name

---

#### getFormat()

```php
public function getFormat(): ReportFormat;
```

**Description:** Returns the output format this template supports.

**Returns:** `ReportFormat` - Supported format

---

#### getContent()

```php
public function getContent(): string;
```

**Description:** Returns the template content (Blade, Twig, or raw markup).

**Returns:** `string` - Template content

---

#### getVariables()

```php
public function getVariables(): array;
```

**Description:** Returns the list of variables expected by the template.

**Returns:** `array` - Variable names and optional defaults

---

## Services

### ReportManager

**Location:** `src/Services/ReportManager.php`

**Purpose:** Main public API for the Reporting package. Orchestrates all reporting operations with security enforcement.

**Constructor Dependencies:**
- `ReportRepositoryInterface` - Persistence operations
- `ReportGeneratorInterface` - Generation orchestration
- `ReportDistributorInterface` - Distribution handling
- `ReportRetentionInterface` - Lifecycle management
- `TenantContextInterface` - Multi-tenant context
- `LoggerInterface` - PSR-3 logging (optional)
- `AuditLogManagerInterface` - Audit trail (optional)

**Public Methods:**

#### createReport()

```php
public function createReport(
    string $name,
    string $queryId,
    ReportFormat $format,
    array $parameters = [],
    ?string $templateId = null
): ReportDefinitionInterface;
```

**Description:** Creates a new report definition.

**Parameters:**
- `$name` (string) - Report name
- `$queryId` (string) - Analytics query ULID
- `$format` (ReportFormat) - Output format
- `$parameters` (array) - Default query parameters
- `$templateId` (?string) - Optional template ULID

**Returns:** `ReportDefinitionInterface` - Created definition

---

#### generateReport()

```php
public function generateReport(
    string $reportId,
    array $parameterOverrides = []
): ReportResult;
```

**Description:** Generates a report from an existing definition.

**Parameters:**
- `$reportId` (string) - Report definition ULID
- `$parameterOverrides` (array) - Optional parameter overrides

**Returns:** `ReportResult` - Generation result

**Throws:**
- `ReportNotFoundException` - When definition not found
- `ReportGenerationException` - When generation fails
- `UnauthorizedReportException` - When lacking permission

---

#### previewReport()

```php
public function previewReport(string $reportId, int $rowLimit = 100): string;
```

**Description:** Generates a limited preview of a report.

**Parameters:**
- `$reportId` (string) - Report definition ULID
- `$rowLimit` (int) - Maximum rows (default: 100)

**Returns:** `string` - Path to preview file

---

#### generateBatch()

```php
public function generateBatch(
    array $reportIds,
    int $concurrencyLimit = 5
): array;
```

**Description:** Generates multiple reports in parallel.

**Parameters:**
- `$reportIds` (array) - Array of report definition ULIDs
- `$concurrencyLimit` (int) - Maximum concurrent generations

**Returns:** `array<string, ReportResult>` - Map of report ID to result

---

#### distributeReport()

```php
public function distributeReport(
    string $reportId,
    array $recipients,
    string $channel = 'email'
): DistributionResult;
```

**Description:** Distributes a generated report to recipients.

**Parameters:**
- `$reportId` (string) - Generated report ULID
- `$recipients` (array) - Recipient list
- `$channel` (string) - Distribution channel

**Returns:** `DistributionResult` - Distribution outcome

---

#### scheduleReport()

```php
public function scheduleReport(
    string $reportId,
    ReportSchedule $schedule,
    array $recipients = [],
    string $channel = 'email'
): string;
```

**Description:** Sets up scheduled generation and optional distribution.

**Parameters:**
- `$reportId` (string) - Report definition ULID
- `$schedule` (ReportSchedule) - Schedule configuration
- `$recipients` (array) - Optional auto-distribution recipients
- `$channel` (string) - Distribution channel

**Returns:** `string` - Scheduler job ID

---

#### updateReport()

```php
public function updateReport(
    string $reportId,
    array $changes
): ReportDefinitionInterface;
```

**Description:** Updates an existing report definition.

**Parameters:**
- `$reportId` (string) - Report definition ULID
- `$changes` (array) - Fields to update

**Returns:** `ReportDefinitionInterface` - Updated definition

---

#### archiveReport()

```php
public function archiveReport(string $reportId): void;
```

**Description:** Archives a report definition (soft delete).

**Parameters:**
- `$reportId` (string) - Report definition ULID

---

## Value Objects

### ReportResult

**Location:** `src/ValueObjects/ReportResult.php`

**Purpose:** Immutable value object representing the outcome of report generation.

**Properties:**
- `$reportId` (string) - Generated report ULID
- `$format` (ReportFormat) - Output format used
- `$filePath` (string) - Storage path to generated file
- `$fileSize` (int) - File size in bytes
- `$generatedAt` (\DateTimeImmutable) - Generation timestamp
- `$durationMs` (int) - Generation duration in milliseconds
- `$isSuccessful` (bool) - Whether generation succeeded
- `$error` (?string) - Error message if failed
- `$retentionTier` (RetentionTier) - Current retention tier
- `$queryResultId` (string) - Analytics query result ID for lineage

**Example:**
```php
$result = new ReportResult(
    reportId: '01HXYZ...',
    format: ReportFormat::PDF,
    filePath: '/reports/2024/01/report_01HXYZ.pdf',
    fileSize: 1048576,
    generatedAt: new \DateTimeImmutable(),
    durationMs: 2500,
    isSuccessful: true,
    error: null,
    retentionTier: RetentionTier::ACTIVE,
    queryResultId: '01HABC...'
);
```

---

### DistributionResult

**Location:** `src/ValueObjects/DistributionResult.php`

**Purpose:** Immutable value object representing the outcome of report distribution.

**Properties:**
- `$reportId` (string) - Distributed report ULID
- `$notificationIds` (array) - Array of Notifier notification IDs
- `$successCount` (int) - Number of successful deliveries
- `$failureCount` (int) - Number of failed deliveries
- `$errors` (array) - Error messages keyed by recipient
- `$distributedAt` (\DateTimeImmutable) - Distribution timestamp

**Methods:**

#### isFullySuccessful()

```php
public function isFullySuccessful(): bool;
```

**Returns:** `bool` - True if all distributions succeeded

#### hasPartialFailure()

```php
public function hasPartialFailure(): bool;
```

**Returns:** `bool` - True if some but not all distributions failed

**Example:**
```php
$result = new DistributionResult(
    reportId: '01HXYZ...',
    notificationIds: ['notif_1', 'notif_2', 'notif_3'],
    successCount: 2,
    failureCount: 1,
    errors: ['user@invalid.com' => 'Mailbox not found'],
    distributedAt: new \DateTimeImmutable()
);

if ($result->hasPartialFailure()) {
    // Handle partial failure
}
```

---

### ReportSchedule

**Location:** `src/ValueObjects/ReportSchedule.php`

**Purpose:** Immutable value object for schedule configuration with factory methods.

**Properties:**
- `$type` (ScheduleType) - Schedule type
- `$cronExpression` (?string) - Cron expression for CRON type
- `$startsAt` (\DateTimeImmutable) - Schedule start date
- `$endsAt` (?\DateTimeImmutable) - Optional end date
- `$maxOccurrences` (?int) - Maximum number of runs

**Factory Methods:**

#### once()

```php
public static function once(\DateTimeImmutable $runAt): self;
```

**Description:** Creates a one-time schedule.

**Parameters:**
- `$runAt` (\DateTimeImmutable) - When to run

**Returns:** `ReportSchedule` - One-time schedule

---

#### daily()

```php
public static function daily(
    \DateTimeImmutable $startsAt,
    ?\DateTimeImmutable $endsAt = null
): self;
```

**Description:** Creates a daily recurring schedule.

**Parameters:**
- `$startsAt` (\DateTimeImmutable) - First run date
- `$endsAt` (?\DateTimeImmutable) - Optional end date

**Returns:** `ReportSchedule` - Daily schedule

---

#### weekly()

```php
public static function weekly(
    \DateTimeImmutable $startsAt,
    ?\DateTimeImmutable $endsAt = null
): self;
```

**Description:** Creates a weekly recurring schedule.

---

#### monthly()

```php
public static function monthly(
    \DateTimeImmutable $startsAt,
    ?\DateTimeImmutable $endsAt = null
): self;
```

**Description:** Creates a monthly recurring schedule.

---

#### cron()

```php
public static function cron(
    string $expression,
    \DateTimeImmutable $startsAt,
    ?\DateTimeImmutable $endsAt = null
): self;
```

**Description:** Creates a custom cron schedule.

**Parameters:**
- `$expression` (string) - Valid cron expression (e.g., '0 8 * * 1-5')
- `$startsAt` (\DateTimeImmutable) - Schedule start
- `$endsAt` (?\DateTimeImmutable) - Optional end date

**Returns:** `ReportSchedule` - Cron schedule

**Throws:**
- `InvalidReportScheduleException` - When cron expression is invalid

**Example:**
```php
// Generate report at 8 AM on weekdays
$schedule = ReportSchedule::cron(
    expression: '0 8 * * 1-5',
    startsAt: new \DateTimeImmutable('2024-01-01'),
    endsAt: new \DateTimeImmutable('2024-12-31')
);
```

---

## Enums

### ReportFormat

**Location:** `src/ValueObjects/ReportFormat.php`

**Purpose:** Defines available report output formats.

**Cases:**
- `PDF` - Adobe PDF format
- `EXCEL` - Microsoft Excel (.xlsx)
- `CSV` - Comma-separated values
- `JSON` - JSON format (for API consumers)
- `HTML` - HTML markup (for web viewing)

**Methods:**

#### label()

```php
public function label(): string;
```

**Returns:** `string` - Human-readable format name

**Example:** `ReportFormat::PDF->label()` returns `'PDF Document'`

---

#### extension()

```php
public function extension(): string;
```

**Returns:** `string` - File extension without dot

**Example:** `ReportFormat::EXCEL->extension()` returns `'xlsx'`

---

#### mimeType()

```php
public function mimeType(): string;
```

**Returns:** `string` - MIME type for HTTP headers

**Example:** `ReportFormat::PDF->mimeType()` returns `'application/pdf'`

---

#### supportsStreaming()

```php
public function supportsStreaming(): bool;
```

**Returns:** `bool` - Whether format can be streamed progressively

**Example:** `ReportFormat::CSV->supportsStreaming()` returns `true`

---

### ScheduleType

**Location:** `src/ValueObjects/ScheduleType.php`

**Purpose:** Defines report schedule recurrence types.

**Cases:**
- `ONCE` - One-time execution
- `DAILY` - Daily recurrence
- `WEEKLY` - Weekly recurrence
- `MONTHLY` - Monthly recurrence
- `YEARLY` - Yearly recurrence
- `CRON` - Custom cron expression

**Methods:**

#### label()

```php
public function label(): string;
```

**Returns:** `string` - Human-readable schedule name

---

#### requiresCronExpression()

```php
public function requiresCronExpression(): bool;
```

**Returns:** `bool` - True only for CRON type

---

#### isRecurring()

```php
public function isRecurring(): bool;
```

**Returns:** `bool` - False only for ONCE type

---

### RetentionTier

**Location:** `src/ValueObjects/RetentionTier.php`

**Purpose:** Defines report lifecycle stages with storage characteristics.

**Cases:**
- `ACTIVE` - Hot storage, immediate access (90 days)
- `ARCHIVED` - Cold storage, slower access (~7 years)
- `PURGED` - Deleted from storage

**Methods:**

#### durationDays()

```php
public function durationDays(): int;
```

**Returns:** `int` - Days before transition to next tier

**Example:** 
- `RetentionTier::ACTIVE->durationDays()` returns `90`
- `RetentionTier::ARCHIVED->durationDays()` returns `2555` (~7 years)

---

#### nextTier()

```php
public function nextTier(): ?self;
```

**Returns:** `?RetentionTier` - Next tier in lifecycle or null

**Example:** `RetentionTier::ACTIVE->nextTier()` returns `RetentionTier::ARCHIVED`

---

#### isAccessible()

```php
public function isAccessible(): bool;
```

**Returns:** `bool` - Whether reports in this tier can be accessed

**Example:** `RetentionTier::PURGED->isAccessible()` returns `false`

---

#### storageClass()

```php
public function storageClass(): string;
```

**Returns:** `string` - Cloud storage class hint

**Example:**
- `RetentionTier::ACTIVE->storageClass()` returns `'STANDARD'`
- `RetentionTier::ARCHIVED->storageClass()` returns `'GLACIER'`

---

### DistributionStatus

**Location:** `src/ValueObjects/DistributionStatus.php`

**Purpose:** Tracks the delivery status of distributed reports.

**Cases:**
- `PENDING` - Queued for delivery
- `SENT` - Sent but not confirmed
- `DELIVERED` - Confirmed delivered
- `FAILED` - Delivery failed
- `BOUNCED` - Recipient rejected
- `READ` - Confirmed read (where trackable)

**Methods:**

#### isSuccessful()

```php
public function isSuccessful(): bool;
```

**Returns:** `bool` - True for DELIVERED and READ

---

#### shouldRetry()

```php
public function shouldRetry(): bool;
```

**Returns:** `bool` - True for FAILED (transient errors)

---

#### isTerminal()

```php
public function isTerminal(): bool;
```

**Returns:** `bool` - True for final states (DELIVERED, BOUNCED, READ)

---

## Exceptions

### ReportingException

**Location:** `src/Exceptions/ReportingException.php`

**Purpose:** Base exception for all reporting errors.

**Factory Methods:**

#### withContext()

```php
public static function withContext(string $message, array $context = []): self;
```

**Description:** Creates exception with additional context data.

**Parameters:**
- `$message` (string) - Error message
- `$context` (array) - Additional context for debugging

**Returns:** `ReportingException` - Exception with context

---

### ReportNotFoundException

**Location:** `src/Exceptions/ReportNotFoundException.php`

**Purpose:** Thrown when a report definition or generated report cannot be found.

**Factory Methods:**

#### forId()

```php
public static function forId(string $id): self;
```

**Description:** Creates exception for missing report definition.

**Parameters:**
- `$id` (string) - Report definition ULID

**Returns:** `ReportNotFoundException` - Exception with message

**Example:**
```php
throw ReportNotFoundException::forId('01HXYZ...');
// Message: "Report definition with ID '01HXYZ...' not found"
```

---

#### forGeneratedReport()

```php
public static function forGeneratedReport(string $id): self;
```

**Description:** Creates exception for missing generated report file.

**Parameters:**
- `$id` (string) - Generated report ULID

**Returns:** `ReportNotFoundException` - Exception with message

---

### ReportGenerationException

**Location:** `src/Exceptions/ReportGenerationException.php`

**Purpose:** Thrown when report generation fails at any stage.

**Factory Methods:**

#### queryExecutionFailed()

```php
public static function queryExecutionFailed(string $queryId, string $reason): self;
```

**Description:** Analytics query execution failed.

---

#### exportFailed()

```php
public static function exportFailed(ReportFormat $format, string $reason): self;
```

**Description:** Export rendering failed.

---

#### templateLoadFailed()

```php
public static function templateLoadFailed(string $templateId): self;
```

**Description:** Template could not be loaded.

---

#### storageFailed()

```php
public static function storageFailed(string $path, string $reason): self;
```

**Description:** File storage operation failed.

---

#### timeout()

```php
public static function timeout(int $timeoutSeconds): self;
```

**Description:** Generation exceeded time limit.

---

### ReportDistributionException

**Location:** `src/Exceptions/ReportDistributionException.php`

**Purpose:** Thrown when report distribution fails.

**Factory Methods:**

#### notificationFailed()

```php
public static function notificationFailed(string $channel, string $reason): self;
```

**Description:** Single notification delivery failed.

---

#### batchFailed()

```php
public static function batchFailed(int $failureCount, int $totalCount): self;
```

**Description:** Batch distribution had failures.

---

#### missingReportFile()

```php
public static function missingReportFile(string $reportId): self;
```

**Description:** Report file no longer exists in storage.

---

#### invalidRecipient()

```php
public static function invalidRecipient(string $recipient, string $reason): self;
```

**Description:** Recipient address or ID is invalid.

---

### UnauthorizedReportException

**Location:** `src/Exceptions/UnauthorizedReportException.php`

**Purpose:** Thrown when user lacks permission for reporting operations.

**Factory Methods:**

#### cannotExecuteQuery()

```php
public static function cannotExecuteQuery(string $queryId, string $userId): self;
```

**Description:** User lacks permission to execute the Analytics query.

---

#### cannotAccessReport()

```php
public static function cannotAccessReport(string $reportId, string $userId): self;
```

**Description:** User lacks access to the report definition.

---

#### tenantMismatch()

```php
public static function tenantMismatch(string $expected, string $actual): self;
```

**Description:** Tenant context does not match report's tenant.

---

#### missingPermission()

```php
public static function missingPermission(string $permission, string $userId): self;
```

**Description:** User lacks required permission.

---

### InvalidReportScheduleException

**Location:** `src/Exceptions/InvalidReportScheduleException.php`

**Purpose:** Thrown when schedule configuration is invalid.

**Factory Methods:**

#### invalidCronExpression()

```php
public static function invalidCronExpression(string $expression): self;
```

**Description:** Cron expression syntax is invalid.

---

#### invalidDateRange()

```php
public static function invalidDateRange(
    \DateTimeImmutable $start,
    \DateTimeImmutable $end
): self;
```

**Description:** End date is before start date.

---

#### missingField()

```php
public static function missingField(string $field): self;
```

**Description:** Required schedule field is missing.

---

#### invalidInterval()

```php
public static function invalidInterval(string $type): self;
```

**Description:** Interval configuration is invalid for schedule type.

---

#### scheduleInPast()

```php
public static function scheduleInPast(\DateTimeImmutable $scheduledAt): self;
```

**Description:** Scheduled time is in the past.

---

## Usage Patterns

### Pattern 1: Basic Report Generation

```php
use Nexus\Reporting\Services\ReportManager;
use Nexus\Reporting\ValueObjects\ReportFormat;

$reportManager = $container->get(ReportManager::class);

// Create a report definition
$definition = $reportManager->createReport(
    name: 'Monthly Sales Report',
    queryId: '01HABC...',  // Analytics query ID
    format: ReportFormat::PDF,
    parameters: ['month' => '2024-01', 'region' => 'APAC']
);

// Generate the report
$result = $reportManager->generateReport($definition->getId());

if ($result->isSuccessful) {
    echo "Report generated: {$result->filePath}";
    echo "Size: {$result->fileSize} bytes";
    echo "Duration: {$result->durationMs}ms";
}
```

---

### Pattern 2: Scheduled Reports with Distribution

```php
use Nexus\Reporting\ValueObjects\ReportSchedule;

// Create weekly schedule
$schedule = ReportSchedule::weekly(
    startsAt: new \DateTimeImmutable('next Monday 08:00'),
    endsAt: new \DateTimeImmutable('+1 year')
);

// Schedule with auto-distribution
$jobId = $reportManager->scheduleReport(
    reportId: $definition->getId(),
    schedule: $schedule,
    recipients: ['cfo@company.com', 'finance-team@company.com'],
    channel: 'email'
);

echo "Scheduled job: {$jobId}";
```

---

### Pattern 3: Multi-Format Report Generation

```php
// Generate same report in multiple formats
$formats = [ReportFormat::PDF, ReportFormat::EXCEL, ReportFormat::CSV];
$results = [];

foreach ($formats as $format) {
    $definition = $reportManager->createReport(
        name: "Q4 Report ({$format->label()})",
        queryId: '01HABC...',
        format: $format
    );
    
    $results[$format->value] = $reportManager->generateReport($definition->getId());
}

// Distribute all versions
foreach ($results as $format => $result) {
    $reportManager->distributeReport(
        reportId: $result->reportId,
        recipients: ['stakeholders@company.com'],
        channel: 'email'
    );
}
```

---

### Pattern 4: Retention Management

```php
// Check retention status
$status = $retentionManager->checkTransitionStatus($reportId);

if ($status['eligible_for_archive']) {
    echo "Report can be archived after: {$status['archive_after']->format('Y-m-d')}";
}

// Get storage statistics
$stats = $retentionManager->getRetentionStatistics();
echo "Active reports: {$stats['active']['count']}";
echo "Archived reports: {$stats['archived']['count']}";
echo "Total storage: " . number_format($stats['total_size_bytes'] / 1024 / 1024) . " MB";
```

---

**Prepared By:** Nexus Architecture Team  
**Last Updated:** 2025-11-30

