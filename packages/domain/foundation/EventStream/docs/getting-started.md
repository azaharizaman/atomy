# Getting Started with Nexus EventStream

## Overview

Nexus EventStream is an **event sourcing engine** designed specifically for critical domains where you need complete audit trails and the ability to reconstruct state at any point in time.

**Critical Principle:** EventStream is NOT for general application events. Use it ONLY for domains requiring temporal queries.

## Prerequisites

- PHP 8.3+
- Composer
- Understanding of Event Sourcing concepts

## Installation

```bash
composer require nexus/event-stream:"*@dev"
```

## When to Use EventStream

### ✅ Use For:

1. **Finance (General Ledger)** - Every debit/credit must be traceable
2. **Inventory** - Stock movements require complete audit trail
3. **Compliance-Critical Domains** - SOX, IFRS, regulatory requirements

### ❌ Do NOT Use For:

1. **User Activity Logs** - Use `Nexus\AuditLogger` instead
2. **HR/Payroll/CRM** - Use `Nexus\AuditLogger` for timeline views
3. **General Application Events** - Use framework event dispatcher

## Core Concepts

### Events
Immutable facts that happened in the past:
- `AccountCreditedEvent` - Money was credited to an account
- `StockReservedEvent` - Stock was reserved for order
- `PaymentReceivedEvent` - Payment was received from customer

### Streams
Ordered sequence of events for a specific aggregate:
- Stream Name: `account-1000` contains all events for GL Account #1000
- Stream Name: `inventory-item-SKU123` contains all stock movements for SKU123

### Aggregates
Entities whose state is rebuilt from events:
- GL Account balance = sum of all credits minus debits
- Current stock = initial stock + receipts - shipments - adjustments

### Projections
Read models optimized for queries:
- `AccountBalanceProjection` - Current balances of all accounts
- `StockLevelProjection` - Current stock levels

### Snapshots
Cached aggregate state to optimize rebuilds:
- Store snapshot every 100 events
- Rebuild from snapshot + recent events instead of full history

## Basic Configuration

### 1. Implement Required Interfaces

EventStream requires repository implementations for your persistence layer:

```php
// app/Repositories/EventStore/DbEventStore.php
namespace App\Repositories\EventStore;

use Nexus\EventStream\Contracts\EventStoreInterface;

class DbEventStore implements EventStoreInterface
{
    // Implementation using your database
}
```

### 2. Bind Interfaces in Service Provider

```php
// app/Providers/EventStreamServiceProvider.php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Nexus\EventStream\Contracts\EventStoreInterface;
use Nexus\EventStream\Contracts\StreamReaderInterface;
use Nexus\EventStream\Contracts\SnapshotRepositoryInterface;

class EventStreamServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(EventStoreInterface::class, DbEventStore::class);
        $this->app->singleton(StreamReaderInterface::class, DbStreamReader::class);
        $this->app->singleton(SnapshotRepositoryInterface::class, DbSnapshotRepository::class);
    }
}
```

### 3. Create Database Tables

```sql
-- Events table
CREATE TABLE events (
    id CHAR(26) PRIMARY KEY,
    stream_name VARCHAR(255) NOT NULL,
    event_type VARCHAR(255) NOT NULL,
    event_data JSON NOT NULL,
    metadata JSON,
    version INT NOT NULL,
    occurred_at TIMESTAMP NOT NULL,
    INDEX idx_stream_name (stream_name),
    INDEX idx_event_type (event_type),
    INDEX idx_occurred_at (occurred_at)
);

-- Snapshots table
CREATE TABLE snapshots (
    aggregate_id VARCHAR(255) PRIMARY KEY,
    aggregate_type VARCHAR(255) NOT NULL,
    state JSON NOT NULL,
    version INT NOT NULL,
    created_at TIMESTAMP NOT NULL
);
```

## Your First Integration

### Step 1: Define Domain Events

```php
namespace App\Finance\Events;

use Nexus\EventStream\Contracts\DomainEventInterface;

readonly class AccountCreditedEvent implements DomainEventInterface
{
    public function __construct(
        public string $accountId,
        public int $amount,
        public string $journalEntryId,
        public string $description
    ) {}
    
    public function getEventType(): string
    {
        return 'AccountCredited';
    }
    
    public function getAggregateId(): string
    {
        return $this->accountId;
    }
}
```

### Step 2: Publish Events

```php
namespace App\Finance\Services;

use Nexus\EventStream\Contracts\EventStoreInterface;
use App\Finance\Events\AccountCreditedEvent;

class LedgerService
{
    public function __construct(
        private readonly EventStoreInterface $eventStore
    ) {}
    
    public function creditAccount(string $accountId, int $amount, string $description): void
    {
        $event = new AccountCreditedEvent(
            accountId: $accountId,
            amount: $amount,
            journalEntryId: '...',
            description: $description
        );
        
        // Append to event stream
        $this->eventStore->append($accountId, $event);
    }
}
```

### Step 3: Read Event Stream

```php
use Nexus\EventStream\Contracts\StreamReaderInterface;

class AccountBalanceQuery
{
    public function __construct(
        private readonly StreamReaderInterface $streamReader
    ) {}
    
    public function getBalance(string $accountId): int
    {
        $events = $this->streamReader->readStream($accountId);
        
        $balance = 0;
        foreach ($events as $event) {
            if ($event instanceof AccountCreditedEvent) {
                $balance += $event->amount;
            } elseif ($event instanceof AccountDebitedEvent) {
                $balance -= $event->amount;
            }
        }
        
        return $balance;
    }
}
```

### Step 4: Temporal Queries (Time Travel)

```php
use Nexus\EventStream\Contracts\StreamReaderInterface;

// Get balance at specific date
public function getBalanceAt(string $accountId, \DateTimeImmutable $timestamp): int
{
    $events = $this->streamReader->readStreamUntil($accountId, $timestamp);
    
    return $this->calculateBalance($events);
}
```

## Next Steps

1. **Read API Reference** - [docs/api-reference.md](api-reference.md)
2. **Integration Guide** - [docs/integration-guide.md](integration-guide.md)
3. **Examples** - [docs/examples/](examples/)
4. **Performance Optimization** - Implement snapshots for large streams
5. **Projections** - Build read models for common queries

## Common Pitfalls

### ❌ Using EventStream for Everything
EventStream adds complexity. Only use for domains requiring temporal queries.

### ❌ Not Using Snapshots
Rebuilding from thousands of events is slow. Use snapshots every 100-500 events.

### ❌ Mutable Events
Events are facts—they cannot be changed. Never modify event data after publishing.

### ❌ Missing Metadata
Always include metadata (user_id, tenant_id, timestamp) for audit trails.

## Troubleshooting

**Events not appearing in stream?**
- Check EventStoreInterface implementation
- Verify database transactions are committed
- Check event serialization

**Slow replay performance?**
- Implement snapshots
- Optimize projection rebuilds
- Consider event archiving for old data

**Concurrency conflicts?**
- Implement optimistic concurrency control
- Use version numbers on streams
- Handle AppendConcurrencyException

## Support

- Documentation: [docs/](.)
- Requirements: [REQUIREMENTS.md](../REQUIREMENTS.md)
- Implementation Summary: [IMPLEMENTATION_SUMMARY.md](../IMPLEMENTATION_SUMMARY.md)
