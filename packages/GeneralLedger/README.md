# GeneralLedger Package

Central transaction hub for all financial entries in the Nexus ERP system.

## Purpose

The **Nexus\GeneralLedger** package is a Layer-1 atomic package that serves as the **central transaction hub** for all financial entries in the Nexus ERP system. The General Ledger (GL) is the core of any financial system - all journal entries from subledgers (Receivables, Payables, Assets, etc.) must ultimately be posted to the GL to ensure accounting accuracy and generate financial statements.

## Key Features

- **Ledger Master Data Management**: Create, update, and manage multiple ledgers per tenant (statutory and management ledgers)
- **Ledger Account Management**: Register and manage accounts within the ledger, integrating with ChartOfAccount
- **Journal Entry Posting**: Post validated journal entries to the general ledger with full audit trail
- **Account Balance Calculation**: Real-time calculation of debit/credit balances as of any date
- **Trial Balance Generation**: Generate comprehensive trial balance reports for all accounts
- **Multi-Entity Support**: Support for multiple entities (tenants) with isolated ledger data
- **Multi-Currency Support**: Handle transactions in multiple currencies with proper conversion
- **Subledger Integration**: Interfaces for AR, AP, and Assets subledgers to post transactions to GL
- **Fiscal Period Validation**: Enforce posting only to open accounting periods
- **Transaction Reversal**: Reverse posted transactions with full audit trail

## Installation

```bash
composer require nexus/general-ledger
```

### Requirements

- PHP ^8.3
- nexus/common ^1.0
- brick/math ^0.12

## Usage

### Creating a Ledger

```php
use Nexus\GeneralLedger\Services\LedgerService;
use Nexus\GeneralLedger\Enums\LedgerType;

// $ledgerService should be injected via IdGeneratorInterface
$ledger = $ledgerService->createLedger(
    tenantId: 'tenant-123',
    name: 'Statutory Ledger',
    type: LedgerType::STATUTORY,
    currency: 'USD',
    description: 'Main statutory ledger'
);

echo $ledger->id; // generated ID string
```

### Calculating Account Balances

```php
use Nexus\GeneralLedger\Services\TransactionService;

$balance = $transactionService->getAccountBalance(
    $account->id,
    new DateTimeImmutable('2024-01-31')
);

echo $balance->getAmount()->getAmount(); // Account balance as of date
echo $balance->balanceType->value; // debit or credit
```

### Generating Trial Balance

```php
$trialBalance = $trialBalanceService->generateTrialBalance(
    $ledger->id,
    'period-2024-01'
);

echo $trialBalance->totalDebits->getAmount();
echo $trialBalance->totalCredits->getAmount();
echo $trialBalance->isBalanced; // true if debits = credits

foreach ($trialBalance->lines as $line) {
    // Correctly access TrialBalanceLine properties
    $balance = $line->getNetBalance();
    echo $line->accountCode . ': ' . $balance->getAmount();
}
```

## Interfaces

### Core Interfaces

| Interface | Description |
|-----------|-------------|
| [`SubledgerPostingInterface`](src/Contracts/SubledgerPostingInterface.php) | Interface for subledgers to post to GL |
| [`IdGeneratorInterface`](src/Contracts/IdGeneratorInterface.php) | Framework-agnostic unique ID generation |
| [`DatabaseTransactionInterface`](src/Contracts/DatabaseTransactionInterface.php) | Framework-agnostic transaction management |

### Repository Interfaces (CQRS)

| Interface | Description |
|-----------|-------------|
| [`LedgerQueryInterface`](src/Contracts/LedgerQueryInterface.php) | Read operations for Ledgers |
| [`LedgerPersistInterface`](src/Contracts/LedgerPersistInterface.php) | Write operations for Ledgers |
| [`LedgerAccountQueryInterface`](src/Contracts/LedgerAccountQueryInterface.php) | Read operations for LedgerAccounts |
| [`LedgerAccountPersistInterface`](src/Contracts/LedgerAccountPersistInterface.php) | Write operations for LedgerAccounts |
| [`TransactionQueryInterface`](src/Contracts/TransactionQueryInterface.php) | Read operations for Transactions |
| [`TransactionPersistInterface`](src/Contracts/TransactionPersistInterface.php) | Write operations for Transactions |

## Models (Entities)

| Entity | Description |
|-------|-------------|
| [`Ledger`](src/Entities/Ledger.php) | Immutable ledger entity |
| [`LedgerAccount`](src/Entities/LedgerAccount.php) | Immutable ledger account entity |
| [`Transaction`](src/Entities/Transaction.php) | Immutable transaction entity |
| [`TrialBalance`](src/Entities/TrialBalance.php) | Immutable trial balance report entity |
| [`TrialBalanceLine`](src/Entities/TrialBalanceLine.php) | Individual line in a trial balance |

## Value Objects

| Value Object | Description |
|--------------|---------|
| [`AccountBalance`](src/ValueObjects/AccountBalance.php) | Represents balance with debit/credit type |
| [`TransactionDetail`](src/ValueObjects/TransactionDetail.php) | Transaction metadata for posting |
| [`PostingResult`](src/ValueObjects/PostingResult.php) | Result of a posting operation |
| [`SubledgerPostingRequest`](src/ValueObjects/SubledgerPostingRequest.php) | Request from a subledger |
| [`ValidationResult`](src/ValueObjects/ValidationResult.php) | Result of a validation check |

## Exceptions

| Exception | Description |
|-----------|-------------|
| [`GeneralLedgerException`](src/Exceptions/GeneralLedgerException.php) | Base GL exception |
| [`LedgerNotFoundException`](src/Exceptions/LedgerNotFoundException.php) | Ledger not found |
| [`LedgerAlreadyActiveException`](src/Exceptions/LedgerAlreadyActiveException.php) | Ledger already active |
| [`LedgerAlreadyClosedException`](src/Exceptions/LedgerAlreadyClosedException.php) | Ledger already closed |
| [`LedgerArchivedException`](src/Exceptions/LedgerArchivedException.php) | Ledger is archived |
| [`AccountNotFoundException`](src/Exceptions/AccountNotFoundException.php) | Account not found |
| [`PeriodClosedException`](src/Exceptions/PeriodClosedException.php) | Posting to closed period |
| [`InvalidPostingException`](src/Exceptions/InvalidPostingException.php) | Posting validation failed |
| [`TransactionAlreadyReversedException`](src/Exceptions/TransactionAlreadyReversedException.php) | Double reversal attempted |
| [`LedgerEntryNotFoundException`](src/Exceptions/LedgerEntryNotFoundException.php) | Entry not found |
| [`DuplicatePostingException`](src/Exceptions/DuplicatePostingException.php) | Duplicate posting detected |
| [`InvalidTransactionException`](src/Exceptions/InvalidTransactionException.php) | Transaction state is invalid |

## Architecture

This package follows the Atomic Architecture pattern (Layer 1):

- **Contracts**: Interface definitions for domain contracts and repository patterns (CQRS)
- **Entities**: Domain entities (Ledger, Transaction, etc.)
- **ValueObjects**: Immutable data structures
- **Services**: Pure business logic (LedgerService, TransactionService)
- **Enums**: Type-safe enumerations
- **Exceptions**: Domain-specific exceptions

## License

MIT License
