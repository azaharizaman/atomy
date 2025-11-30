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

## Architecture (DDD Layering)

This package follows Domain-Driven Design (DDD) architecture with three distinct layers:

```
packages/Finance/
├── src/
│   ├── Domain/              # THE TRUTH (Pure Logic)
│   │   ├── Contracts/       # Entity and Repository interfaces
│   │   ├── Entities/        # Domain entities (future)
│   │   ├── ValueObjects/    # Money, AccountCode, ExchangeRate, etc.
│   │   ├── Enums/           # AccountType, JournalEntryStatus
│   │   ├── Events/          # Domain events (future)
│   │   ├── Exceptions/      # Domain-specific exceptions
│   │   └── Services/        # Domain services (PostingEngine, BalanceCalculator)
│   │
│   ├── Application/         # THE USE CASES
│   │   ├── Handlers/        # Application services (FinanceManager)
│   │   ├── Commands/        # Command DTOs (future)
│   │   ├── Queries/         # Query DTOs (future)
│   │   └── DTOs/            # Data transfer objects (future)
│   │
│   └── Infrastructure/      # INTERNAL ADAPTERS
│       ├── Persistence/     # InMemory repositories for testing
│       └── Mappers/         # Data mappers (future)
```

### Domain Layer (Core)
- `Domain\Contracts\FinanceManagerInterface` - Main service contract
- `Domain\Contracts\JournalEntryInterface` - Journal entry entity contract
- `Domain\Contracts\AccountInterface` - COA account entity contract
- `Domain\Contracts\LedgerRepositoryInterface` - Read-only ledger query operations
- `Domain\Contracts\JournalEntryRepositoryInterface` - Journal entry persistence
- `Domain\Contracts\AccountRepositoryInterface` - COA management

### Value Objects
- `Domain\ValueObjects\Money` - Immutable amount with currency (4 decimal precision)
- `Domain\ValueObjects\ExchangeRate` - Currency conversion rate with effective date
- `Domain\ValueObjects\JournalEntryNumber` - Sequential number with pattern support
- `Domain\ValueObjects\AccountCode` - Validated account code

### Domain Services
- `Domain\Services\PostingEngine` - Transaction posting logic with validation
- `Domain\Services\BalanceCalculator` - Account balance calculation

### Application Layer
- `Application\Handlers\FinanceManager` - Application service implementing FinanceManagerInterface

### Infrastructure Layer
- `Infrastructure\Persistence\InMemoryAccountRepository` - In-memory account storage for testing
- `Infrastructure\Persistence\InMemoryJournalEntryRepository` - In-memory journal entry storage for testing
- `Infrastructure\Persistence\InMemoryLedgerRepository` - In-memory ledger queries for testing

## Usage Example

```php
use Nexus\Finance\Application\Handlers\FinanceManager;
use Nexus\Finance\Domain\ValueObjects\Money;

// Obtain FinanceManager from your service container (framework-agnostic)
// The consumer application binds concrete implementations

// Create and post a journal entry
$journalEntry = $financeManager->createJournalEntry([
    'date' => new \DateTimeImmutable('2024-11-15'),
    'description' => 'Customer payment received',
    'lines' => [
        [
            'account_id' => '1000', // Cash
            'debit' => '1000.0000',
            'credit' => '0.0000',
        ],
        [
            'account_id' => '1200', // Accounts Receivable
            'debit' => '0.0000',
            'credit' => '1000.0000',
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

---

## Documentation

### Getting Started
- **[Getting Started Guide](docs/getting-started.md)** - Installation, setup, and core concepts

### API Reference
- **[API Reference](docs/api-reference.md)** - Complete interface documentation

### Integration Guides
- **[Integration Guide](docs/integration-guide.md)** - Laravel and Symfony examples

### Code Examples
- **[Basic Usage](docs/examples/basic-usage.php)** - Common GL operations
- **[Advanced Usage](docs/examples/advanced-usage.php)** - Multi-currency transactions

### Implementation Documentation
- **[Implementation Summary](IMPLEMENTATION_SUMMARY.md)** - Development progress and metrics
- **[Requirements](REQUIREMENTS.md)** - Complete requirements traceability
- **[Test Suite Summary](TEST_SUITE_SUMMARY.md)** - Testing strategy and coverage
- **[Valuation Matrix](VALUATION_MATRIX.md)** - Package valuation ($720K value, 4,515% ROI)

---

## License

MIT License
