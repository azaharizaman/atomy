# API Reference: Compliance

Complete API documentation for the Nexus\Compliance package.

## Table of Contents

- [Interfaces](#interfaces)
  - [ComplianceManagerInterface](#compliancemanagerinterface)
  - [ComplianceSchemeInterface](#complianceschemeinterface)
  - [ComplianceSchemeRepositoryInterface](#complianceschemerepositoryinterface)
  - [SodManagerInterface](#sodmanagerinterface)
  - [SodRuleInterface](#sodruleinterface)
  - [SodRuleRepositoryInterface](#sodrulerepositoryinterface)
  - [SodViolationInterface](#sodviolationinterface)
  - [SodViolationRepositoryInterface](#sodviolationrepositoryinterface)
- [Services](#services)
  - [ComplianceManager](#compliancemanager)
  - [SodManager](#sodmanager)
  - [ConfigurationAuditor](#configurationauditor)
- [Value Objects](#value-objects)
  - [SeverityLevel](#severitylevel)
- [Exceptions](#exceptions)

---

## Interfaces

### ComplianceManagerInterface

**Namespace:** `Nexus\Compliance\Contracts`

Main service interface for compliance scheme lifecycle management.

#### Methods

##### `activateScheme`

Activate a compliance scheme for a tenant.

```php
public function activateScheme(
    string $tenantId,
    string $schemeName,
    array $configuration = []
): string;
```

**Parameters:**
- `$tenantId` (string) - Tenant identifier
- `$schemeName` (string) - Scheme name (ISO14001, SOX, GDPR, HIPAA, PCI_DSS)
- `$configuration` (array) - Optional scheme-specific configuration

**Returns:** string - Activated scheme ID

**Throws:**
- `InvalidSchemeException` - If scheme name is invalid
- `SchemeAlreadyActiveException` - If scheme already active for tenant
- `InvalidArgumentException` - If configuration fails validation

**Example:**
```php
$schemeId = $complianceManager->activateScheme(
    tenantId: 'tenant-123',
    schemeName: 'ISO14001',
    configuration: ['audit_frequency' => 'quarterly']
);
```

---

##### `deactivateScheme`

Deactivate an active compliance scheme.

```php
public function deactivateScheme(
    string $tenantId,
    string $schemeName
): void;
```

**Parameters:**
- `$tenantId` (string) - Tenant identifier
- `$schemeName` (string) - Scheme name to deactivate

**Throws:**
- `SchemeNotFoundException` - If scheme not found or not active

**Example:**
```php
$complianceManager->deactivateScheme('tenant-123', 'ISO14001');
```

---

##### `isSchemeActive`

Check if a compliance scheme is active for a tenant.

```php
public function isSchemeActive(
    string $tenantId,
    string $schemeName
): bool;
```

**Parameters:**
- `$tenantId` (string) - Tenant identifier
- `$schemeName` (string) - Scheme name to check

**Returns:** bool - True if scheme is active

**Example:**
```php
if ($complianceManager->isSchemeActive('tenant-123', 'SOX')) {
    // SOX compliance is active
}
```

---

##### `getActiveSchemes`

Get all active compliance schemes for a tenant.

```php
public function getActiveSchemes(string $tenantId): array;
```

**Parameters:**
- `$tenantId` (string) - Tenant identifier

**Returns:** array - Array of ComplianceSchemeInterface objects

**Example:**
```php
$activeSchemes = $complianceManager->getActiveSchemes('tenant-123');
foreach ($activeSchemes as $scheme) {
    echo $scheme->getSchemeName();
}
```

---

##### `getSchemeConfiguration`

Get the configuration of an active scheme.

```php
public function getSchemeConfiguration(
    string $tenantId,
    string $schemeName
): array;
```

**Parameters:**
- `$tenantId` (string) - Tenant identifier
- `$schemeName` (string) - Scheme name

**Returns:** array - Scheme configuration

**Throws:**
- `SchemeNotFoundException` - If scheme not active

---

### ComplianceSchemeInterface

**Namespace:** `Nexus\Compliance\Contracts`

Entity interface for compliance schemes.

#### Methods

```php
public function getId(): string;
public function getTenantId(): string;
public function getSchemeName(): string;
public function isActive(): bool;
public function getConfiguration(): array;
public function activate(array $configuration): void;
public function deactivate(): void;
```

---

### ComplianceSchemeRepositoryInterface

**Namespace:** `Nexus\Compliance\Contracts`

Repository interface for compliance scheme persistence.

#### Methods

##### `findById`

```php
public function findById(string $id): ?ComplianceSchemeInterface;
```

##### `findByTenantAndName`

```php
public function findByTenantAndName(
    string $tenantId,
    string $schemeName
): ?ComplianceSchemeInterface;
```

##### `save`

```php
public function save(ComplianceSchemeInterface $scheme): void;
```

##### `findActiveSchemesForTenant`

```php
public function findActiveSchemesForTenant(string $tenantId): array;
```

---

### SodManagerInterface

**Namespace:** `Nexus\Compliance\Contracts`

Main service interface for SOD rule management and violation checking.

#### Methods

##### `createRule`

Create a new SOD rule.

```php
public function createRule(
    string $tenantId,
    string $ruleName,
    string $transactionType,
    SeverityLevel $severityLevel,
    string $creatorRole,
    string $approverRole
): string;
```

**Parameters:**
- `$tenantId` (string) - Tenant identifier
- `$ruleName` (string) - Descriptive name for the rule
- `$transactionType` (string) - Type of transaction (e.g., 'invoice_approval')
- `$severityLevel` (SeverityLevel) - Severity level enum
- `$creatorRole` (string) - Role that creates the transaction
- `$approverRole` (string) - Role that approves (must be different from creator)

**Returns:** string - Created rule ID

**Throws:**
- `DuplicateRuleException` - If rule already exists
- `InvalidArgumentException` - If creatorRole == approverRole

**Example:**
```php
$ruleId = $sodManager->createRule(
    tenantId: 'tenant-123',
    ruleName: 'Purchase Order Approval',
    transactionType: 'purchase_order',
    severityLevel: SeverityLevel::CRITICAL,
    creatorRole: 'purchaser',
    approverRole: 'manager'
);
```

---

##### `validateTransaction`

Validate a transaction for SOD violations.

```php
public function validateTransaction(
    string $tenantId,
    string $transactionType,
    string $creatorId,
    string $approverId
): void;
```

**Parameters:**
- `$tenantId` (string) - Tenant identifier
- `$transactionType` (string) - Type of transaction
- `$creatorId` (string) - User ID of creator
- `$approverId` (string) - User ID of approver

**Throws:**
- `SodViolationException` - If SOD violation detected

**Example:**
```php
try {
    $sodManager->validateTransaction(
        tenantId: 'tenant-123',
        transactionType: 'invoice_approval',
        creatorId: 'user-001',
        approverId: 'user-001' // Same user - violation!
    );
} catch (SodViolationException $e) {
    // Handle violation
}
```

---

##### `getViolations`

Get all SOD violations within a date range.

```php
public function getViolations(
    string $tenantId,
    \DateTimeImmutable $from,
    \DateTimeImmutable $to
): array;
```

**Parameters:**
- `$tenantId` (string) - Tenant identifier
- `$from` (DateTimeImmutable) - Start date
- `$to` (DateTimeImmutable) - End date

**Returns:** array - Array of SodViolationInterface objects

**Example:**
```php
$violations = $sodManager->getViolations(
    tenantId: 'tenant-123',
    from: new \DateTimeImmutable('2025-01-01'),
    to: new \DateTimeImmutable('2025-12-31')
);
```

---

##### `getActiveRules`

Get all active SOD rules for a tenant.

```php
public function getActiveRules(string $tenantId): array;
```

**Returns:** array - Array of SodRuleInterface objects

---

##### `deactivateRule`

Deactivate a SOD rule.

```php
public function deactivateRule(string $ruleId): void;
```

**Throws:**
- `RuleNotFoundException` - If rule not found

---

### SodRuleInterface

**Namespace:** `Nexus\Compliance\Contracts`

Entity interface for SOD rules.

#### Methods

```php
public function getId(): string;
public function getTenantId(): string;
public function getRuleName(): string;
public function getTransactionType(): string;
public function getSeverityLevel(): SeverityLevel;
public function getCreatorRole(): string;
public function getApproverRole(): string;
public function isActive(): bool;
public function deactivate(): void;
```

---

### SodRuleRepositoryInterface

**Namespace:** `Nexus\Compliance\Contracts`

Repository interface for SOD rule persistence.

#### Methods

```php
public function findById(string $id): ?SodRuleInterface;
public function findByTenantAndType(string $tenantId, string $transactionType): array;
public function save(SodRuleInterface $rule): void;
public function findActiveRulesForTenant(string $tenantId): array;
```

---

### SodViolationInterface

**Namespace:** `Nexus\Compliance\Contracts`

Entity interface for SOD violations.

#### Methods

```php
public function getId(): string;
public function getTenantId(): string;
public function getRuleId(): string;
public function getTransactionId(): string;
public function getTransactionType(): string;
public function getCreatorId(): string;
public function getApproverId(): string;
public function getViolatedAt(): \DateTimeImmutable;
```

---

### SodViolationRepositoryInterface

**Namespace:** `Nexus\Compliance\Contracts`

Repository interface for SOD violation persistence.

#### Methods

```php
public function save(SodViolationInterface $violation): void;
public function findByTenantAndDateRange(
    string $tenantId,
    \DateTimeImmutable $from,
    \DateTimeImmutable $to
): array;
```

---

## Services

### ComplianceManager

**Namespace:** `Nexus\Compliance\Services`

Concrete implementation of ComplianceManagerInterface.

**Dependencies (Constructor):**
```php
public function __construct(
    private readonly ComplianceSchemeRepositoryInterface $schemeRepository,
    private readonly ConfigurationAuditorInterface $configurationAuditor,
    private readonly ?AuditLogManagerInterface $auditLogger = null
) {}
```

**See:** [ComplianceManagerInterface](#compliancemanagerinterface) for method documentation.

---

### SodManager

**Namespace:** `Nexus\Compliance\Services`

Concrete implementation of SodManagerInterface.

**Dependencies (Constructor):**
```php
public function __construct(
    private readonly SodRuleRepositoryInterface $ruleRepository,
    private readonly SodViolationRepositoryInterface $violationRepository,
    private readonly ?AuditLogManagerInterface $auditLogger = null
) {}
```

**See:** [SodManagerInterface](#sodmanagerinterface) for method documentation.

---

### ConfigurationAuditor

**Namespace:** `Nexus\Compliance\Services`

Service for validating compliance scheme configurations.

**Dependencies (Constructor):**
```php
public function __construct(
    private readonly SettingsManagerInterface $settings,
    private readonly FeatureFlagsInterface $featureFlags
) {}
```

#### Methods

##### `audit`

Audit the configuration for a compliance scheme.

```php
public function audit(
    string $tenantId,
    string $schemeName
): array;
```

**Parameters:**
- `$tenantId` (string) - Tenant identifier
- `$schemeName` (string) - Scheme name

**Returns:** array - Audit violations (empty if all checks pass)

**Example:**
```php
$violations = $configurationAuditor->audit('tenant-123', 'ISO14001');
if (empty($violations)) {
    // Configuration is valid
}
```

---

## Value Objects

### SeverityLevel

**Namespace:** `Nexus\Compliance\ValueObjects`

Native PHP enum for severity levels.

```php
enum SeverityLevel: string
{
    case LOW = 'low';
    case MEDIUM = 'medium';
    case HIGH = 'high';
    case CRITICAL = 'critical';
    
    public function getPriority(): int
    {
        return match($this) {
            self::LOW => 1,
            self::MEDIUM => 2,
            self::HIGH => 3,
            self::CRITICAL => 4,
        };
    }
    
    public function requiresImmediateAction(): bool
    {
        return $this === self::CRITICAL || $this === self::HIGH;
    }
}
```

**Example:**
```php
use Nexus\Compliance\ValueObjects\SeverityLevel;

$level = SeverityLevel::CRITICAL;
echo $level->value; // 'critical'
echo $level->getPriority(); // 4
echo $level->requiresImmediateAction(); // true
```

---

## Exceptions

All exceptions extend the base PHP `Exception` class and are in the `Nexus\Compliance\Exceptions` namespace.

### DuplicateRuleException

Thrown when attempting to create a SOD rule that already exists.

```php
throw new DuplicateRuleException(
    "SOD rule already exists for transaction type 'invoice_approval'"
);
```

---

### InvalidSchemeException

Thrown when scheme validation fails.

```php
throw new InvalidSchemeException(
    "Invalid compliance scheme: INVALID_SCHEME"
);
```

---

### RuleNotFoundException

Thrown when a SOD rule is not found.

```php
throw new RuleNotFoundException(
    "SOD rule not found: rule-123"
);
```

---

### SchemeAlreadyActiveException

Thrown when attempting to activate an already active scheme.

```php
throw new SchemeAlreadyActiveException(
    "Compliance scheme 'ISO14001' is already active for tenant 'tenant-123'"
);
```

---

### SchemeNotFoundException

Thrown when a compliance scheme is not found.

```php
throw new SchemeNotFoundException(
    "Compliance scheme not found: ISO14001"
);
```

---

### SodViolationException

Thrown when a SOD violation is detected.

```php
throw new SodViolationException(
    "SOD violation: user-001 cannot approve transaction created by themselves"
);
```

---

## Usage Patterns

### Pattern 1: Check Scheme Before Operation

```php
if (!$complianceManager->isSchemeActive($tenantId, 'SOX')) {
    throw new \Exception("SOX compliance required for this operation");
}

// Proceed with operation
```

### Pattern 2: Validate SOD Before Approval

```php
public function approveTransaction(string $transactionId, string $approverId): void
{
    $transaction = $this->repository->findById($transactionId);
    
    // SOD validation
    $this->sodManager->validateTransaction(
        tenantId: $transaction->getTenantId(),
        transactionType: $transaction->getType(),
        creatorId: $transaction->getCreatedBy(),
        approverId: $approverId
    );
    
    // Proceed with approval
    $transaction->approve($approverId);
}
```

### Pattern 3: Periodic Violation Reporting

```php
public function generateComplianceReport(string $tenantId): array
{
    $activeSchemes = $complianceManager->getActiveSchemes($tenantId);
    $violations = $sodManager->getViolations(
        $tenantId,
        new \DateTimeImmutable('-30 days'),
        new \DateTimeImmutable()
    );
    
    return [
        'active_schemes' => array_map(fn($s) => $s->getSchemeName(), $activeSchemes),
        'violation_count' => count($violations),
        'violations' => $violations,
    ];
}
```

---

## See Also

- **[Getting Started Guide](getting-started.md)** - Setup and basic usage
- **[Integration Guide](integration-guide.md)** - Laravel and Symfony integration
- **[Basic Examples](examples/basic-usage.php)** - Working code examples
- **[Advanced Examples](examples/advanced-usage.php)** - Complex scenarios
