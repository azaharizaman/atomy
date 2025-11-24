# API Reference: AuditLogger

## Interfaces

### AuditLogInterface

**Location:** `src/Contracts/AuditLogInterface.php`

**Purpose:** Defines the structure of an audit log record

**Methods:**

#### getId()

```php
public function getId(): string;
```

**Description:** Get the unique identifier of the audit log (ULID)

**Returns:** `string` - The audit log ID

---

#### getTenantId()

```php
public function getTenantId(): ?string;
```

**Description:** Get the tenant ID for multi-tenant isolation

**Returns:** `string|null` - The tenant ID or null for single-tenant systems

---

#### getLogName()

```php
public function getLogName(): string;
```

**Description:** Get the log name (e.g., 'user_updated', 'invoice_created')

**Returns:** `string` - The log name

---

#### getDescription()

```php
public function getDescription(): string;
```

**Description:** Get the human-readable description of the activity

**Returns:** `string` - The description

---

#### getSubjectType()

```php
public function getSubjectType(): ?string;
```

**Description:** Get the entity type that was affected (e.g., 'User', 'Invoice')

**Returns:** `string|null` - The subject type

---

#### getSubjectId()

```php
public function getSubjectId(): ?string;
```

**Description:** Get the ID of the entity that was affected

**Returns:** `string|null` - The subject ID

---

#### getCauserType()

```php
public function getCauserType(): ?string;
```

**Description:** Get the entity type that caused the activity (e.g., 'User', 'System')

**Returns:** `string|null` - The causer type (null for system activities)

---

#### getCauserId()

```php
public function getCauserId(): ?string;
```

**Description:** Get the ID of the entity that caused the activity

**Returns:** `string|null` - The causer ID

---

#### getProperties()

```php
public function getProperties(): array;
```

**Description:** Get additional properties (old/new values, metadata)

**Returns:** `array` - Properties array (can contain 'old', 'new', 'attributes', etc.)

---

#### getLevel()

```php
public function getLevel(): int;
```

**Description:** Get the audit level (1=Low, 2=Medium, 3=High, 4=Critical)

**Returns:** `int` - The audit level

---

#### getBatchUuid()

```php
public function getBatchUuid(): ?string;
```

**Description:** Get the batch UUID for grouping related operations

**Returns:** `string|null` - The batch UUID

---

#### getIpAddress()

```php
public function getIpAddress(): ?string;
```

**Description:** Get the IP address of the user

**Returns:** `string|null` - The IP address

---

#### getUserAgent()

```php
public function getUserAgent(): ?string;
```

**Description:** Get the user agent string

**Returns:** `string|null` - The user agent

---

#### getCreatedAt()

```php
public function getCreatedAt(): \DateTimeImmutable;
```

**Description:** Get the timestamp when the log was created

**Returns:** `\DateTimeImmutable` - The creation timestamp

---

### AuditLogRepositoryInterface

**Location:** `src/Contracts/AuditLogRepositoryInterface.php`

**Purpose:** Defines persistence operations for audit logs

**Methods:**

#### save()

```php
public function save(AuditLogInterface $auditLog): void;
```

**Description:** Persist an audit log record

**Parameters:**
- `$auditLog` (AuditLogInterface) - The audit log to save

**Returns:** `void`

**Throws:** None

---

#### findById()

```php
public function findById(string $id): AuditLogInterface;
```

**Description:** Retrieve an audit log by ID

**Parameters:**
- `$id` (string) - The audit log ID

**Returns:** `AuditLogInterface` - The audit log

**Throws:**
- `AuditLogNotFoundException` - When log not found

---

#### findAll()

```php
public function findAll(?string $tenantId = null): array;
```

**Description:** Retrieve all audit logs (optionally filtered by tenant)

**Parameters:**
- `$tenantId` (string|null) - Optional tenant ID filter

**Returns:** `array<AuditLogInterface>` - Array of audit logs

---

#### search()

```php
public function search(
    ?string $tenantId = null,
    ?string $keyword = null,
    ?string $entityType = null,
    ?\DateTimeImmutable $startDate = null,
    ?\DateTimeImmutable $endDate = null,
    ?int $level = null,
    int $limit = 100,
    int $offset = 0
): array;
```

**Description:** Search audit logs with filters

**Parameters:**
- `$tenantId` (string|null) - Filter by tenant
- `$keyword` (string|null) - Search in description and properties
- `$entityType` (string|null) - Filter by subject_type
- `$startDate` (\DateTimeImmutable|null) - Start of date range
- `$endDate` (\DateTimeImmutable|null) - End of date range
- `$level` (int|null) - Filter by audit level
- `$limit` (int) - Maximum results
- `$offset` (int) - Offset for pagination

**Returns:** `array<AuditLogInterface>` - Matching audit logs

---

#### delete()

```php
public function delete(string $id): void;
```

**Description:** Delete an audit log by ID

**Parameters:**
- `$id` (string) - The audit log ID

**Returns:** `void`

---

#### deleteOlderThan()

```php
public function deleteOlderThan(\DateTimeImmutable $date, ?string $tenantId = null): int;
```

**Description:** Delete audit logs older than specified date

**Parameters:**
- `$date` (\DateTimeImmutable) - Cutoff date
- `$tenantId` (string|null) - Optional tenant filter

**Returns:** `int` - Number of deleted records

---

### AuditConfigInterface

**Location:** `src/Contracts/AuditConfigInterface.php`

**Purpose:** Defines configuration for audit logging

**Methods:**

#### getDefaultRetentionDays()

```php
public function getDefaultRetentionDays(): int;
```

**Description:** Get default retention period in days

**Returns:** `int` - Number of days to retain logs

---

#### getSensitiveFields()

```php
public function getSensitiveFields(): array;
```

**Description:** Get list of sensitive field names to mask

**Returns:** `array<string>` - Field names

---

#### isAsyncEnabled()

```php
public function isAsyncEnabled(): bool;
```

**Description:** Check if asynchronous logging is enabled

**Returns:** `bool` - True if async logging enabled

---

#### getExportFormats()

```php
public function getExportFormats(): array;
```

**Description:** Get supported export formats

**Returns:** `array<string>` - Formats (e.g., ['csv', 'json', 'pdf'])

---

## Services

### AuditLogManager

**Location:** `src/Services/AuditLogManager.php`

**Purpose:** Core logging service

**Constructor Dependencies:**
- `AuditLogRepositoryInterface` - For persistence
- `AuditConfigInterface` - For configuration

**Public Methods:**

#### log()

```php
public function log(
    string $logName,
    string $description,
    ?string $subjectType = null,
    ?string $subjectId = null,
    ?string $causerType = null,
    ?string $causerId = null,
    array $properties = [],
    int $level = 2,
    ?string $batchUuid = null,
    ?string $tenantId = null,
    ?string $ipAddress = null,
    ?string $userAgent = null
): string;
```

**Description:** Create an audit log entry

**Parameters:**
- `$logName` (string) - Log identifier
- `$description` (string) - Human-readable description
- `$subjectType` (string|null) - Entity type affected
- `$subjectId` (string|null) - Entity ID affected
- `$causerType` (string|null) - Entity type that caused action
- `$causerId` (string|null) - Entity ID that caused action
- `$properties` (array) - Additional data (old/new values)
- `$level` (int) - Audit level (1-4)
- `$batchUuid` (string|null) - Batch grouping UUID
- `$tenantId` (string|null) - Tenant ID
- `$ipAddress` (string|null) - IP address
- `$userAgent` (string|null) - User agent

**Returns:** `string` - The created audit log ID

**Throws:**
- `MissingRequiredFieldException` - When required fields missing
- `InvalidAuditLevelException` - When level not 1-4

**Example:**
```php
$logId = $auditManager->log(
    logName: 'user_updated',
    description: 'User profile updated',
    subjectType: 'User',
    subjectId: '01USER123',
    causerType: 'User',
    causerId: '01USER456',
    properties: ['old' => [...], 'new' => [...]],
    level: 2,
    tenantId: '01TENANT123'
);
```

---

### AuditLogSearchService

**Location:** `src/Services/AuditLogSearchService.php`

**Purpose:** Search and filtering service

**Constructor Dependencies:**
- `AuditLogRepositoryInterface` - For data access

**Public Methods:**

#### search()

```php
public function search(
    ?string $tenantId = null,
    ?string $keyword = null,
    ?string $entityType = null,
    ?\DateTimeImmutable $startDate = null,
    ?\DateTimeImmutable $endDate = null,
    ?int $level = null,
    int $limit = 100,
    int $offset = 0
): array;
```

**Description:** Search audit logs with filters

**Returns:** `array<AuditLogInterface>` - Matching logs

**Example:**
```php
$results = $searchService->search(
    tenantId: '01TENANT123',
    keyword: 'invoice',
    level: 3,
    startDate: new \DateTimeImmutable('2025-01-01'),
    limit: 50
);
```

---

### AuditLogExportService

**Location:** `src/Services/AuditLogExportService.php`

**Purpose:** Export audit logs to various formats

**Constructor Dependencies:**
- `AuditLogRepositoryInterface` - For data access

**Public Methods:**

#### exportToCsv()

```php
public function exportToCsv(
    ?string $tenantId = null,
    ?\DateTimeImmutable $startDate = null,
    ?\DateTimeImmutable $endDate = null
): string;
```

**Description:** Export audit logs to CSV format

**Returns:** `string` - CSV content

---

#### exportToJson()

```php
public function exportToJson(
    ?string $tenantId = null,
    ?\DateTimeImmutable $startDate = null,
    ?\DateTimeImmutable $endDate = null
): string;
```

**Description:** Export audit logs to JSON format

**Returns:** `string` - JSON content

---

#### exportToPdf()

```php
public function exportToPdf(
    ?string $tenantId = null,
    ?\DateTimeImmutable $startDate = null,
    ?\DateTimeImmutable $endDate = null
): string;
```

**Description:** Export audit logs to PDF format

**Returns:** `string` - PDF content

---

### RetentionPolicyService

**Location:** `src/Services/RetentionPolicyService.php`

**Purpose:** Automated purging of expired logs

**Constructor Dependencies:**
- `AuditLogRepositoryInterface` - For data access

**Public Methods:**

#### purgeExpiredLogs()

```php
public function purgeExpiredLogs(
    ?string $tenantId = null,
    ?RetentionPolicy $policy = null
): int;
```

**Description:** Delete logs older than retention period

**Parameters:**
- `$tenantId` (string|null) - Optional tenant filter
- `$policy` (RetentionPolicy|null) - Optional custom policy (defaults to config)

**Returns:** `int` - Number of deleted logs

**Example:**
```php
$deletedCount = $retentionService->purgeExpiredLogs(
    tenantId: '01TENANT123',
    policy: RetentionPolicy::days90()
);
```

---

### SensitiveDataMasker

**Location:** `src/Services/SensitiveDataMasker.php`

**Purpose:** Mask sensitive data in properties

**Constructor Dependencies:**
- `AuditConfigInterface` - For sensitive field configuration

**Public Methods:**

#### mask()

```php
public function mask(array $properties): array;
```

**Description:** Mask sensitive fields in properties array

**Parameters:**
- `$properties` (array) - Properties to mask

**Returns:** `array` - Masked properties

**Example:**
```php
$masked = $masker->mask([
    'email' => 'user@example.com',
    'password' => 'secret123',  // Will be masked
    'token' => 'abc123xyz',      // Will be masked
]);

// Result: ['email' => 'user@example.com', 'password' => '********', 'token' => '***123xyz']
```

---

## Value Objects

### AuditLevel

**Location:** `src/ValueObjects/AuditLevel.php`

**Purpose:** Audit severity levels (enum)

**Type:** `enum`

**Cases:**
- `Low = 1` - Routine activities (user login, document view)
- `Medium = 2` - Standard operations (record update, file upload)
- `High = 3` - Sensitive operations (role assignment, config change)
- `Critical = 4` - High-value activities (financial transaction, data export)

**Methods:**

#### fromValue()

```php
public static function fromValue(int $value): self;
```

**Description:** Create enum from integer value

**Throws:** `InvalidAuditLevelException` - When value not 1-4

**Example:**
```php
$level = AuditLevel::fromValue(3); // AuditLevel::High
```

---

### RetentionPolicy

**Location:** `src/ValueObjects/RetentionPolicy.php`

**Purpose:** Retention period configuration

**Properties:**
- `$retentionDays` (int, readonly) - Number of days to retain logs

**Methods:**

#### constructor

```php
public function __construct(
    public readonly int $retentionDays
)
```

**Throws:** `InvalidRetentionPolicyException` - When days < 1

---

#### isExpired()

```php
public function isExpired(\DateTimeImmutable $date): bool;
```

**Description:** Check if a date is beyond retention period

**Returns:** `bool` - True if date is older than retention period

---

#### Factory Methods

```php
public static function days30(): self;   // 30 days
public static function days90(): self;   // 90 days
public static function days365(): self;  // 365 days
```

**Example:**
```php
$policy = RetentionPolicy::days90();

$isExpired = $policy->isExpired(new \DateTimeImmutable('2024-01-01'));
// True if more than 90 days ago
```

---

## Exceptions

### AuditLogNotFoundException

**Location:** `src/Exceptions/AuditLogNotFoundException.php`

**Extends:** `\RuntimeException`

**Purpose:** Thrown when audit log not found by ID

**Factory Methods:**

#### forId()

```php
public static function forId(string $id): self;
```

**Returns:** Exception with message "Audit log with ID [{$id}] not found"

**Example:**
```php
throw AuditLogNotFoundException::forId('01LOG123');
```

---

### InvalidAuditLevelException

**Location:** `src/Exceptions/InvalidAuditLevelException.php`

**Extends:** `\InvalidArgumentException`

**Purpose:** Thrown when audit level not 1-4

**Factory Methods:**

#### forLevel()

```php
public static function forLevel(int $level): self;
```

**Returns:** Exception with message "Invalid audit level [{$level}]. Must be 1-4"

---

### InvalidRetentionPolicyException

**Location:** `src/Exceptions/InvalidRetentionPolicyException.php`

**Extends:** `\InvalidArgumentException`

**Purpose:** Thrown when retention days < 1

**Factory Methods:**

#### forDays()

```php
public static function forDays(int $days): self;
```

**Returns:** Exception with message "Invalid retention period [{$days}]. Must be >= 1"

---

### MissingRequiredFieldException

**Location:** `src/Exceptions/MissingRequiredFieldException.php`

**Extends:** `\InvalidArgumentException`

**Purpose:** Thrown when required field is missing

**Factory Methods:**

#### forField()

```php
public static function forField(string $fieldName): self;
```

**Returns:** Exception with message "Required field [{$fieldName}] is missing"

---

## Usage Patterns

### Pattern 1: Basic Audit Logging

```php
use Nexus\AuditLogger\Services\AuditLogManager;

$auditManager->log(
    logName: 'user_login',
    description: 'User logged in successfully',
    causerType: 'User',
    causerId: '01USER123',
    level: 1, // Low
    ipAddress: request()->ip()
);
```

---

### Pattern 2: CRUD Operation with Before/After

```php
$auditManager->log(
    logName: 'invoice_updated',
    description: 'Invoice INV-2024-001 updated',
    subjectType: 'Invoice',
    subjectId: '01INVOICE123',
    causerType: 'User',
    causerId: '01USER456',
    properties: [
        'old' => ['status' => 'draft', 'amount' => 1000],
        'new' => ['status' => 'approved', 'amount' => 1200],
    ],
    level: 3 // High
);
```

---

### Pattern 3: Batch Operations

```php
$batchUuid = Str::uuid();

foreach ($users as $user) {
    $auditManager->log(
        logName: 'user_imported',
        description: "User {$user->name} imported",
        subjectType: 'User',
        subjectId: $user->id,
        batchUuid: $batchUuid,
        level: 2
    );
}
```

---

### Pattern 4: System Activities (No Causer)

```php
$auditManager->log(
    logName: 'automated_purge',
    description: 'Purged 100 expired audit logs',
    causerType: null, // System activity
    causerId: null,
    level: 2
);
```

---

### Pattern 5: High-Value Critical Activities

```php
$auditManager->log(
    logName: 'data_export',
    description: 'Customer data exported to CSV',
    subjectType: 'DataExport',
    subjectId: '01EXPORT123',
    causerType: 'User',
    causerId: '01USER789',
    properties: ['row_count' => 10000, 'format' => 'csv'],
    level: 4, // Critical
    tenantId: '01TENANT123'
);
```
