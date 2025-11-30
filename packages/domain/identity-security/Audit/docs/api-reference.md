# API Reference: Audit

## Interfaces

### AuditEngineInterface

**Location:** `src/Contracts/AuditEngineInterface.php`

**Purpose:** Main audit logging engine with dual-mode operations (synchronous and asynchronous)

**Methods:**

#### logSync()

```php
public function logSync(
    string $tenantId,
    string $entityId,
    string $action,
    AuditLevel $level,
    array $metadata = [],
    ?string $userId = null,
    bool $sign = false
): string;
```

**Description:** Logs an audit record synchronously, blocking until the record is persisted and hash chain is updated

**Parameters:**
- `$tenantId` (string) - Tenant identifier for multi-tenant isolation
- `$entityId` (string) - Entity being audited (invoice ID, user ID, etc.)
- `$action` (string) - Action performed (e.g., 'created', 'updated', 'deleted')
- `$level` (AuditLevel) - Severity level (Info, Warning, Critical)
- `$metadata` (array) - Additional context data
- `$userId` (string|null) - User who performed the action
- `$sign` (bool) - Whether to digitally sign the record (requires Nexus\Crypto)

**Returns:** `string` - Audit record ID (ULID)

**Throws:**
- `AuditStorageException` - When storage operation fails
- `HashChainException` - When hash chain generation fails
- `SignatureVerificationException` - When signature generation fails

**Example:**
```php
$recordId = $this->auditEngine->logSync(
    tenantId: 'tenant-123',
    entityId: 'invoice-456',
    action: 'approved',
    level: AuditLevel::Info,
    metadata: ['amount' => 1000, 'approver' => 'manager-789'],
    userId: 'user-001',
    sign: true
);
```

#### logAsync()

```php
public function logAsync(
    string $tenantId,
    string $entityId,
    string $action,
    AuditLevel $level,
    array $metadata = [],
    ?string $userId = null,
    bool $sign = false
): string;
```

**Description:** Logs an audit record asynchronously via queue, returning immediately without blocking

**Parameters:** Same as `logSync()`

**Returns:** `string` - Audit record ID (generated immediately, actual logging happens in background)

**Throws:** None (errors logged to queue system)

**Example:**
```php
$recordId = $this->auditEngine->logAsync(
    tenantId: 'tenant-123',
    entityId: 'user-456',
    action: 'logged_in',
    level: AuditLevel::Info,
    metadata: ['ip' => '192.168.1.1'],
    userId: 'user-456',
    sign: false
);
```

---

### AuditStorageInterface

**Location:** `src/Contracts/AuditStorageInterface.php`

**Purpose:** Defines storage operations for immutable audit records

**Methods:**

#### append()

```php
public function append(AuditRecordInterface $record): void;
```

**Description:** Appends an audit record to storage (append-only, no updates)

**Parameters:**
- `$record` (AuditRecordInterface) - Audit record to persist

**Returns:** `void`

**Throws:**
- `AuditStorageException` - When persistence fails

#### findByEntity()

```php
public function findByEntity(string $tenantId, string $entityId): array;
```

**Description:** Retrieves all audit records for a specific entity

**Parameters:**
- `$tenantId` (string) - Tenant identifier
- `$entityId` (string) - Entity identifier

**Returns:** `array<AuditRecordInterface>` - Array of audit records ordered by sequence

#### getLastRecordHash()

```php
public function getLastRecordHash(string $tenantId): ?string;
```

**Description:** Gets the hash of the last record in the chain for a tenant

**Parameters:**
- `$tenantId` (string) - Tenant identifier

**Returns:** `string|null` - SHA-256 hash or null if no records exist

#### search()

```php
public function search(string $tenantId, array $criteria): array;
```

**Description:** Searches audit records by criteria (action, user, date range, etc.)

**Parameters:**
- `$tenantId` (string) - Tenant identifier
- `$criteria` (array) - Search criteria

**Returns:** `array<AuditRecordInterface>` - Matching audit records

---

### AuditVerifierInterface

**Location:** `src/Contracts/AuditVerifierInterface.php`

**Purpose:** Verifies hash chain integrity and detects tampering

**Methods:**

#### verifyChainIntegrity()

```php
public function verifyChainIntegrity(string $tenantId): bool;
```

**Description:** Verifies the entire hash chain for a tenant

**Parameters:**
- `$tenantId` (string) - Tenant identifier

**Returns:** `bool` - True if chain is intact, false otherwise

**Throws:**
- `AuditTamperedException` - When tampering is detected
- `HashChainException` - When chain is broken

**Example:**
```php
try {
    $isValid = $this->verifier->verifyChainIntegrity('tenant-123');
    // Chain is intact
} catch (AuditTamperedException $e) {
    // Tampering detected - investigate!
}
```

#### verifyRecord()

```php
public function verifyRecord(AuditRecordInterface $record): bool;
```

**Description:** Verifies a single audit record's hash and signature

**Parameters:**
- `$record` (AuditRecordInterface) - Audit record to verify

**Returns:** `bool` - True if record is valid

**Throws:**
- `HashChainException` - When hash doesn't match
- `SignatureVerificationException` - When signature is invalid

---

### AuditSequenceManagerInterface

**Location:** `src/Contracts/AuditSequenceManagerInterface.php`

**Purpose:** Manages per-tenant monotonic sequence numbers

**Methods:**

#### getNextSequence()

```php
public function getNextSequence(string $tenantId): int;
```

**Description:** Gets the next sequence number for a tenant (atomic operation)

**Parameters:**
- `$tenantId` (string) - Tenant identifier

**Returns:** `int` - Next sequence number

**Throws:**
- `AuditSequenceException` - When sequence generation fails

#### detectGaps()

```php
public function detectGaps(string $tenantId): array;
```

**Description:** Detects missing sequence numbers in the audit chain

**Parameters:**
- `$tenantId` (string) - Tenant identifier

**Returns:** `array<int>` - Array of missing sequence numbers

**Example:**
```php
$gaps = $this->sequenceManager->detectGaps('tenant-123');
// Result: [15, 23, 24] - sequences 15, 23, 24 are missing
```

---

### AuditRecordInterface

**Location:** `src/Contracts/AuditRecordInterface.php`

**Purpose:** Represents an immutable audit record with hash chain fields

**Methods:**

#### getId()
```php
public function getId(): string;
```
Returns the unique audit record ID (ULID)

#### getTenantId()
```php
public function getTenantId(): string;
```
Returns the tenant identifier

#### getSequenceNumber()
```php
public function getSequenceNumber(): int;
```
Returns the monotonic sequence number

#### getEntityId()
```php
public function getEntityId(): string;
```
Returns the audited entity ID

#### getAction()
```php
public function getAction(): string;
```
Returns the action performed

#### getLevel()
```php
public function getLevel(): AuditLevel;
```
Returns the audit level (Info, Warning, Critical)

#### getPreviousHash()
```php
public function getPreviousHash(): ?AuditHash;
```
Returns the previous record's hash (null for first record)

#### getRecordHash()
```php
public function getRecordHash(): AuditHash;
```
Returns the current record's SHA-256 hash

#### getSignature()
```php
public function getSignature(): ?AuditSignature;
```
Returns the digital signature (null if unsigned)

#### getMetadata()
```php
public function getMetadata(): array;
```
Returns additional metadata

#### getUserId()
```php
public function getUserId(): ?string;
```
Returns the user who performed the action

#### getCreatedAt()
```php
public function getCreatedAt(): \DateTimeImmutable;
```
Returns the record creation timestamp

---

## Value Objects

### AuditHash

**Location:** `src/ValueObjects/AuditHash.php`

**Purpose:** Immutable SHA-256 hash value object

**Properties:**
- `value` (string, readonly) - 64-character SHA-256 hash

**Methods:**

#### __construct()
```php
public function __construct(
    public readonly string $value
)
```

**Validation:** Ensures value is 64-character hexadecimal string

**Example:**
```php
$hash = new AuditHash('a1b2c3d4e5f6...');
echo $hash->toString(); // "a1b2c3d4e5f6..."
```

#### toString()
```php
public function toString(): string
```
Returns the hash value as a string

---

### AuditSignature

**Location:** `src/ValueObjects/AuditSignature.php`

**Purpose:** Immutable Ed25519 digital signature

**Properties:**
- `signature` (string, readonly) - Base64-encoded Ed25519 signature
- `signedBy` (string, readonly) - Identifier of the signer

**Methods:**

#### __construct()
```php
public function __construct(
    public readonly string $signature,
    public readonly string $signedBy
)
```

**Validation:** Ensures signature is valid base64 and signedBy is not empty

**Example:**
```php
$sig = new AuditSignature(
    signature: 'SGVsbG8gV29ybGQ=',
    signedBy: 'user-123'
);
```

---

### SequenceNumber

**Location:** `src/ValueObjects/SequenceNumber.php`

**Purpose:** Immutable sequence number value object

**Properties:**
- `value` (int, readonly) - Positive integer sequence number

**Methods:**

#### __construct()
```php
public function __construct(
    public readonly int $value
)
```

**Validation:** Ensures value is positive (> 0)

#### increment()
```php
public function increment(): self
```
Returns a new instance with incremented value

**Example:**
```php
$seq = new SequenceNumber(1);
$next = $seq->increment(); // SequenceNumber(2)
```

---

### RetentionPolicy

**Location:** `src/ValueObjects/RetentionPolicy.php`

**Purpose:** Defines audit record retention rules

**Properties:**
- `retentionDays` (int, readonly) - Number of days to retain records

**Methods:**

#### __construct()
```php
public function __construct(
    public readonly int $retentionDays
)
```

**Validation:** Ensures retentionDays is positive

#### isExpired()
```php
public function isExpired(\DateTimeImmutable $recordDate): bool
```
Returns true if record is older than retention period

**Example:**
```php
$policy = new RetentionPolicy(retentionDays: 2555); // 7 years
$isExpired = $policy->isExpired($recordDate);
```

---

## Enums

### AuditLevel

**Location:** `src/ValueObjects/AuditLevel.php`

**Purpose:** Audit log severity levels

**Cases:**
- `Info` - Informational events (default)
- `Warning` - Warning events (potential issues)
- `Critical` - Critical events (security, compliance violations)

**Example:**
```php
$level = AuditLevel::Critical;

match ($level) {
    AuditLevel::Critical => alert('Critical audit event!'),
    AuditLevel::Warning => warn('Audit warning'),
    AuditLevel::Info => log('Audit info'),
};
```

---

## Exceptions

### AuditException

**Location:** `src/Exceptions/AuditException.php`

**Extends:** `\RuntimeException`

**Purpose:** Base exception for all audit-related errors

---

### AuditTamperedException

**Location:** `src/Exceptions/AuditTamperedException.php`

**Extends:** `AuditException`

**Purpose:** Thrown when hash chain tampering is detected

**Factory Methods:**

#### hashMismatch()
```php
public static function hashMismatch(string $recordId, string $expected, string $actual): self
```

**Example:**
```php
throw AuditTamperedException::hashMismatch(
    'audit-123',
    'expected-hash',
    'actual-hash'
);
```

---

### AuditSequenceException

**Location:** `src/Exceptions/AuditSequenceException.php`

**Extends:** `AuditException`

**Purpose:** Thrown when sequence integrity is violated

**Factory Methods:**

#### gapDetected()
```php
public static function gapDetected(string $tenantId, array $gaps): self
```

---

### HashChainException

**Location:** `src/Exceptions/HashChainException.php`

**Extends:** `AuditException`

**Purpose:** Thrown when hash chain is broken

**Factory Methods:**

#### brokenChain()
```php
public static function brokenChain(string $tenantId, int $sequenceNumber): self
```

---

### SignatureVerificationException

**Location:** `src/Exceptions/SignatureVerificationException.php`

**Extends:** `AuditException`

**Purpose:** Thrown when digital signature verification fails

**Factory Methods:**

#### invalidSignature()
```php
public static function invalidSignature(string $recordId): self
```

---

### AuditStorageException

**Location:** `src/Exceptions/AuditStorageException.php`

**Extends:** `AuditException`

**Purpose:** Thrown when storage operations fail

**Factory Methods:**

#### persistenceFailed()
```php
public static function persistenceFailed(string $reason): self
```

---

### InvalidRetentionPolicyException

**Location:** `src/Exceptions/InvalidRetentionPolicyException.php`

**Extends:** `AuditException`

**Purpose:** Thrown when retention policy is invalid

**Factory Methods:**

#### invalidDays()
```php
public static function invalidDays(int $days): self
```

---

## Services

### AuditEngine

**Location:** `src/Services/AuditEngine.php`

**Purpose:** Main audit logging service implementing AuditEngineInterface

**Constructor Dependencies:**
- `AuditStorageInterface` - Storage backend
- `AuditSequenceManagerInterface` - Sequence management
- `?CryptoManagerInterface` - Optional crypto for signatures

---

### HashChainVerifier

**Location:** `src/Services/HashChainVerifier.php`

**Purpose:** Verifies hash chain integrity and detects tampering

**Constructor Dependencies:**
- `AuditStorageInterface` - Storage backend
- `?CryptoManagerInterface` - Optional crypto for signature verification

---

### AuditSequenceManager

**Location:** `src/Services/AuditSequenceManager.php`

**Purpose:** Manages per-tenant monotonic sequences

**Constructor Dependencies:**
- `AuditStorageInterface` - Storage backend (for sequence persistence)

---

### RetentionPolicyService

**Location:** `src/Services/RetentionPolicyService.php`

**Purpose:** Enforces retention policies and purges expired records

**Constructor Dependencies:**
- `AuditStorageInterface` - Storage backend

---

## Usage Patterns

### Pattern 1: Critical Event Logging

For financial transactions, security events:

```php
$this->auditEngine->logSync(
    tenantId: $tenantId,
    entityId: $invoiceId,
    action: 'payment_received',
    level: AuditLevel::Critical,
    metadata: ['amount' => 1000, 'payment_method' => 'bank_transfer'],
    userId: $userId,
    sign: true // Enable signature
);
```

### Pattern 2: Bulk Event Logging

For user activity, background jobs:

```php
$this->auditEngine->logAsync(
    tenantId: $tenantId,
    entityId: $userId,
    action: 'profile_viewed',
    level: AuditLevel::Info,
    metadata: ['viewer_id' => $viewerId],
    userId: $userId,
    sign: false
);
```

### Pattern 3: Chain Verification

Periodic integrity checks:

```php
foreach ($tenants as $tenant) {
    try {
        $this->verifier->verifyChainIntegrity($tenant->getId());
    } catch (AuditTamperedException $e) {
        alert("Tampering detected for tenant {$tenant->getId()}");
    }
}
```

---

**This API reference covers all public interfaces, value objects, exceptions, and services in the Nexus\Audit package.**
