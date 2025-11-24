# API Reference: CashManagement

This document provides comprehensive documentation of all interfaces, value objects, enums, and exceptions in the `Nexus\CashManagement` package.

---

## Interfaces

### CashManagementManagerInterface

**Location:** `src/Contracts/CashManagementManagerInterface.php`

**Purpose:** Main orchestrator for cash management operations.

**Methods:**

#### createBankAccount()

```php
public function createBankAccount(
    string $tenantId,
    string $accountCode,
    string $glAccountId,
    string $accountNumber,
    string $bankName,
    string $bankCode,
    BankAccountType $accountType,
    string $currency,
    ?array $csvImportConfig = null
): BankAccountInterface;
```

**Description:** Creates a new bank account with validation.

**Parameters:**
- `$tenantId` (string) - Tenant identifier
- `$accountCode` (string) - Unique account code (e.g., "1000-01")
- `$glAccountId` (string) - GL account linked to this bank account
- `$accountNumber` (string) - Bank account number
- `$bankName` (string) - Bank name (e.g., "Maybank")
- `$bankCode` (string) - Bank code (e.g., "MBB")
- `$accountType` (BankAccountType) - Account type enum
- `$currency` (string) - Currency code (e.g., "MYR")
- `$csvImportConfig` (array|null) - CSV column mapping configuration

**Returns:** `BankAccountInterface` - Created bank account

**Throws:**
- `InvalidArgumentException` - If validation fails

**Example:**
```php
$bankAccount = $cashManager->createBankAccount(
    tenantId: $tenantId,
    accountCode: '1000-01',
    glAccountId: $cashAccountGlId,
    accountNumber: '1234567890',
    bankName: 'Maybank',
    bankCode: 'MBB',
    accountType: BankAccountType::CHECKING,
    currency: 'MYR',
    csvImportConfig: [
        'date_column' => 'Transaction Date',
        'description_column' => 'Description',
        'debit_column' => 'Debit',
        'credit_column' => 'Credit',
        'balance_column' => 'Balance'
    ]
);
```

#### importBankStatement()

```php
public function importBankStatement(
    string $bankAccountId,
    string $startDate,
    string $endDate,
    array $transactions,
    string $importedBy
): BankStatementInterface;
```

**Description:** Imports a bank statement from parsed CSV data.

**Parameters:**
- `$bankAccountId` (string) - Bank account ID
- `$startDate` (string) - Statement start date (YYYY-MM-DD)
- `$endDate` (string) - Statement end date (YYYY-MM-DD)
- `$transactions` (array) - Array of transaction data from CSV
- `$importedBy` (string) - User ID who imported

**Returns:** `BankStatementInterface` - Created bank statement

**Throws:**
- `DuplicateStatementException` - If statement already imported
- `PartialOverlapException` - If statement period overlaps existing
- `InvalidStatementFormatException` - If data format invalid

#### reconcileStatement()

```php
public function reconcileStatement(string $statementId): ReconciliationResultInterface;
```

**Description:** Automatically reconciles bank statement transactions.

**Parameters:**
- `$statementId` (string) - Bank statement ID

**Returns:** `ReconciliationResultInterface` - Reconciliation results

#### postPendingAdjustment()

```php
public function postPendingAdjustment(
    string $pendingAdjustmentId,
    string $glAccount,
    string $postedBy
): string;
```

**Description:** Posts a pending adjustment to GL (requires manual approval).

**Parameters:**
- `$pendingAdjustmentId` (string) - Pending adjustment ID
- `$glAccount` (string) - GL account code to post to
- `$postedBy` (string) - User ID who approved

**Returns:** `string` - Journal entry ID

**Throws:**
- `ReconciliationException` - If posting fails

#### rejectPendingAdjustment()

```php
public function rejectPendingAdjustment(
    string $pendingAdjustmentId,
    string $reason,
    string $rejectedBy
): void;
```

**Description:** Rejects a pending adjustment and triggers reversal workflow.

**Parameters:**
- `$pendingAdjustmentId` (string) - Pending adjustment ID
- `$reason` (string) - Rejection reason
- `$rejectedBy` (string) - User ID who rejected

**Throws:**
- `ReversalRequiredException` - If reversal prerequisites not met

#### updateBankAccountStatus()

```php
public function updateBankAccountStatus(
    string $bankAccountId,
    BankAccountStatus $status
): void;
```

**Description:** Updates bank account status (active, inactive, closed, suspended).

---

### ReconciliationEngineInterface

**Location:** `src/Contracts/ReconciliationEngineInterface.php`

**Purpose:** Core engine for automatic transaction matching.

**Methods:**

#### reconcileStatement()

```php
public function reconcileStatement(
    string $statementId,
    ?ReconciliationTolerance $tolerance = null
): ReconciliationResultInterface;
```

**Description:** Reconciles all transactions in a bank statement.

**Parameters:**
- `$statementId` (string) - Statement ID
- `$tolerance` (ReconciliationTolerance|null) - Optional tolerance settings

**Returns:** `ReconciliationResultInterface` - Results with matched/unmatched counts

#### matchTransaction()

```php
public function matchTransaction(
    string $bankTransactionId,
    ?ReconciliationTolerance $tolerance = null
): ?ReconciliationInterface;
```

**Description:** Attempts to match a single bank transaction.

**Returns:** `ReconciliationInterface|null` - Match if found, null otherwise

#### findPotentialMatches()

```php
public function findPotentialMatches(
    string $bankTransactionId,
    int $limit = 10
): array;
```

**Description:** Finds potential matches for manual review.

**Returns:** `array<ReconciliationInterface>` - Array of potential matches

---

### ReconciliationResultInterface

**Location:** `src/Contracts/ReconciliationResultInterface.php`

**Purpose:** Represents reconciliation results.

**Methods:**

```php
public function getMatchedCount(): int;
public function getUnmatchedCount(): int;
public function getVarianceCount(): int;
public function getTotalCount(): int;
public function getMatchedTransactions(): array;
public function getUnmatchedTransactions(): array;
```

---

### DuplicationDetectorInterface

**Location:** `src/Contracts/DuplicationDetectorInterface.php`

**Purpose:** Detects duplicate and overlapping statements.

**Methods:**

#### isDuplicate()

```php
public function isDuplicate(StatementHash $hash): bool;
```

**Description:** Checks if statement with same hash already exists.

#### checkOverlap()

```php
public function checkOverlap(
    string $bankAccountId,
    \DateTimeImmutable $startDate,
    \DateTimeImmutable $endDate
): array;
```

**Description:** Checks for partially overlapping statements.

**Returns:** `array` - List of overlapping statement IDs

**Throws:**
- `PartialOverlapException` - If overlap detected

---

### ReversalHandlerInterface

**Location:** `src/Contracts/ReversalHandlerInterface.php`

**Purpose:** Handles automatic reversal of reconciliations.

**Methods:**

#### reversePaymentApplication()

```php
public function reversePaymentApplication(
    string $paymentApplicationId,
    string $reconciliationId,
    string $reason
): void;
```

**Description:** Reverses a payment application when reconciliation is rejected.

---

### CashFlowForecastInterface

**Location:** `src/Contracts/CashFlowForecastInterface.php`

**Purpose:** Generates cash flow forecasts.

**Methods:**

#### forecast()

```php
public function forecast(
    string $tenantId,
    ScenarioParametersVO $parameters,
    ?string $bankAccountId = null
): ForecastResultVO;
```

**Description:** Generates cash flow forecast for specified scenario.

**Parameters:**
- `$tenantId` (string) - Tenant ID
- `$parameters` (ScenarioParametersVO) - Forecast parameters
- `$bankAccountId` (string|null) - Specific account (null for consolidated)

**Returns:** `ForecastResultVO` - Forecast results with daily balances

---

### CashPositionInterface

**Location:** `src/Contracts/CashPositionInterface.php`

**Purpose:** Tracks cash position at specific dates.

**Methods:**

```php
public function getBalanceAt(string $bankAccountId, \DateTimeImmutable $date): string;
public function getConsolidatedBalance(string $tenantId, \DateTimeImmutable $date): string;
```

---

## Repository Interfaces

All repositories follow standard patterns:

### BankAccountRepositoryInterface
```php
public function findById(string $id): BankAccountInterface;
public function findByTenant(string $tenantId): array;
public function save(BankAccountInterface $bankAccount): void;
public function delete(string $id): void;
```

### BankStatementRepositoryInterface
```php
public function findById(string $id): BankStatementInterface;
public function findByBankAccount(string $bankAccountId): array;
public function save(BankStatementInterface $statement): void;
```

### BankTransactionRepositoryInterface
```php
public function findById(string $id): BankTransactionInterface;
public function findByStatement(string $statementId): array;
public function save(BankTransactionInterface $transaction): void;
```

### ReconciliationRepositoryInterface
```php
public function findById(string $id): ReconciliationInterface;
public function findByBankTransaction(string $bankTransactionId): ?ReconciliationInterface;
public function save(ReconciliationInterface $reconciliation): void;
```

### PendingAdjustmentRepositoryInterface
```php
public function findById(string $id): PendingAdjustmentInterface;
public function findByTenant(string $tenantId): array;
public function markAsPosted(string $id, string $journalEntryId, string $postedBy): void;
public function save(PendingAdjustmentInterface $adjustment): void;
```

---

## Value Objects

### BankAccountNumber

**Location:** `src/ValueObjects/BankAccountNumber.php`

**Purpose:** Immutable representation of bank account number with validation.

**Constructor:**
```php
public function __construct(
    private string $accountNumber,
    private string $bankCode,
    private ?string $branchCode = null,
    private ?string $swiftCode = null,
    private ?string $iban = null
)
```

**Validation Rules:**
- Account number and bank code cannot be empty
- IBAN must match standard format (if provided)
- SWIFT code must be 8 or 11 characters (if provided)

**Example:**
```php
$accountNumber = new BankAccountNumber(
    accountNumber: '1234567890',
    bankCode: 'MBB',
    branchCode: '0123',
    swiftCode: 'MBBEMYKL',
    iban: null
);
```

---

### StatementPeriod

**Location:** `src/ValueObjects/StatementPeriod.php`

**Purpose:** Represents a date range with overlap detection.

**Methods:**
```php
public function overlaps(StatementPeriod $other): bool;
public function contains(\DateTimeImmutable $date): bool;
public function getStartDate(): \DateTimeImmutable;
public function getEndDate(): \DateTimeImmutable;
public function getDays(): int;
```

---

### ReconciliationTolerance

**Location:** `src/ValueObjects/ReconciliationTolerance.php`

**Purpose:** Defines tolerance thresholds for matching.

**Properties:**
```php
public readonly string $amountTolerance;  // e.g., "0.01"
public readonly int $dateTolerance;       // days (e.g., 3)
```

**Methods:**
```php
public function isAmountWithinTolerance(string $amount1, string $amount2): bool;
public function isDateWithinTolerance(\DateTimeImmutable $date1, \DateTimeImmutable $date2): bool;
```

---

### StatementHash

**Location:** `src/ValueObjects/StatementHash.php`

**Purpose:** Cryptographic hash for duplicate detection.

**Factory Method:**
```php
public static function create(
    string $bankAccountId,
    \DateTimeImmutable $startDate,
    \DateTimeImmutable $endDate,
    string $totalDebit,
    string $totalCredit
): self;
```

**Implementation:** Uses SHA-256 hashing of concatenated values.

---

### ScenarioParametersVO

**Location:** `src/ValueObjects/ScenarioParametersVO.php`

**Purpose:** Forecast scenario parameters.

**Factory Method:**
```php
public static function fromScenarioType(
    ForecastScenarioType $scenarioType,
    int $horizonDays
): self;
```

**Properties:**
- Horizon days
- Growth rate assumptions
- Collection/payment patterns
- Risk factors

---

### ForecastResultVO

**Location:** `src/ValueObjects/ForecastResultVO.php`

**Purpose:** Forecast results with daily balances.

**Methods:**
```php
public function getDailyBalances(): array;
public function getMinBalance(): string;
public function getMaxBalance(): string;
public function hasNegativeBalance(): bool;
public function getFirstNegativeDate(): ?\DateTimeImmutable;
```

---

### AIModelVersion

**Location:** `src/ValueObjects/AIModelVersion.php`

**Purpose:** Semantic versioning for AI models.

**Factory Method:**
```php
public static function fromString(string $version): self; // e.g., "1.2.3"
```

**Methods:**
```php
public function toString(): string;
public function getMajor(): int;
public function getMinor(): int;
public function getPatch(): int;
```

---

## Enums

### BankAccountType

**Location:** `src/Enums/BankAccountType.php`

**Cases:**
- `CHECKING` - Checking Account
- `SAVINGS` - Savings Account
- `CREDIT_CARD` - Credit Card
- `MONEY_MARKET` - Money Market
- `LINE_OF_CREDIT` - Line of Credit

**Methods:**
```php
public function label(): string; // Human-readable label
```

---

### BankAccountStatus

**Location:** `src/Enums/BankAccountStatus.php`

**Cases:**
- `ACTIVE` - Active account
- `INACTIVE` - Temporarily inactive
- `CLOSED` - Permanently closed
- `SUSPENDED` - Suspended (compliance/fraud)

---

### BankTransactionType

**Location:** `src/Enums/BankTransactionType.php`

**Cases:**
- `DEPOSIT` - Deposit
- `WITHDRAWAL` - Withdrawal
- `TRANSFER` - Transfer
- `FEE` - Bank fee
- `INTEREST` - Interest earned/charged
- `CHECK` - Check payment
- `ATM` - ATM transaction
- `DIRECT_DEBIT` - Direct debit
- `DIRECT_CREDIT` - Direct credit
- `REVERSAL` - Reversal
- `OTHER` - Other

---

### ReconciliationStatus

**Location:** `src/Enums/ReconciliationStatus.php`

**Cases:**
- `PENDING` - Awaiting reconciliation
- `MATCHED` - Automatically matched
- `VARIANCE_REVIEW` - Requires variance review
- `RECONCILED` - Manually reconciled
- `UNMATCHED` - No match found
- `REJECTED` - Rejected (requires reversal)

---

### MatchingConfidence

**Location:** `src/Enums/MatchingConfidence.php`

**Cases:**
- `HIGH` - High confidence (auto-match)
- `MEDIUM` - Medium confidence (review recommended)
- `LOW` - Low confidence (manual review required)
- `MANUAL` - Manual match

---

### ForecastScenarioType

**Location:** `src/Enums/ForecastScenarioType.php`

**Cases:**
- `OPTIMISTIC` - Best case scenario
- `BASELINE` - Most likely scenario
- `PESSIMISTIC` - Worst case scenario
- `CUSTOM` - Custom scenario

---

## Exceptions

### DuplicateStatementException

**Location:** `src/Exceptions/DuplicateStatementException.php`

**Extends:** `RuntimeException`

**Purpose:** Thrown when attempting to import duplicate statement.

**Factory Method:**
```php
public static function withHash(string $hash, string $existingId): self;
```

**Example:**
```php
throw DuplicateStatementException::withHash($hash, $existingStatementId);
```

---

### PartialOverlapException

**Location:** `src/Exceptions/PartialOverlapException.php`

**Purpose:** Thrown when statement period overlaps existing statement.

**Factory Method:**
```php
public static function withDetails(
    array $overlappingStatements,
    \DateTimeImmutable $newStartDate,
    \DateTimeImmutable $newEndDate
): self;
```

---

### ReconciliationException

**Location:** `src/Exceptions/ReconciliationException.php`

**Purpose:** General reconciliation errors.

---

### ReversalRequiredException

**Location:** `src/Exceptions/ReversalRequiredException.php`

**Purpose:** Thrown when reversal prerequisites not met.

---

### BankAccountNotFoundException

**Location:** `src/Exceptions/BankAccountNotFoundException.php`

**Purpose:** Thrown when bank account not found.

**Factory Method:**
```php
public static function withId(string $id): self;
```

---

### InvalidStatementFormatException

**Location:** `src/Exceptions/InvalidStatementFormatException.php`

**Purpose:** Thrown when CSV format invalid.

---

### UnmatchedTransactionsException

**Location:** `src/Exceptions/UnmatchedTransactionsException.php`

**Purpose:** Thrown when reconciliation has unmatched transactions requiring review.

---

## Usage Patterns

### Pattern 1: Import and Auto-Reconcile

```php
// Import statement
$statement = $cashManager->importBankStatement(
    $bankAccountId, $startDate, $endDate, $transactions, auth()->id()
);

// Auto-reconcile
$result = $cashManager->reconcileStatement($statement->getId());

if ($result->getUnmatchedCount() > 0) {
    // User review required
}
```

### Pattern 2: Review and Post Pending Adjustments

```php
$adjustments = $pendingAdjustmentRepo->findByTenant($tenantId);

foreach ($adjustments as $adjustment) {
    // User selects GL account
    $journalEntryId = $cashManager->postPendingAdjustment(
        $adjustment->getId(),
        $userSelectedGlAccount,
        auth()->id()
    );
}
```

### Pattern 3: Rejection with Reversal

```php
try {
    $cashManager->rejectPendingAdjustment(
        $adjustmentId,
        'Incorrect customer match',
        auth()->id()
    );
    // Automatic reversal initiated
} catch (ReversalRequiredException $e) {
    // Handle reversal error
}
```
