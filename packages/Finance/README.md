# Nexus\Finance

Framework-agnostic general ledger and journal entry management for the Nexus ERP system.

## Purpose

The Finance package provides the core double-entry bookkeeping engine for the ERP system. It manages the Chart of Accounts (COA), journal entries, and general ledger posting operations. This package is the foundation for all financial operations and is consumed by Accounting, Payable, and Receivable packages.

## Key Features

- **Hierarchical Chart of Accounts**: Unlimited depth using nested set model
- **Double-Entry Bookkeeping**: Enforced debit/credit balance validation
- **Multi-Currency Support**: Foreign currency transactions with exchange rates
- **Immutable Posting**: Journal entries cannot be modified once posted
- **Period Validation**: Integration with Nexus\Period for fiscal period checking
- **Audit Trail**: Comprehensive logging via Nexus\AuditLogger
- **Event Sourcing**: Critical GL events published to Nexus\EventStream for replay capability

## Architecture

### Contracts (Interfaces)
- `FinanceManagerInterface` - Main service for journal entry operations
- `JournalEntryInterface` - Journal entry entity contract
- `AccountInterface` - COA account entity contract
- `LedgerRepositoryInterface` - Read-only ledger query operations
- `JournalEntryRepositoryInterface` - Journal entry persistence
- `AccountRepositoryInterface` - COA management

### Value Objects
- `Money` - Immutable amount with currency (4 decimal precision)
- `ExchangeRate` - Currency conversion rate with effective date
- `JournalEntryNumber` - Sequential number with pattern support
- `AccountCode` - Validated account code

### Core Engine (Internal)
- `PostingEngine` - Transaction posting logic with validation
- `BalanceCalculator` - Account balance calculation
- `AccountHierarchyManager` - COA tree operations

## Usage Example

```php
use Nexus\Finance\Services\FinanceManager;
use Nexus\Finance\ValueObjects\Money;

// Create and post a journal entry
$journalEntry = $financeManager->createJournalEntry([
    'date' => new \DateTimeImmutable('2024-11-15'),
    'description' => 'Customer payment received',
    'lines' => [
        [
            'account_code' => '1000', // Cash
            'debit' => Money::of(1000, 'MYR'),
            'credit' => Money::zero('MYR'),
        ],
        [
            'account_code' => '1200', // Accounts Receivable
            'debit' => Money::zero('MYR'),
            'credit' => Money::of(1000, 'MYR'),
        ],
    ],
]);

$financeManager->postJournalEntry($journalEntry->getId());
```

## Integration

This package integrates with:
- `Nexus\Period` - for fiscal period validation (REQUIRED)
- `Nexus\Sequencing` - for journal entry number generation (REQUIRED)
- `Nexus\Uom` - for currency management (REQUIRED)
- `Nexus\AuditLogger` - for audit trails (REQUIRED)
- `Nexus\EventStream` - for event sourcing (OPTIONAL, large enterprise only)

Consumed by:
- `Nexus\Accounting` - for financial statement generation
- `Nexus\Payable` - for AP journal entry posting
- `Nexus\Receivable` - for AR journal entry posting

## Performance Requirements

- Journal entry validation: < 100ms for up to 100 line items
- Single journal entry posting: < 200ms (p95)
- Batch posting: 1000 entries per minute
- Account balance calculation: < 500ms for 100K transactions
- Trial balance generation: < 3s for 100K transactions

## Event Sourcing (Large Enterprise Only)

For large enterprises requiring complete audit trail with replay capability:
- `AccountCreditedEvent` - Published when an account is credited
- `AccountDebitedEvent` - Published when an account is debited
- `JournalPostedEvent` - Published when a journal entry is posted
- Supports temporal queries: "What was the balance on 2024-10-15?"
