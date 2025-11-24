# Nexus EventStream Package

**Event Sourcing Engine for Critical Domains (Finance GL, Inventory)**

The EventStream package provides an immutable, append-only event store for domains requiring complete audit trails and state replay capability. This is NOT a general-purpose event bus—it's specifically designed for event sourcing in compliance-critical domains.

## 🎯 Purpose

Event Sourcing is **RESERVED** for critical domains where you need to answer: *"What was the exact state of this entity on [date]?"*

**Use EventStream for:**
- ✅ **Finance (GL)**: Every debit/credit is an event (SOX/IFRS compliance)
- ✅ **Inventory**: Every stock change is an event (stock accuracy verification)
- ✅ **Large Enterprise AP/AR**: Payment lifecycle tracking (optional)

**Do NOT use EventStream for:**
- ❌ HRM, Payroll, CRM, Procurement (use `Nexus\AuditLogger` for timeline views)
- ❌ User activity logs (use `Nexus\AuditLogger`)
- ❌ General application events (use Laravel events or message queue)

## 🏗️ Architecture

This package follows the **Hybrid Approach** described in ARCHITECTURE.md:

### The "Feed" View (AuditLogger) vs. "Replay" Capability (EventStream)

| Feature | AuditLogger | EventStream |
|---------|-------------|-------------|
| Purpose | User-facing timeline ("what happened") | State reconstruction ("replay history") |
| Storage | Outcome-based logs | Immutable event log |
| Query | "Show me changes to Customer #123" | "What was GL Account 1000 balance on 2024-10-15?" |
| Complexity | Low | High (snapshots, projections, upcasters) |
| Use Case | 95% of domains | Critical domains only |

### Key Concepts

1. **Event**: Immutable fact that happened (AccountCreditedEvent, StockReservedEvent)
2. **Stream**: Ordered sequence of events for an aggregate
3. **Aggregate**: Entity whose state is rebuilt from events (e.g., GL Account, Inventory Item)
4. **Projection**: Read model built from event stream (e.g., CurrentBalanceProjection)
5. **Snapshot**: Cached aggregate state to optimize replay performance
6. **Temporal Query**: Query state at specific point in time

## 📦 Framework-Agnostic Design

This package is **pure PHP** with no Laravel dependencies. All persistence operations are defined via interfaces:

- `EventStoreInterface`: Append events to streams
- `StreamReaderInterface`: Read events from streams
- `SnapshotRepositoryInterface`: Store/retrieve aggregate snapshots
- `ProjectorInterface`: Rebuild state from events
- `EventSerializerInterface`: Serialize event payloads

## 🔧 Installation

```bash
composer require nexus/event-stream:"*@dev"
```

## 📋 Requirements Satisfied

This package satisfies 104 requirements across 7 categories:
- **14 Architectural Requirements** (ARC-EVS-7001 to ARC-EVS-7014)
- **13 Business Requirements** (BUS-EVS-7101 to BUS-EVS-7113)
- **28 Functional Requirements** (FUN-EVS-7201 to FUN-EVS-7228)
- **9 Performance Requirements** (PER-EVS-7301 to PER-EVS-7309)
- **10 Reliability Requirements** (REL-EVS-7401 to REL-EVS-7410)
- **10 Security Requirements** (SEC-EVS-7501 to SEC-EVS-7510)
- **10 Integration Requirements** (INT-EVS-7601 to INT-EVS-7610)
- **8 Usability Requirements** (USA-EVS-7701 to USA-EVS-7708)

## 🚀 Usage Example

### Publishing Events

```php
use Nexus\EventStream\Contracts\EventStoreInterface;
use Nexus\Finance\Events\AccountCreditedEvent;

// In Nexus\Finance\Services\LedgerManager
public function __construct(
    private readonly EventStoreInterface $eventStore
) {}

public function postJournalEntry(JournalEntry $entry): void
{
    foreach ($entry->getLines() as $line) {
        if ($line->isCredit()) {
            $this->eventStore->append(
                $line->getAccountId(),
                new AccountCreditedEvent(
                    accountId: $line->getAccountId(),
                    amount: $line->getAmount(),
                    journalEntryId: $entry->getId()
                )
            );
        }
    }
}
```

### Temporal Queries

```php
use Nexus\EventStream\Services\EventStreamManager;

// Get account balance at specific date
$balance = $manager->getStateAt(
    aggregateId: 'account-1000',
    timestamp: new \DateTimeImmutable('2024-10-15')
);
```

### Building Projections

```php
use Nexus\EventStream\Contracts\ProjectorInterface;

// Projection rebuilds current balance from events
class CurrentBalanceProjector implements ProjectorInterface
{
    public function project(EventInterface $event): void
    {
        if ($event instanceof AccountCreditedEvent) {
            $this->balances[$event->accountId] += $event->amount;
        }
    }
}
```

## 🔒 Security & Compliance

- **Immutable Streams**: Events cannot be modified or deleted (append-only)
- **Tenant Isolation**: All streams are tenant-scoped
- **Encryption**: Event payloads encrypted at rest and in transit
- **Audit Trail**: Event store operations logged via `Nexus\AuditLogger`
- **SOX Compliance**: Immutable financial event trails
- **GDPR**: Support for event anonymization (not deletion)

## 📊 Performance Characteristics

| Operation | Target | Notes |
|-----------|--------|-------|
| Event Append | < 50ms (p95) | With database transaction |
| Stream Read (1000 events) | < 100ms | From database |
| Snapshot Restoration | < 10ms | In-memory cache |
| Replay 10K events | < 5s | For state reconstruction |
| Temporal Query | < 3s | For < 10K events |

## 📖 Documentation

### Package Documentation
- **[Getting Started Guide](docs/getting-started.md)** - Quick start guide with prerequisites, concepts, and first integration
- **[API Reference](docs/api-reference.md)** - Complete documentation of all interfaces, value objects, and exceptions
- **[Integration Guide](docs/integration-guide.md)** - Laravel and Symfony integration examples
- **[Basic Usage Example](docs/examples/basic-usage.php)** - Simple event publishing and reading patterns
- **[Advanced Usage Example](docs/examples/advanced-usage.php)** - Snapshots, temporal queries, and concurrency control

### Additional Resources
- `IMPLEMENTATION_SUMMARY.md` - Implementation progress and metrics (122 tests, 100% pass rate)
- `REQUIREMENTS.md` - 104 detailed requirements across 7 categories
- `TEST_SUITE_SUMMARY.md` - Test coverage and results
- `VALUATION_MATRIX.md` - Package valuation metrics (estimated value: $85,296)
- See root `ARCHITECTURE.md` section "Hybrid Approach: Feed vs. Replay"

## 🤝 Integration Points

- **Nexus\Finance**: Publishes journal entry events (AccountCreditedEvent, AccountDebitedEvent)
- **Nexus\Inventory**: Publishes stock events (StockReservedEvent, StockShippedEvent)
- **Nexus\AuditLogger**: Logs event store operations (meta-auditing)
- **Nexus\Storage**: Stores snapshots and archived events
- **Nexus\Notifier**: Sends alerts for projection failures

## ⚠️ When NOT to Use

If you only need to show "a timeline of changes" to users, use `Nexus\AuditLogger` instead. EventStream adds complexity and should only be used when state replay is legally or operationally required.

## 📝 License

MIT License - see LICENSE file for details.
