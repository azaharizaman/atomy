# Integration Guide: EventStream

Complete integration examples for Laravel and Symfony applications.

---

## Table of Contents

1. [Laravel Integration](#laravel-integration)
2. [Symfony Integration](#symfony-integration)
3. [Database Setup](#database-setup)
4. [Dependency Injection](#dependency-injection)
5. [Common Patterns](#common-patterns)
6. [Troubleshooting](#troubleshooting)

---

## Laravel Integration

### Step 1: Install Package

```bash
composer require nexus/event-stream:"*@dev"
```

### Step 2: Create Migrations

```php
// database/migrations/2025_11_24_000001_create_event_store_tables.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->char('id', 26)->primary(); // ULID
            $table->string('stream_name')->index();
            $table->string('event_type')->index();
            $table->json('event_data');
            $table->json('metadata')->nullable();
            $table->unsignedInteger('version');
            $table->timestamp('occurred_at')->index();
            $table->timestamps();
            
            $table->unique(['stream_name', 'version']);
        });

        Schema::create('snapshots', function (Blueprint $table) {
            $table->string('aggregate_id')->primary();
            $table->string('aggregate_type');
            $table->json('state');
            $table->unsignedInteger('version');
            $table->timestamps();
        });

        Schema::create('projections', function (Blueprint $table) {
            $table->string('name')->primary();
            $table->string('status');
            $table->unsignedBigInteger('position')->default(0);
            $table->json('state')->nullable();
            $table->timestamp('locked_until')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projections');
        Schema::dropIfExists('snapshots');
        Schema::dropIfExists('events');
    }
};
```

### Step 3: Create Eloquent Models

```php
// app/Models/Event.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    protected $fillable = [
        'id',
        'stream_name',
        'event_type',
        'event_data',
        'metadata',
        'version',
        'occurred_at',
    ];

    protected $casts = [
        'event_data' => 'array',
        'metadata' => 'array',
        'occurred_at' => 'datetime',
    ];

    public $incrementing = false;
    protected $keyType = 'string';
}
```

```php
// app/Models/Snapshot.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Snapshot extends Model
{
    protected $primaryKey = 'aggregate_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'aggregate_id',
        'aggregate_type',
        'state',
        'version',
    ];

    protected $casts = [
        'state' => 'array',
    ];
}
```

### Step 4: Implement Repository Interfaces

```php
// app/Repositories/EventStream/LaravelEventStore.php
namespace App\Repositories\EventStream;

use App\Models\Event;
use Illuminate\Support\Facades\DB;
use Nexus\EventStream\Contracts\EventInterface;
use Nexus\EventStream\Contracts\EventStoreInterface;
use Nexus\EventStream\Contracts\EventSerializerInterface;
use Nexus\EventStream\Exceptions\ConcurrencyException;
use Symfony\Component\Uid\Ulid;

class LaravelEventStore implements EventStoreInterface
{
    public function __construct(
        private readonly EventSerializerInterface $serializer
    ) {}

    public function append(
        string $aggregateId,
        EventInterface $event,
        ?int $expectedVersion = null
    ): void {
        DB::transaction(function () use ($aggregateId, $event, $expectedVersion) {
            $currentVersion = $this->getStreamVersion($aggregateId);

            if ($expectedVersion !== null && $currentVersion !== $expectedVersion) {
                throw new ConcurrencyException(
                    $aggregateId,
                    $expectedVersion,
                    $currentVersion
                );
            }

            $newVersion = $currentVersion + 1;

            Event::create([
                'id' => (string) new Ulid(),
                'stream_name' => $aggregateId,
                'event_type' => $event->getEventType(),
                'event_data' => $event->getPayload(),
                'metadata' => $event->getMetadata(),
                'version' => $newVersion,
                'occurred_at' => $event->getOccurredAt(),
            ]);
        });
    }

    public function appendBatch(
        string $aggregateId,
        array $events,
        ?int $expectedVersion = null
    ): void {
        DB::transaction(function () use ($aggregateId, $events, $expectedVersion) {
            $currentVersion = $this->getStreamVersion($aggregateId);

            if ($expectedVersion !== null && $currentVersion !== $expectedVersion) {
                throw new ConcurrencyException(
                    $aggregateId,
                    $expectedVersion,
                    $currentVersion
                );
            }

            foreach ($events as $event) {
                $currentVersion++;
                
                Event::create([
                    'id' => (string) new Ulid(),
                    'stream_name' => $aggregateId,
                    'event_type' => $event->getEventType(),
                    'event_data' => $event->getPayload(),
                    'metadata' => $event->getMetadata(),
                    'version' => $currentVersion,
                    'occurred_at' => $event->getOccurredAt(),
                ]);
            }
        });
    }

    public function getStreamVersion(string $aggregateId): int
    {
        return Event::where('stream_name', $aggregateId)
            ->max('version') ?? 0;
    }

    public function streamExists(string $aggregateId): bool
    {
        return Event::where('stream_name', $aggregateId)->exists();
    }

    public function deleteStream(string $aggregateId): void
    {
        Event::where('stream_name', $aggregateId)->delete();
    }
}
```

```php
// app/Repositories/EventStream/LaravelStreamReader.php
namespace App\Repositories\EventStream;

use App\Models\Event;
use Nexus\EventStream\Contracts\EventInterface;
use Nexus\EventStream\Contracts\StreamReaderInterface;
use Nexus\EventStream\Contracts\EventSerializerInterface;

class LaravelStreamReader implements StreamReaderInterface
{
    public function __construct(
        private readonly EventSerializerInterface $serializer
    ) {}

    public function readStream(string $aggregateId): array
    {
        return Event::where('stream_name', $aggregateId)
            ->orderBy('version')
            ->get()
            ->map(fn($event) => $this->serializer->deserialize($event))
            ->all();
    }

    public function readStreamFromVersion(string $aggregateId, int $fromVersion): array
    {
        return Event::where('stream_name', $aggregateId)
            ->where('version', '>=', $fromVersion)
            ->orderBy('version')
            ->get()
            ->map(fn($event) => $this->serializer->deserialize($event))
            ->all();
    }

    public function readStreamUntil(
        string $aggregateId,
        \DateTimeImmutable $timestamp
    ): array {
        return Event::where('stream_name', $aggregateId)
            ->where('occurred_at', '<=', $timestamp)
            ->orderBy('version')
            ->get()
            ->map(fn($event) => $this->serializer->deserialize($event))
            ->all();
    }

    public function readStreamBetween(
        string $aggregateId,
        \DateTimeImmutable $from,
        \DateTimeImmutable $to
    ): array {
        return Event::where('stream_name', $aggregateId)
            ->whereBetween('occurred_at', [$from, $to])
            ->orderBy('version')
            ->get()
            ->map(fn($event) => $this->serializer->deserialize($event))
            ->all();
    }

    public function getEventCount(string $aggregateId): int
    {
        return Event::where('stream_name', $aggregateId)->count();
    }
}
```

```php
// app/Repositories/EventStream/LaravelSnapshotRepository.php
namespace App\Repositories\EventStream;

use App\Models\Snapshot;
use Nexus\EventStream\Contracts\SnapshotInterface;
use Nexus\EventStream\Contracts\SnapshotRepositoryInterface;
use Nexus\EventStream\ValueObjects\Snapshot as SnapshotValueObject;

class LaravelSnapshotRepository implements SnapshotRepositoryInterface
{
    public function store(SnapshotInterface $snapshot): void
    {
        Snapshot::updateOrCreate(
            ['aggregate_id' => $snapshot->getAggregateId()],
            [
                'aggregate_type' => get_class($snapshot),
                'state' => $snapshot->getState(),
                'version' => $snapshot->getVersion(),
            ]
        );
    }

    public function get(string $aggregateId): ?SnapshotInterface
    {
        $snapshot = Snapshot::find($aggregateId);

        if (!$snapshot) {
            return null;
        }

        return new SnapshotValueObject(
            aggregateId: $snapshot->aggregate_id,
            state: $snapshot->state,
            version: $snapshot->version,
            createdAt: $snapshot->created_at
        );
    }

    public function delete(string $aggregateId): void
    {
        Snapshot::where('aggregate_id', $aggregateId)->delete();
    }

    public function exists(string $aggregateId): bool
    {
        return Snapshot::where('aggregate_id', $aggregateId)->exists();
    }
}
```

### Step 5: Register in Service Provider

```php
// app/Providers/EventStreamServiceProvider.php
namespace App\Providers;

use App\Repositories\EventStream\LaravelEventStore;
use App\Repositories\EventStream\LaravelStreamReader;
use App\Repositories\EventStream\LaravelSnapshotRepository;
use Illuminate\Support\ServiceProvider;
use Nexus\EventStream\Contracts\EventStoreInterface;
use Nexus\EventStream\Contracts\StreamReaderInterface;
use Nexus\EventStream\Contracts\SnapshotRepositoryInterface;
use Nexus\EventStream\Contracts\EventSerializerInterface;
use Nexus\EventStream\Services\JsonEventSerializer;

class EventStreamServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind serializer
        $this->app->singleton(
            EventSerializerInterface::class,
            JsonEventSerializer::class
        );

        // Bind repositories
        $this->app->singleton(
            EventStoreInterface::class,
            LaravelEventStore::class
        );

        $this->app->singleton(
            StreamReaderInterface::class,
            LaravelStreamReader::class
        );

        $this->app->singleton(
            SnapshotRepositoryInterface::class,
            LaravelSnapshotRepository::class
        );
    }
}
```

Register in `config/app.php`:
```php
'providers' => [
    // ...
    App\Providers\EventStreamServiceProvider::class,
],
```

### Step 6: Usage in Domain Services

```php
// app/Finance/Services/LedgerService.php
namespace App\Finance\Services;

use Nexus\EventStream\Contracts\EventStoreInterface;
use App\Finance\Events\AccountCreditedEvent;

class LedgerService
{
    public function __construct(
        private readonly EventStoreInterface $eventStore
    ) {}

    public function creditAccount(
        string $accountId,
        int $amount,
        string $journalEntryId,
        string $description
    ): void {
        $event = new AccountCreditedEvent(
            accountId: $accountId,
            amount: $amount,
            journalEntryId: $journalEntryId,
            description: $description
        );

        $this->eventStore->append($accountId, $event);
    }
}
```

---

## Symfony Integration

### Step 1: Install Package

```bash
composer require nexus/event-stream:"*@dev"
```

### Step 2: Configure Services

```yaml
# config/services.yaml
services:
    # Event Serializer
    Nexus\EventStream\Contracts\EventSerializerInterface:
        class: Nexus\EventStream\Services\JsonEventSerializer

    # Event Store
    Nexus\EventStream\Contracts\EventStoreInterface:
        class: App\Infrastructure\EventStream\DoctrineEventStore
        arguments:
            - '@doctrine.orm.entity_manager'
            - '@Nexus\EventStream\Contracts\EventSerializerInterface'

    # Stream Reader
    Nexus\EventStream\Contracts\StreamReaderInterface:
        class: App\Infrastructure\EventStream\DoctrineStreamReader
        arguments:
            - '@doctrine.orm.entity_manager'
            - '@Nexus\EventStream\Contracts\EventSerializerInterface'

    # Snapshot Repository
    Nexus\EventStream\Contracts\SnapshotRepositoryInterface:
        class: App\Infrastructure\EventStream\DoctrineSnapshotRepository
        arguments:
            - '@doctrine.orm.entity_manager'
```

### Step 3: Create Doctrine Entities

```php
// src/Infrastructure/EventStream/Entity/Event.php
namespace App\Infrastructure\EventStream\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'events')]
#[ORM\UniqueConstraint(columns: ['stream_name', 'version'])]
class Event
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 26)]
    private string $id;

    #[ORM\Column(type: 'string')]
    private string $streamName;

    #[ORM\Column(type: 'string')]
    private string $eventType;

    #[ORM\Column(type: 'json')]
    private array $eventData;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $metadata;

    #[ORM\Column(type: 'integer')]
    private int $version;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $occurredAt;

    // Getters and setters...
}
```

### Step 4: Usage in Domain

```php
// src/Finance/Application/Service/LedgerService.php
namespace App\Finance\Application\Service;

use Nexus\EventStream\Contracts\EventStoreInterface;
use App\Finance\Domain\Event\AccountCreditedEvent;

class LedgerService
{
    public function __construct(
        private readonly EventStoreInterface $eventStore
    ) {}

    public function creditAccount(
        string $accountId,
        int $amount,
        string $journalEntryId
    ): void {
        $event = new AccountCreditedEvent(
            accountId: $accountId,
            amount: $amount,
            journalEntryId: $journalEntryId,
            description: 'Credit to account'
        );

        $this->eventStore->append($accountId, $event);
    }
}
```

---

## Common Patterns

### Pattern 1: Event Listener for Projections

```php
// Laravel Event Listener
namespace App\Listeners;

use App\Finance\Events\AccountCreditedEvent;
use App\Projections\AccountBalanceProjection;

class UpdateAccountBalanceProjection
{
    public function __construct(
        private readonly AccountBalanceProjection $projection
    ) {}

    public function handle(AccountCreditedEvent $event): void
    {
        $this->projection->applyAccountCredited($event);
    }
}
```

### Pattern 2: Snapshot Strategy

```php
// Create snapshot every 100 events
public function appendWithSnapshot(
    string $aggregateId,
    EventInterface $event
): void {
    $this->eventStore->append($aggregateId, $event);
    
    $version = $this->eventStore->getStreamVersion($aggregateId);
    
    if ($version % 100 === 0) {
        $state = $this->rebuildState($aggregateId);
        $snapshot = new Snapshot($aggregateId, $state, $version);
        $this->snapshotRepository->store($snapshot);
    }
}
```

### Pattern 3: Temporal Query with Caching

```php
public function getBalanceAt(
    string $accountId,
    \DateTimeImmutable $timestamp
): int {
    $cacheKey = "balance:{$accountId}:{$timestamp->format('Y-m-d')}";
    
    return cache()->remember($cacheKey, 3600, function () use ($accountId, $timestamp) {
        $events = $this->streamReader->readStreamUntil($accountId, $timestamp);
        return $this->calculateBalance($events);
    });
}
```

---

## Troubleshooting

### Issue: ConcurrencyException on High Traffic

**Solution:** Implement retry logic with exponential backoff

```php
use Nexus\EventStream\Exceptions\ConcurrencyException;

public function appendWithRetry(
    string $aggregateId,
    EventInterface $event,
    int $maxRetries = 3
): void {
    $attempt = 0;
    
    while ($attempt < $maxRetries) {
        try {
            $version = $this->eventStore->getStreamVersion($aggregateId);
            $this->eventStore->append($aggregateId, $event, $version);
            return;
        } catch (ConcurrencyException $e) {
            $attempt++;
            if ($attempt >= $maxRetries) {
                throw $e;
            }
            usleep(100000 * $attempt); // 100ms, 200ms, 300ms
        }
    }
}
```

### Issue: Slow Event Replay

**Solution:** Use snapshots and optimize queries

```php
// Add database indexes
Schema::table('events', function (Blueprint $table) {
    $table->index(['stream_name', 'version']);
    $table->index(['occurred_at']);
});

// Implement snapshot strategy
public function rebuildWithSnapshot(string $aggregateId): Aggregate
{
    $snapshot = $this->snapshotRepository->get($aggregateId);
    
    if ($snapshot) {
        $aggregate = Aggregate::fromSnapshot($snapshot);
        $events = $this->streamReader->readStreamFromVersion(
            $aggregateId,
            $snapshot->getVersion() + 1
        );
    } else {
        $aggregate = new Aggregate();
        $events = $this->streamReader->readStream($aggregateId);
    }
    
    foreach ($events as $event) {
        $aggregate->apply($event);
    }
    
    return $aggregate;
}
```

### Issue: Event Serialization Errors

**Solution:** Ensure events are properly serializable

```php
// Event class must be serializable
readonly class AccountCreditedEvent implements EventInterface
{
    // Use primitive types only
    public function __construct(
        public string $accountId,
        public int $amount,        // ✅ int, not Money object
        public string $journalEntryId,
        public string $description
    ) {}
    
    public function getPayload(): array
    {
        return [
            'account_id' => $this->accountId,
            'amount' => $this->amount,
            'journal_entry_id' => $this->journalEntryId,
            'description' => $this->description,
        ];
    }
}
```

---

## Performance Optimization

### Index Strategy

```sql
-- Critical indexes for event store
CREATE INDEX idx_events_stream_version ON events(stream_name, version);
CREATE INDEX idx_events_occurred_at ON events(occurred_at);
CREATE INDEX idx_events_type ON events(event_type);
CREATE INDEX idx_events_stream_occurred ON events(stream_name, occurred_at);
```

### Projection Optimization

```php
// Use chunked processing for large event streams
public function rebuildProjection(): void
{
    Event::where('event_type', AccountCreditedEvent::class)
        ->orderBy('occurred_at')
        ->chunk(1000, function ($events) {
            foreach ($events as $event) {
                $this->projection->apply($event);
            }
        });
}
```

---

## Next Steps

- Review [API Reference](api-reference.md) for complete interface documentation
- See [Examples](examples/) for more usage patterns
- Read [Getting Started](getting-started.md) for conceptual overview
