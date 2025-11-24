# API Reference: EventStream

Complete reference for all interfaces and services in Nexus EventStream package.

---

## Core Interfaces

### EventStoreInterface

Primary interface for appending events to event streams with optimistic concurrency control.

```php
namespace Nexus\EventStream\Contracts;

interface EventStoreInterface
{
    /**
     * Append a single event to the stream
     *
     * @param string $aggregateId The aggregate identifier
     * @param EventInterface $event The event to append
     * @param int|null $expectedVersion Expected current version for optimistic locking (null = no check)
     * @return void
     * @throws ConcurrencyException If version conflict detected
     * @throws EventStreamException If append fails
     */
    public function append(
        string $aggregateId,
        EventInterface $event,
        ?int $expectedVersion = null
    ): void;

    /**
     * Append multiple events to the stream in a single transaction
     *
     * @param string $aggregateId The aggregate identifier
     * @param EventInterface[] $events The events to append
     * @param int|null $expectedVersion Expected current version for optimistic locking
     * @return void
     * @throws ConcurrencyException If version conflict detected
     */
    public function appendBatch(
        string $aggregateId,
        array $events,
        ?int $expectedVersion = null
    ): void;

    /**
     * Get current version of stream
     *
     * @param string $aggregateId The aggregate identifier
     * @return int Current version (0 if stream doesn't exist)
     */
    public function getStreamVersion(string $aggregateId): int;

    /**
     * Check if stream exists
     *
     * @param string $aggregateId The aggregate identifier
     * @return bool True if stream exists
     */
    public function streamExists(string $aggregateId): bool;

    /**
     * Delete a stream and all its events
     *
     * @param string $aggregateId The aggregate identifier
     * @return void
     */
    public function deleteStream(string $aggregateId): void;
}
```

**Requirements:** ARC-EVS-7003, FUN-EVS-7201, FUN-EVS-7205, BUS-EVS-7105

---

### StreamReaderInterface

Interface for reading events from event streams.

```php
namespace Nexus\EventStream\Contracts;

interface StreamReaderInterface
{
    /**
     * Read all events from stream
     *
     * @param string $aggregateId The aggregate identifier
     * @return EventInterface[] All events in chronological order
     */
    public function readStream(string $aggregateId): array;

    /**
     * Read events from stream starting at specific version
     *
     * @param string $aggregateId The aggregate identifier
     * @param int $fromVersion Starting version (inclusive)
     * @return EventInterface[] Events from specified version onwards
     */
    public function readStreamFromVersion(string $aggregateId, int $fromVersion): array;

    /**
     * Read events from stream up to specific timestamp
     *
     * @param string $aggregateId The aggregate identifier
     * @param \DateTimeImmutable $timestamp End timestamp (inclusive)
     * @return EventInterface[] Events up to specified timestamp
     */
    public function readStreamUntil(string $aggregateId, \DateTimeImmutable $timestamp): array;

    /**
     * Read events from stream within date range
     *
     * @param string $aggregateId The aggregate identifier
     * @param \DateTimeImmutable $from Start timestamp
     * @param \DateTimeImmutable $to End timestamp
     * @return EventInterface[] Events within range
     */
    public function readStreamBetween(
        string $aggregateId,
        \DateTimeImmutable $from,
        \DateTimeImmutable $to
    ): array;

    /**
     * Get total event count for stream
     *
     * @param string $aggregateId The aggregate identifier
     * @return int Number of events in stream
     */
    public function getEventCount(string $aggregateId): int;
}
```

**Requirements:** FUN-EVS-7202, FUN-EVS-7208, FUN-EVS-7209

---

### SnapshotRepositoryInterface

Interface for storing and retrieving aggregate snapshots.

```php
namespace Nexus\EventStream\Contracts;

interface SnapshotRepositoryInterface
{
    /**
     * Store snapshot of aggregate state
     *
     * @param SnapshotInterface $snapshot The snapshot to store
     * @return void
     */
    public function store(SnapshotInterface $snapshot): void;

    /**
     * Retrieve latest snapshot for aggregate
     *
     * @param string $aggregateId The aggregate identifier
     * @return SnapshotInterface|null Snapshot or null if not found
     */
    public function get(string $aggregateId): ?SnapshotInterface;

    /**
     * Delete snapshot for aggregate
     *
     * @param string $aggregateId The aggregate identifier
     * @return void
     */
    public function delete(string $aggregateId): void;

    /**
     * Check if snapshot exists
     *
     * @param string $aggregateId The aggregate identifier
     * @return bool True if snapshot exists
     */
    public function exists(string $aggregateId): bool;
}
```

**Requirements:** FUN-EVS-7210, PER-EVS-7302

---

### ProjectorInterface

Interface for rebuilding state from events (projections).

```php
namespace Nexus\EventStream\Contracts;

interface ProjectorInterface
{
    /**
     * Project events to rebuild state
     *
     * @param string $aggregateId The aggregate identifier
     * @param EventInterface[] $events Events to project
     * @return mixed Projected state
     */
    public function project(string $aggregateId, array $events): mixed;

    /**
     * Get projection name
     *
     * @return string Projection identifier
     */
    public function getName(): string;

    /**
     * Reset projection state
     *
     * @return void
     */
    public function reset(): void;
}
```

**Requirements:** FUN-EVS-7211, FUN-EVS-7212

---

### EventQueryInterface

Interface for querying events across multiple streams.

```php
namespace Nexus\EventStream\Contracts;

interface EventQueryInterface
{
    /**
     * Query events by type
     *
     * @param string $eventType Event class name
     * @param int $limit Maximum results
     * @param int $offset Pagination offset
     * @return EventInterface[] Matching events
     */
    public function findByType(string $eventType, int $limit = 100, int $offset = 0): array;

    /**
     * Query events within date range
     *
     * @param \DateTimeImmutable $from Start timestamp
     * @param \DateTimeImmutable $to End timestamp
     * @param int $limit Maximum results
     * @return EventInterface[] Events within range
     */
    public function findBetween(
        \DateTimeImmutable $from,
        \DateTimeImmutable $to,
        int $limit = 100
    ): array;

    /**
     * Query events by metadata
     *
     * @param array $metadata Metadata key-value pairs
     * @param int $limit Maximum results
     * @return EventInterface[] Matching events
     */
    public function findByMetadata(array $metadata, int $limit = 100): array;
}
```

**Requirements:** FUN-EVS-7218, FUN-EVS-7219

---

## Event Interfaces

### EventInterface

Base interface for all domain events.

```php
namespace Nexus\EventStream\Contracts;

interface EventInterface
{
    /**
     * Get event type identifier
     *
     * @return string Event type (e.g., 'AccountCredited')
     */
    public function getEventType(): string;

    /**
     * Get aggregate identifier
     *
     * @return string The aggregate this event belongs to
     */
    public function getAggregateId(): string;

    /**
     * Get event payload as array
     *
     * @return array Event data
     */
    public function getPayload(): array;

    /**
     * Get event metadata
     *
     * @return array Metadata (tenant_id, user_id, etc.)
     */
    public function getMetadata(): array;

    /**
     * Get event timestamp
     *
     * @return \DateTimeImmutable When event occurred
     */
    public function getOccurredAt(): \DateTimeImmutable;
}
```

---

### SnapshotInterface

Interface for aggregate snapshots.

```php
namespace Nexus\EventStream\Contracts;

interface SnapshotInterface
{
    /**
     * Get aggregate identifier
     *
     * @return string Aggregate ID
     */
    public function getAggregateId(): string;

    /**
     * Get snapshot state
     *
     * @return array Serialized aggregate state
     */
    public function getState(): array;

    /**
     * Get stream version at snapshot
     *
     * @return int Version number
     */
    public function getVersion(): int;

    /**
     * Get snapshot creation timestamp
     *
     * @return \DateTimeImmutable When snapshot was created
     */
    public function getCreatedAt(): \DateTimeImmutable;
}
```

---

## Value Objects

### StreamName

Value object for stream naming.

```php
namespace Nexus\EventStream\ValueObjects;

readonly class StreamName
{
    public function __construct(
        public string $value
    ) {}

    public function toString(): string;
    
    public function equals(StreamName $other): bool;
}
```

### EventVersion

Value object for event versioning.

```php
namespace Nexus\EventStream\ValueObjects;

readonly class EventVersion
{
    public function __construct(
        public int $value
    ) {}

    public function increment(): self;
    
    public function isGreaterThan(EventVersion $other): bool;
}
```

---

## Enums

### ProjectionStatus

Status of projection rebuild.

```php
namespace Nexus\EventStream\Enums;

enum ProjectionStatus: string
{
    case IDLE = 'idle';
    case RUNNING = 'running';
    case STOPPED = 'stopped';
    case ERROR = 'error';
}
```

### SnapshotStrategy

When to create snapshots.

```php
namespace Nexus\EventStream\Enums;

enum SnapshotStrategy: string
{
    case NEVER = 'never';
    case EVERY_N_EVENTS = 'every_n_events';
    case TIME_BASED = 'time_based';
    case MANUAL = 'manual';
}
```

---

## Exceptions

### EventStreamException

Base exception for all EventStream errors.

```php
namespace Nexus\EventStream\Exceptions;

class EventStreamException extends \RuntimeException
{
    // Base exception
}
```

### ConcurrencyException

Thrown when optimistic concurrency check fails.

```php
namespace Nexus\EventStream\Exceptions;

class ConcurrencyException extends EventStreamException
{
    public function __construct(
        string $aggregateId,
        int $expectedVersion,
        int $actualVersion
    ) {
        parent::__construct(
            "Concurrency conflict for {$aggregateId}: expected v{$expectedVersion}, actual v{$actualVersion}"
        );
    }
}
```

### StreamNotFoundException

Thrown when stream doesn't exist.

```php
namespace Nexus\EventStream\Exceptions;

class StreamNotFoundException extends EventStreamException
{
    public function __construct(string $aggregateId)
    {
        parent::__construct("Stream not found: {$aggregateId}");
    }
}
```

### SnapshotNotFoundException

Thrown when snapshot doesn't exist.

```php
namespace Nexus\EventStream\Exceptions;

class SnapshotNotFoundException extends EventStreamException
{
    public function __construct(string $aggregateId)
    {
        parent::__construct("Snapshot not found: {$aggregateId}");
    }
}
```

---

## Usage Patterns

### Pattern 1: Event Sourced Aggregate

```php
class Account
{
    private array $events = [];
    
    public static function fromEvents(array $events): self
    {
        $account = new self();
        foreach ($events as $event) {
            $account->apply($event);
        }
        return $account;
    }
    
    private function apply(EventInterface $event): void
    {
        match ($event::class) {
            AccountCreditedEvent::class => $this->balance += $event->amount,
            AccountDebitedEvent::class => $this->balance -= $event->amount,
        };
    }
}
```

### Pattern 2: Snapshot + Recent Events

```php
public function getAccount(string $accountId): Account
{
    // Try snapshot first
    $snapshot = $this->snapshots->get($accountId);
    
    if ($snapshot) {
        $events = $this->stream->readStreamFromVersion(
            $accountId,
            $snapshot->getVersion() + 1
        );
        $account = Account::fromSnapshot($snapshot);
    } else {
        $events = $this->stream->readStream($accountId);
        $account = new Account();
    }
    
    foreach ($events as $event) {
        $account->apply($event);
    }
    
    return $account;
}
```

### Pattern 3: Temporal Query

```php
public function getBalanceAt(
    string $accountId,
    \DateTimeImmutable $timestamp
): int {
    $events = $this->stream->readStreamUntil($accountId, $timestamp);
    
    $balance = 0;
    foreach ($events as $event) {
        $balance += match ($event::class) {
            AccountCreditedEvent::class => $event->amount,
            AccountDebitedEvent::class => -$event->amount,
            default => 0
        };
    }
    
    return $balance;
}
```

---

## Requirements Mapping

| Interface | Requirements Satisfied |
|-----------|------------------------|
| `EventStoreInterface` | ARC-EVS-7003, FUN-EVS-7201, FUN-EVS-7205, BUS-EVS-7105 |
| `StreamReaderInterface` | FUN-EVS-7202, FUN-EVS-7208, FUN-EVS-7209 |
| `SnapshotRepositoryInterface` | FUN-EVS-7210, PER-EVS-7302 |
| `ProjectorInterface` | FUN-EVS-7211, FUN-EVS-7212 |
| `EventQueryInterface` | FUN-EVS-7218, FUN-EVS-7219 |

See [REQUIREMENTS.md](../REQUIREMENTS.md) for complete requirements list.
