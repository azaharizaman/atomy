# Nexus\CashManagement

**Atomic, stateless package for managing bank accounts, bank statement imports, cash reconciliation, and liquidity forecasting—strictly decoupled from GL ownership.**

## Overview

The `Nexus\CashManagement` package provides comprehensive cash and bank account management capabilities within the Nexus ERP ecosystem. It focuses on:

- **Bank Account Management**: Master data for company bank accounts with multi-currency support
- **Statement Import**: Configurable CSV import with duplicate detection and validation
- **Automatic Reconciliation**: AI-assisted matching of bank transactions to ERP records (Payments, Receipts, GL entries)
- **Manual Reconciliation**: Review and approval workflow for unmatched transactions
- **Cash Flow Forecasting**: Deterministic and AI-powered multi-scenario forecasting
- **Cash Position Tracking**: Real-time cash position across all bank accounts

## Core Philosophy

### Strict Decoupling

This package adheres to the **"Logic in Packages, Implementation in Applications"** principle:

- **Framework-Agnostic**: Pure PHP with zero Laravel dependencies
- **Contract-Driven**: All dependencies expressed via interfaces
- **Stateless Operations**: No GL posting—only reconciliation and classification
- **Integration via Events**: Consumes `Nexus\Import` events for statement data

### Separation of Concerns

```
Nexus\Import          → Parses CSV files → Emits StatementLineDTO[]
Nexus\CashManagement  → Consumes DTOs   → Creates BankStatement entities → Matches transactions
Nexus\Finance         → Receives post commands → Creates Journal Entries
```

## Architecture

### Key Design Decisions

1. **AuditLogger for Timeline** (V1): All reconciliation events logged for user-facing timeline
2. **EventStream Optional** (V2): Available for large enterprises requiring SOX compliance and temporal queries
3. **Manual GL Posting**: Reconciliation engine creates `PendingAdjustment` entities; user manually posts to GL
4. **Auto-Reversal**: Rejected pending adjustments automatically reverse payment applications via workflow
5. **AI Governance**: Model versioning tracked in `PendingAdjustment` for explainability

### Integration Points

- **`Nexus\Finance`**: GL account validation, journal entry posting
- **`Nexus\Receivable`**: Payment application matching and reversal
- **`Nexus\Payable`**: Payment matching for outflows
- **`Nexus\Period`**: Period validation for transaction dates
- **`Nexus\Currency`**: Multi-currency exchange rates (V2)
- **`Nexus\Sequencing`**: Auto-numbering for statements and reconciliations
- **`Nexus\Import`**: CSV file parsing and standardization
- **`Nexus\Setting`**: Feature flags and configuration
- **`Nexus\Workflow`**: High-value variance escalation and reversal approval
- **`Nexus\Intelligence`** (optional): AI-powered classification and forecasting
- **`Nexus\Analytics`** (optional): Cash Conversion Cycle and bank fee analysis

## Features

### Bank Account Management

```php
$bankAccount = $cashManager->createBankAccount(
    tenantId: $tenantId,
    accountCode: '1000-01',
    glAccountId: $glAccountId,
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

### Statement Import

```php
// Import via Nexus\Import package (emits FileImportedEvent)
// BankStatementImportedListener consumes event and creates entities

$result = $cashManager->reconcileStatement($statementId);

echo "Matched: {$result->getMatchedCount()}\n";
echo "Unmatched: {$result->getUnmatchedCount()}\n";
```

### Cash Flow Forecasting

```php
$parameters = ScenarioParametersVO::fromScenarioType(
    ForecastScenarioType::BASELINE,
    horizonDays: 90
);

$forecast = $cashFlowForecaster->forecast($tenantId, $parameters);

if ($forecast->hasNegativeBalance()) {
    // Alert: Liquidity risk detected
}
```

### Pending Adjustment Posting

```php
// User reviews unmatched transaction
$cashManager->postPendingAdjustment(
    pendingAdjustmentId: $adjustmentId,
    glAccount: '6200', // Bank Fees Expense
    postedBy: $userId
);

// If user rejects (wrong match):
$cashManager->rejectPendingAdjustment(
    pendingAdjustmentId: $adjustmentId,
    reason: 'Incorrect match - customer deposit',
    rejectedBy: $userId
);
// Triggers automatic payment application reversal + GL workflow
```

## Value Objects

- **`BankAccountNumber`**: Validated bank account with IBAN/SWIFT support
- **`StatementPeriod`**: Date range with overlap detection
- **`ReconciliationTolerance`**: Amount/date variance thresholds
- **`CashPosition`**: Point-in-time balance snapshot
- **`CSVColumnMapping`**: Import configuration
- **`ScenarioParametersVO`**: Forecast scenario parameters
- **`ForecastResultVO`**: Persistable forecast output
- **`StatementHash`**: Cryptographic deduplication
- **`AIModelVersion`**: Semantic versioning for AI models

## Enums

- **`BankAccountType`**: CHECKING, SAVINGS, CREDIT_CARD, MONEY_MARKET, LINE_OF_CREDIT
- **`BankAccountStatus`**: ACTIVE, INACTIVE, CLOSED, SUSPENDED
- **`BankTransactionType`**: DEPOSIT, WITHDRAWAL, TRANSFER, FEE, INTEREST, etc.
- **`ReconciliationStatus`**: PENDING, MATCHED, VARIANCE_REVIEW, RECONCILED, UNMATCHED, REJECTED
- **`MatchingConfidence`**: HIGH, MEDIUM, LOW, MANUAL
- **`ForecastScenarioType`**: OPTIMISTIC, BASELINE, PESSIMISTIC, CUSTOM

## Exceptions

- **`BankAccountNotFoundException`**
- **`DuplicateStatementException`**
- **`PartialOverlapException`**
- **`ReconciliationException`**
- **`ReversalRequiredException`**
- **`InvalidStatementFormatException`**
- **`UnmatchedTransactionsException`**

## Multi-Currency Support (V2)

The package schema is V2-ready with nullable columns:
- `transaction_currency`
- `exchange_rate`
- `functional_amount`

Multi-currency activation requires:
```php
$featureManager->isEnabled('multi_currency_banking')
```

## Dependencies

- `nexus/finance` - GL integration
- `nexus/receivable` - Payment application
- `nexus/payable` - Payment matching
- `nexus/period` - Period validation
- `nexus/currency` - Exchange rates
- `nexus/sequencing` - Auto-numbering
- `nexus/import` - Statement parsing
- `nexus/setting` - Configuration
- `nexus/workflow` - Approval processes

## Optional Dependencies

- `nexus/intelligence` - AI classification/forecasting
- `nexus/analytics` - KPI calculation

## Installation

```bash
composer require nexus/cash-management:"*@dev"
```

## License

MIT License. See LICENSE file for details.

## Architecture Documentation

See `docs/CASH_MANAGEMENT_IMPLEMENTATION_SUMMARY.md` for comprehensive implementation details.
