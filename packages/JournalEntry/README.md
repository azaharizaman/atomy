# Nexus Journal Entry Package

A framework-agnostic PHP package for journal entry management in general ledger systems.

## Overview

`Nexus\JournalEntry` provides a complete journal entry management engine for ERP systems, including:

- Double-entry bookkeeping with balance validation
- Journal entry creation, posting, and reversal
- Multi-currency support with exchange rate handling
- Fiscal period validation integration
- Balance calculation engine
- Trial balance generation

## Requirements

- PHP 8.3+
- `nexus/common` ^1.0
- `nexus/chart-of-account` ^1.0
- `nexus/period` ^1.0

## Installation

```bash
composer require nexus/journal-entry
```

## Key Concepts

### Journal Entries

A journal entry represents a financial transaction recorded in the general ledger. Each entry:

- Contains multiple line items (minimum 2 for double-entry)
- Must be balanced (total debits = total credits)
- References valid accounts from the Chart of Accounts
- Is posted to an open fiscal period

### Status Lifecycle

```
Draft → Posted → Reversed
```

- **Draft**: Entry created but not yet posted
- **Posted**: Entry finalized and immutable
- **Reversed**: Entry has been reversed with offsetting entry

### Multi-Currency

Line items can be in different currencies:
- Transaction amount in original currency
- Base amount converted using effective exchange rate
- Exchange rate captured at posting time

**Important**: Multi-currency journal entries require a `CurrencyConverterInterface` implementation to be injected into `JournalEntryManager`. Without it, all lines must use the same currency.

```php
// Single currency - works without CurrencyConverter
$entry = $manager->createEntry([
    'lines' => [
        ['account_id' => 'acc-1', 'debit' => '100.00', 'currency' => 'MYR'],
        ['account_id' => 'acc-2', 'credit' => '100.00', 'currency' => 'MYR'],
    ],
]);

// Multi-currency - requires CurrencyConverter
$manager = new JournalEntryManager(
    $query,
    $persist,
    $ledgerQuery,
    $clock,
    'MYR', // default currency
    $currencyConverter, // inject converter
    $logger
);

$entry = $manager->createEntry([
    'lines' => [
        ['account_id' => 'acc-1', 'debit' => '100.00', 'currency' => 'USD'],
        ['account_id' => 'acc-2', 'credit' => '475.00', 'currency' => 'MYR'], // ~100 USD at 4.75 rate
    ],
]);
```

## Quick Start

```php
use Nexus\JournalEntry\Contracts\JournalEntryManagerInterface;

// Inject the manager (implementation provided by consuming application)
public function __construct(
    private readonly JournalEntryManagerInterface $journalManager
) {}

// Create and post a journal entry
public function recordSale(): void
{
    // Create journal entry
    $entry = $this->journalManager->createEntry([
        'date' => new DateTimeImmutable('2024-01-15'),
        'description' => 'Sales revenue - Invoice #1001',
        'reference' => 'INV-1001',
        'lines' => [
            [
                'account_id' => 'acc-cash-1000',
                'debit' => '1000.00',
                'credit' => '0.00',
                'currency' => 'MYR',
            ],
            [
                'account_id' => 'acc-revenue-4000',
                'debit' => '0.00',
                'credit' => '1000.00',
                'currency' => 'MYR',
            ],
        ],
    ]);
    
    // Post the entry
    $this->journalManager->postEntry($entry->getId());
}
```

## Available Interfaces

### Core Contracts

| Interface | Description |
|-----------|-------------|
| `JournalEntryInterface` | Journal entry entity contract |
| `JournalEntryLineInterface` | Line item entity contract |
| `JournalEntryQueryInterface` | Read operations (CQRS Query) |
| `JournalEntryPersistInterface` | Write operations (CQRS Command) |
| `JournalEntryManagerInterface` | High-level management operations |

### Supporting Contracts

| Interface | Description |
|-----------|-------------|
| `LedgerQueryInterface` | Balance and ledger queries |
| `CurrencyConverterInterface` | Currency conversion for multi-currency entries |
| `SequencingIntegrationInterface` | Journal entry number generation |
| `PeriodValidationInterface` | Fiscal period validation |

## Services

### JournalEntryManager

High-level service for journal entry operations:

```php
// Create entry
$entry = $manager->createEntry($data);

// Post entry (validates and marks as Posted)
$manager->postEntry($entryId);

// Reverse entry (creates offsetting entry)
$reversal = $manager->reverseEntry($entryId, $reason);
```

### PostingEngine

Validates journal entries before posting:

```php
// Validate entry
$engine->validate($entry);

// Calculate impact on accounts
$impacts = $engine->calculateImpact($entry);
```

### BalanceCalculator

Calculates account balances:

```php
// Balance as of date
$balance = $calculator->getAccountBalance($accountId, $asOfDate);

// Trial balance
$trialBalance = $calculator->generateTrialBalance($asOfDate);

// Running balance
$runningBalance = $calculator->calculateRunningBalance($accountId, $transactions);
```

## Value Objects

### Money

The `Money` value object is provided by `Nexus\Common` (shared across all packages):

```php
use Nexus\Common\ValueObjects\Money;

$amount = Money::of(1000.00, 'MYR');
$sum = $amount->add(Money::of(500.00, 'MYR'));
$isEqual = $amount->equals($other);
$formatted = $amount->format(); // "1000.00 MYR"
```

### ExchangeRate

Effective-dated exchange rate:

```php
use Nexus\JournalEntry\ValueObjects\ExchangeRate;

$rate = new ExchangeRate('USD', 'MYR', '4.7500', new DateTimeImmutable());
$converted = $rate->convert(Money::of('100.0000', 'USD'));
```

### JournalEntryNumber

Validated journal entry number:

```php
use Nexus\JournalEntry\ValueObjects\JournalEntryNumber;

$number = JournalEntryNumber::fromString('JE-2024-001234');
$prefix = $number->getPrefix(); // 'JE'
$sequence = $number->getSequence(); // 1234
```

## Integration with Other Packages

### Chart of Account (`nexus/chart-of-account`)

Journal entries reference accounts for posting:

```php
// Account validation during posting
$account = $accountQuery->find($line->getAccountId());

if (!$account->isPostable()) {
    throw new InvalidAccountException('Cannot post to header account');
}

if (!$account->isActive()) {
    throw new InvalidAccountException('Account is inactive');
}
```

### Period (`nexus/period`)

Period validation before posting:

```php
// Validate posting date is in open period
if (!$periodValidator->isOpen($entry->getPostingDate())) {
    throw new PeriodClosedException('Cannot post to closed period');
}
```

### Sequencing (`nexus/sequencing`)

Journal entry number generation:

```php
// Generate next number via sequencing integration
$number = $sequencing->getNext('journal_entry');
```

## Consumer Implementation

Consuming applications must provide implementations for:

1. **Repository Interfaces** - Database persistence using Eloquent/Doctrine
2. **Currency Converter** - Convert money between currencies (required for multi-currency entries)
3. **Period Validator** - Integration with Period package

### Example: Laravel Implementation

```php
// Service Provider bindings
$this->app->bind(
    JournalEntryQueryInterface::class,
    EloquentJournalEntryRepository::class
);

$this->app->bind(
    JournalEntryPersistInterface::class,
    EloquentJournalEntryRepository::class
);

$this->app->bind(
    CurrencyConverterInterface::class,
    CurrencyExchangeRateAdapter::class // Wraps Nexus\Currency\ExchangeRateService
);
```

## Testing

```bash
composer test
```

## License

MIT License. See [LICENSE](LICENSE) for details.
