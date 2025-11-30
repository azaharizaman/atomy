# API Reference: Finance

Complete documentation of all interfaces, value objects, enums, and exceptions.

---

## Interfaces

### FinanceManagerInterface

Main service for GL operations.

```php
namespace Nexus\Finance\Contracts;

interface FinanceManagerInterface
{
    /**
     * Create new GL account
     */
    public function createAccount(
        AccountCode $code,
        string $name,
        AccountType $type,
        ?string $parentId = null,
        string $currency = 'MYR'
    ): AccountInterface;
    
    /**
     * Post journal entry to ledger
     * 
     * @throws UnbalancedJournalEntryException
     * @throws JournalEntryAlreadyPostedException
     */
    public function postJournalEntry(JournalEntryInterface $entry): void;
    
    /**
     * Reverse journal entry
     */
    public function reverseJournalEntry(string $entryId, string $reason): JournalEntryInterface;
    
    /**
     * Get account balance
     */
    public function getAccountBalance(string $accountId, ?\DateTimeImmutable $asOf = null): Money;
    
    /**
     * Generate trial balance
     */
    public function getTrialBalance(?\DateTimeImmutable $asOf = null): array;
}
```

### AccountInterface

Represents a GL account.

```php
interface AccountInterface
{
    public function getId(): string;
    public function getCode(): AccountCode;
    public function getName(): string;
    public function getType(): AccountType;
    public function getCurrency(): string;
    public function isActive(): bool;
}
```

### JournalEntryInterface

Represents a journal entry.

```php
interface JournalEntryInterface
{
    public function getId(): string;
    public function getNumber(): JournalEntryNumber;
    public function getDate(): \DateTimeImmutable;
    public function getDescription(): string;
    public function getStatus(): JournalEntryStatus;
    public function getLines(): array; // JournalEntryLineInterface[]
    public function getTotalDebit(): Money;
    public function getTotalCredit(): Money;
    public function isBalanced(): bool;
}
```

---

## Value Objects

### AccountCode

```php
readonly class AccountCode
{
    public function __construct(public string $value)
    {
        if (empty($value) || strlen($value) > 20) {
            throw new InvalidArgumentException();
        }
    }
}
```

### Money

```php
readonly class Money
{
    public function __construct(
        public float $amount,
        public string $currency
    ) {}
    
    public function add(Money $other): Money;
    public function subtract(Money $other): Money;
    public function format(): string;
}
```

---

## Enums

### AccountType

```php
enum AccountType: string
{
    case Asset = 'asset';
    case Liability = 'liability';
    case Equity = 'equity';
    case Revenue = 'revenue';
    case Expense = 'expense';
}
```

### JournalEntryStatus

```php
enum JournalEntryStatus: string
{
    case Draft = 'draft';
    case Posted = 'posted';
    case Reversed = 'reversed';
}
```

---

## Exceptions

All exceptions extend `Nexus\Finance\Exceptions\FinanceException`.

- `UnbalancedJournalEntryException` - Debits ≠ credits
- `AccountNotFoundException` - Account not found
- `DuplicateAccountCodeException` - Account code already exists
- `JournalEntryAlreadyPostedException` - Cannot modify posted entry
- `AccountHasTransactionsException` - Cannot delete account with transactions

---

**Last Updated:** 2025-11-25
