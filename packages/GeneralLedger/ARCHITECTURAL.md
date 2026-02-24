# GeneralLedger Package Architecture

## 1. Package Overview

### Purpose

The **Nexus\GeneralLedger** package is a Layer-1 atomic package that serves as the **central transaction hub** for all financial entries in the Nexus ERP system. The General Ledger (GL) is the core of any financial system - all journal entries from subledgers (Receivables, Payables, Assets, etc.) must ultimately be posted to the GL to ensure accounting accuracy and generate financial statements.

### Position in Architecture

| Layer | Role |
|-------|------|
| Layer 1 (Atomic Package) | Pure business logic, framework-agnostic |
| Domain | Financial Management |
| Namespace | `Nexus\GeneralLedger` |
| Dependencies | Own interfaces only, PSR interfaces, Common package |

---

## 2. Domain Model

### Entities

- **Ledger**: Represents the general ledger for a tenant.
- **LedgerAccount**: Represents an account within a specific ledger.
- **Transaction**: Represents a single debit or credit entry with a running balance.
- **TrialBalance**: A report-like entity capturing all account balances as of a date.
- **TrialBalanceLine**: An individual line within a trial balance.

### Value Objects

- **AccountBalance**: Encapsulates monetary amount and balance type (DEBIT/CREDIT/NONE).
- **TransactionDetail**: Data required to post a transaction.
- **PostingResult**: Result of a single or batch posting operation.
- **SubledgerPostingRequest**: Request from a subledger to post entries.
- **ValidationResult**: Feedback on the validity of a posting request.

### Enums

- **LedgerType**: STATUTORY, MANAGEMENT.
- **LedgerStatus**: ACTIVE, CLOSED, ARCHIVED.
- **TransactionType**: DEBIT, CREDIT.
- **BalanceType**: DEBIT, CREDIT, NONE.
- **SubledgerType**: RECEIVABLE, PAYABLE, ASSET, etc.

---

## 3. Core Services

- **LedgerService**: Manages ledger lifecycle (create, close, archive).
- **TransactionService**: Handles posting of transactions and reversals.
- **TrialBalanceService**: Generates trial balance entities.
- **BalanceCalculationService**: Logic for computing running and account balances.
- **LedgerAccountService**: Manages registration and retrieval of ledger accounts.

---

## 4. Key Interfaces (Contracts)

### Repository Interfaces (CQRS)

- **LedgerQueryInterface** / **LedgerPersistInterface**
- **LedgerAccountQueryInterface** / **LedgerAccountPersistInterface**
- **TransactionQueryInterface** / **TransactionPersistInterface**

### Integration & Support Interfaces

- **SubledgerPostingInterface**: Contract for subledgers to post to the GL.
- **IdGeneratorInterface**: Framework-agnostic unique ID generation.
- **DatabaseTransactionInterface**: Framework-agnostic transaction management.

---

## 5. Directory Structure

```text
packages/GeneralLedger/
├── src/
│   ├── Contracts/
│   │   ├── DatabaseTransactionInterface.php
│   │   ├── IdGeneratorInterface.php
│   │   ├── LedgerAccountPersistInterface.php
│   │   ├── LedgerAccountQueryInterface.php
│   │   ├── LedgerPersistInterface.php
│   │   ├── LedgerQueryInterface.php
│   │   ├── SubledgerPostingInterface.php
│   │   ├── TransactionPersistInterface.php
│   │   ├── TransactionQueryInterface.php
│   │   └── TrialBalanceQueryInterface.php
│   │
│   ├── Entities/
│   │   ├── Ledger.php
│   │   ├── LedgerAccount.php
│   │   ├── Transaction.php
│   │   ├── TrialBalance.php
│   │   └── TrialBalanceLine.php
│   │
│   ├── Enums/
│   │   ├── BalanceType.php
│   │   ├── LedgerStatus.php
│   │   ├── LedgerType.php
│   │   ├── SubledgerType.php
│   │   └── TransactionType.php
│   │
│   ├── Exceptions/
│   │   ├── AccountNotFoundException.php
│   │   ├── GeneralLedgerException.php
│   │   ├── InvalidPostingException.php
│   │   ├── LedgerAlreadyActiveException.php
│   │   ├── LedgerAlreadyClosedException.php
│   │   ├── LedgerArchivedException.php
│   │   ├── LedgerNotFoundException.php
│   │   ├── PeriodClosedException.php
│   │   └── TransactionAlreadyReversedException.php
│   │
│   ├── Services/
│   │   ├── BalanceCalculationService.php
│   │   ├── LedgerAccountService.php
│   │   ├── LedgerService.php
│   │   ├── TransactionService.php
│   │   └── TrialBalanceService.php
│   │
│   ├── ValueObjects/
│   │   ├── AccountBalance.php
│   │   ├── PostingResult.php
│   │   ├── SubledgerPostingRequest.php
│   │   ├── TransactionDetail.php
│   │   └── ValidationResult.php
│   │
│   └── Support/
│
└── tests/
```
