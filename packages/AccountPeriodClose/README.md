# Nexus\AccountPeriodClose

**Framework-Agnostic Period Close Engine**

[![PHP Version](https://img.shields.io/badge/php-%5E8.3-blue)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-MIT-green)](LICENSE)

## Overview

`Nexus\AccountPeriodClose` is a pure PHP package that provides the core engine for managing accounting period close processes. It handles close readiness validation, closing entry generation, retained earnings calculation, and period reopening controls. The package ensures proper month-end, quarter-end, and year-end close procedures.

This package is **framework-agnostic** and contains no database access, no HTTP controllers, and no framework-specific code. Consuming applications provide period data and execute the actual database operations through injected interfaces.

## Installation

```bash
composer require nexus/account-period-close
```

## Package Responsibilities

| Responsibility | Description |
|----------------|-------------|
| **Close Readiness Validation** | Verify all prerequisites are met before closing |
| **Closing Entry Generation** | Generate entries to close temporary accounts |
| **Retained Earnings Calculation** | Calculate and post retained earnings entries |
| **Reopen Validation** | Control when and how periods can be reopened |
| **Close Sequence Management** | Ensure periods close in proper order |
| **Year-End Processing** | Handle annual close with equity rollforward |

## Key Concepts

### Close Types

| Type | Scope | Actions |
|------|-------|---------|
| **Soft Close** | Period-level | Lock transactions, allow adjustments |
| **Hard Close** | Period-level | No changes allowed |
| **Year-End Close** | Annual | Close income/expense to retained earnings |
| **Interim Close** | Sub-period | Monthly/quarterly within fiscal year |

### Close Sequence

Periods must close in chronological order:
1. Earlier periods must close before later periods
2. All sub-periods must close before parent period
3. All subledgers must close before GL close

---

## Architecture

```
src/
├── Contracts/           # Interfaces defining the public API
├── ValueObjects/        # Immutable close process data structures
├── Enums/               # Close types, statuses, severities
├── Services/            # Core close process logic
├── Rules/               # Close validation rules
└── Exceptions/          # Domain-specific errors
```

---

## Contracts (Interfaces)

### Core Interfaces

#### `CloseReadinessValidatorInterface`

Validates that a period is ready to close.

```php
interface CloseReadinessValidatorInterface
{
    /**
     * Validate period close readiness
     *
     * @param string $tenantId
     * @param string $periodId
     * @return CloseReadinessResult
     */
    public function validate(string $tenantId, string $periodId): CloseReadinessResult;
    
    /**
     * Run a specific validation rule
     */
    public function runRule(CloseRuleInterface $rule, string $periodId): CloseCheckResult;
    
    /**
     * Get all registered validation rules
     *
     * @return array<CloseRuleInterface>
     */
    public function getRules(): array;
    
    /**
     * Check if period can be closed (all rules pass)
     */
    public function canClose(string $tenantId, string $periodId): bool;
}
```

#### `ClosingEntryGeneratorInterface`

Generates closing journal entries.

```php
interface ClosingEntryGeneratorInterface
{
    /**
     * Generate closing entries for a period
     *
     * @param string $tenantId
     * @param string $periodId
     * @param CloseType $closeType
     * @return array<ClosingEntrySpec>
     */
    public function generate(
        string $tenantId,
        string $periodId,
        CloseType $closeType = CloseType::HARD
    ): array;
    
    /**
     * Generate year-end closing entries
     * (Close revenue/expense accounts to retained earnings)
     */
    public function generateYearEndEntries(
        string $tenantId,
        string $fiscalYearId
    ): array;
    
    /**
     * Preview closing entries without posting
     */
    public function preview(
        string $tenantId,
        string $periodId
    ): array;
}
```

#### `ReopenValidatorInterface`

Validates and controls period reopening.

```php
interface ReopenValidatorInterface
{
    /**
     * Validate if a period can be reopened
     *
     * @param string $tenantId
     * @param string $periodId
     * @param ReopenRequest $request
     * @return ValidationResult
     */
    public function validate(
        string $tenantId,
        string $periodId,
        ReopenRequest $request
    ): ValidationResult;
    
    /**
     * Check if period is eligible for reopening
     */
    public function canReopen(string $tenantId, string $periodId): bool;
    
    /**
     * Get reopen restrictions for period
     */
    public function getRestrictions(string $periodId): array;
    
    /**
     * Verify reopen authorization
     */
    public function verifyAuthorization(
        string $userId,
        string $periodId
    ): bool;
}
```

#### `CloseSequenceInterface`

Manages the order of period closes.

```php
interface CloseSequenceInterface
{
    /**
     * Get the next period to close
     */
    public function getNextToClose(string $tenantId): ?string;
    
    /**
     * Verify period can close in sequence
     */
    public function verifySequence(
        string $tenantId,
        string $periodId
    ): bool;
    
    /**
     * Get periods that must close first
     *
     * @return array<string>
     */
    public function getPrerequisitePeriods(string $periodId): array;
    
    /**
     * Check if any dependent periods are open
     */
    public function hasDependentOpenPeriods(string $periodId): bool;
}
```

#### `CloseRuleInterface`

Contract for individual close validation rules.

```php
interface CloseRuleInterface
{
    /**
     * Get rule identifier
     */
    public function getId(): string;
    
    /**
     * Get rule name
     */
    public function getName(): string;
    
    /**
     * Get rule description
     */
    public function getDescription(): string;
    
    /**
     * Execute the validation rule
     */
    public function check(string $tenantId, string $periodId): CloseCheckResult;
    
    /**
     * Get severity if rule fails
     */
    public function getSeverity(): ValidationSeverity;
    
    /**
     * Can this rule be bypassed with override?
     */
    public function canBypass(): bool;
}
```

#### `CloseDataProviderInterface`

Contract for consuming applications to provide close data.

```php
interface CloseDataProviderInterface
{
    /**
     * Get period details
     */
    public function getPeriod(string $periodId): PeriodContext;
    
    /**
     * Get unposted entries count
     */
    public function getUnpostedEntriesCount(
        string $tenantId,
        string $periodId
    ): int;
    
    /**
     * Get trial balance status
     */
    public function isTrialBalanceBalanced(
        string $tenantId,
        string $periodId
    ): bool;
    
    /**
     * Get reconciliation status
     */
    public function getReconciliationStatus(
        string $tenantId,
        string $periodId
    ): array;
    
    /**
     * Get subledger close status
     */
    public function getSubledgerCloseStatus(
        string $tenantId,
        string $periodId
    ): array;
}
```

#### `PeriodContextInterface`

Provides period context information.

```php
interface PeriodContextInterface
{
    public function getPeriodId(): string;
    public function getTenantId(): string;
    public function getStartDate(): \DateTimeImmutable;
    public function getEndDate(): \DateTimeImmutable;
    public function getFiscalYearId(): string;
    public function getStatus(): CloseStatus;
    public function isYearEnd(): bool;
    public function getPriorPeriodId(): ?string;
}
```

---

## Value Objects

### `CloseReadinessResult`

```php
final readonly class CloseReadinessResult
{
    public function __construct(
        public string $periodId,
        public bool $isReady,
        public array $passedChecks,
        public array $failedChecks,
        public array $warnings,
        public \DateTimeImmutable $checkedAt
    ) {}
    
    public function canProceed(): bool
    {
        return $this->isReady || $this->onlyHasWarnings();
    }
    
    public function getBlockingIssues(): array
    {
        return array_filter(
            $this->failedChecks,
            fn($check) => $check->severity === ValidationSeverity::ERROR
        );
    }
}
```

### `CloseValidationIssue`

```php
final readonly class CloseValidationIssue
{
    public function __construct(
        public string $ruleId,
        public string $ruleName,
        public string $message,
        public ValidationSeverity $severity,
        public bool $canBypass,
        public ?string $resolution = null
    ) {}
}
```

### `ClosingEntrySpec`

```php
final readonly class ClosingEntrySpec
{
    public function __construct(
        public string $accountId,
        public string $accountCode,
        public string $accountName,
        public Money $debitAmount,
        public Money $creditAmount,
        public string $description,
        public string $closingType,
        public string $retainedEarningsAccountId
    ) {}
}
```

### `ReopenRequest`

```php
final readonly class ReopenRequest
{
    public function __construct(
        public string $periodId,
        public string $requestedBy,
        public ReopenReason $reason,
        public string $justification,
        public ?\DateTimeImmutable $requestedUntil = null,
        public ?string $approvedBy = null
    ) {}
}
```

### `CloseCheckResult`

```php
final readonly class CloseCheckResult
{
    public function __construct(
        public string $ruleId,
        public bool $passed,
        public string $message,
        public ValidationSeverity $severity,
        public array $details = []
    ) {}
    
    public static function pass(string $ruleId, string $message = 'Check passed'): self
    {
        return new self($ruleId, true, $message, ValidationSeverity::INFO);
    }
    
    public static function fail(string $ruleId, string $message, ValidationSeverity $severity): self
    {
        return new self($ruleId, false, $message, $severity);
    }
}
```

---

## Enums

### `CloseStatus`

```php
enum CloseStatus: string
{
    case OPEN = 'open';
    case SOFT_CLOSED = 'soft_closed';
    case HARD_CLOSED = 'hard_closed';
    case REOPENED = 'reopened';
}
```

### `CloseType`

```php
enum CloseType: string
{
    case SOFT = 'soft';           // Allow adjustments
    case HARD = 'hard';           // No changes allowed
    case YEAR_END = 'year_end';   // Annual close
    case INTERIM = 'interim';     // Sub-period close
}
```

### `ValidationSeverity`

```php
enum ValidationSeverity: string
{
    case ERROR = 'error';       // Blocks close
    case WARNING = 'warning';   // Proceed with caution
    case INFO = 'info';         // Informational only
}
```

### `ReopenReason`

```php
enum ReopenReason: string
{
    case CORRECTION = 'correction';           // Fix errors
    case ADJUSTMENT = 'adjustment';           // Late adjustments
    case AUDIT_FINDING = 'audit_finding';     // Auditor requirement
    case REGULATORY = 'regulatory';           // Regulatory requirement
    case SYSTEM_ERROR = 'system_error';       // Technical issue
}
```

---

## Services

### `CloseReadinessValidator`

Orchestrates readiness validation:
1. Runs all registered close rules
2. Aggregates results
3. Determines overall readiness
4. Provides resolution guidance

### `ClosingEntryGenerator`

Generates closing journal entries:
- Close revenue accounts to income summary
- Close expense accounts to income summary
- Close income summary to retained earnings
- Handle dividends accounts

### `ReopenValidator`

Controls period reopening:
- Verify authorization levels
- Check dependent period status
- Validate reason and justification
- Enforce reopen time limits

### `RetainedEarningsCalculator`

Calculates retained earnings:
- Beginning retained earnings
- Add: Net income (loss)
- Less: Dividends declared
- Equals: Ending retained earnings

### `EquityRollForwardGenerator`

Generates equity rollforward:
- Track each equity component
- Opening → Changes → Closing
- Comprehensive income items

### `YearEndCloseHandler`

Special year-end processing:
- Close all temporary accounts
- Post to retained earnings
- Create opening balances for new year
- Lock fiscal year

### `AdjustingEntryGenerator`

Generates period-end adjusting entries:
- Accruals (revenue/expense)
- Deferrals (prepaid/unearned)
- Depreciation
- Allowances

### `DeferredRevenueCalculator`

Calculates deferred revenue recognition:
- Revenue recognition schedules
- Deferred balance calculation
- Period-end adjustments

---

## Rules

### Built-in Close Rules

| Rule | Severity | Description |
|------|----------|-------------|
| `TrialBalanceMustBalanceRule` | ERROR | Trial balance debits must equal credits |
| `NoUnpostedEntriesRule` | ERROR | All entries must be posted |
| `ReconciliationCompleteRule` | WARNING | Bank reconciliations should be complete |
| `AllSubledgersClosedRule`* | ERROR | AR, AP, Inventory must be closed first |
| `WorkflowApprovalRule`* | ERROR | Required approvals obtained |

*Located in `orchestrators/AccountingOperations`

---

## Exceptions

| Exception | When Thrown |
|-----------|-------------|
| `PeriodCloseException` | General period close failure |
| `PeriodNotReadyException` | Validation rules not satisfied |
| `ReopenNotAllowedException` | Period cannot be reopened |
| `CloseSequenceException` | Periods closing out of order |

---

## Usage Example

```php
use Nexus\AccountPeriodClose\Contracts\CloseReadinessValidatorInterface;
use Nexus\AccountPeriodClose\Contracts\ClosingEntryGeneratorInterface;
use Nexus\AccountPeriodClose\Enums\CloseType;

final readonly class PeriodCloseService
{
    public function __construct(
        private CloseReadinessValidatorInterface $readinessValidator,
        private ClosingEntryGeneratorInterface $entryGenerator,
        private AuditLogManagerInterface $auditLogger
    ) {}
    
    public function closePeriod(string $tenantId, string $periodId): CloseResult
    {
        // Step 1: Validate readiness
        $readiness = $this->readinessValidator->validate($tenantId, $periodId);
        
        if (!$readiness->canProceed()) {
            throw new PeriodNotReadyException(
                $periodId,
                $readiness->getBlockingIssues()
            );
        }
        
        // Step 2: Generate closing entries
        $closingEntries = $this->entryGenerator->generate(
            tenantId: $tenantId,
            periodId: $periodId,
            closeType: CloseType::HARD
        );
        
        // Step 3: Post entries (delegated to consuming application)
        foreach ($closingEntries as $entry) {
            $this->postClosingEntry($entry);
        }
        
        // Step 4: Log the close event
        $this->auditLogger->log(
            entityId: $periodId,
            action: 'period_closed',
            description: "Period {$periodId} closed with " . count($closingEntries) . " entries"
        );
        
        return new CloseResult(
            periodId: $periodId,
            status: CloseStatus::HARD_CLOSED,
            entriesGenerated: count($closingEntries)
        );
    }
}
```

---

## Period Close Process Flow

```
┌─────────────────────────────────────────────────────────────┐
│                    PERIOD CLOSE PROCESS                      │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  1. READINESS VALIDATION                                     │
│     ├── Trial balance balanced?                              │
│     ├── All entries posted?                                  │
│     ├── Reconciliations complete?                            │
│     ├── Subledgers closed?                                   │
│     └── Approvals obtained?                                  │
│                                                              │
│  2. ADJUSTING ENTRIES (if needed)                            │
│     ├── Accruals                                             │
│     ├── Deferrals                                            │
│     ├── Depreciation                                         │
│     └── Allowances                                           │
│                                                              │
│  3. CLOSING ENTRY GENERATION                                 │
│     ├── Close revenue to income summary                      │
│     ├── Close expenses to income summary                     │
│     └── Close income summary to retained earnings            │
│                                                              │
│  4. POST CLOSING ENTRIES                                     │
│     └── Post all generated entries                           │
│                                                              │
│  5. LOCK PERIOD                                              │
│     ├── Set status to HARD_CLOSED                            │
│     └── Prevent further transactions                         │
│                                                              │
│  6. YEAR-END (if applicable)                                 │
│     ├── Calculate retained earnings                          │
│     ├── Create opening balances                              │
│     └── Roll forward equity                                  │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

---

## Integration with Other Packages

| Package | Integration |
|---------|-------------|
| `Nexus\Finance` | Provides GL data, posts closing entries |
| `Nexus\Period` | Provides period definitions and status |
| `Nexus\FinancialStatements` | Statement generation on close |
| `Nexus\AuditLogger` | Log close events |
| `Nexus\Workflow` | Approval workflows for close |

---

## Related Documentation

- [ARCHITECTURE.md](../../ARCHITECTURE.md) - Overall system architecture
- [CODING_GUIDELINES.md](../../CODING_GUIDELINES.md) - Coding standards
- [Nexus Packages Reference](../../docs/NEXUS_PACKAGES_REFERENCE.md) - All available packages

---

## License

MIT License - See [LICENSE](LICENSE) for details.
