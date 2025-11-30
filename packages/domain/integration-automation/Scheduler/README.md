# Nexus\Scheduler

A framework-agnostic job scheduling engine that manages future-dated instructions through contracts, delegating execution to domain packages and persistence to the application layer.

## Overview

The **Nexus\Scheduler** package serves as the **central repository for future-dated instructions**. It manages *when* an action should happen, but delegates *what* the action is (domain logic) and *how* the action is executed (job runner/queue).

### Core Principles

1. **Complete Decoupling**: Stateless design with all persistence via `ScheduleRepositoryInterface`
2. **Execution Agnostic**: Unaware of runtime environment (Laravel Queue, pure Cron, etc.)
3. **Handler-Based**: Domain packages implement `JobHandlerInterface` for their specific job types
4. **Time Control**: Testable via `ClockInterface` injection
5. **Hybrid Retry**: Handlers signal intent, engine manages execution

## Architecture

### The Scheduling Paradigm

```
┌─────────────────────────────────────────────────────────────────┐
│                      DOMAIN PACKAGE                              │
│  (e.g., Nexus\Export, Nexus\Workflow)                           │
│                                                                   │
│  Calls: $scheduler->schedule(ScheduleDefinition)                │
│  Implements: ExportReportHandler implements JobHandlerInterface │
│  Tags: $app->tag([ExportReportHandler::class], 'scheduler.han..│
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                   NEXUS\SCHEDULER (THIS PACKAGE)                │
│                                                                   │
│  ScheduleManager ──► ExecutionEngine ──► JobHandlerInterface   │
│         │                    │                                   │
│         │                    └──► Interprets JobResult          │
│         │                         (shouldRetry, retryDelay)      │
│         │                                                        │
│         └──► RecurrenceEngine (cron, intervals)                 │
│                                                                   │
│  Contracts: ScheduleRepositoryInterface, JobQueueInterface      │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                     APPLICATION LAYER                            │
│                      (Nexus\Atomy)                               │
│                                                                   │
│  DbScheduleRepository ──► Eloquent Model                        │
│  LaravelJobQueue ──► Laravel Queue System                       │
│  SystemClock ──► DateTimeImmutable                              │
│  Tagged Handler Discovery ──► Service Provider                  │
└─────────────────────────────────────────────────────────────────┘
```

### Execution Flow

1. **Scheduling Phase** (Domain → Scheduler)
   - Domain package calls `$scheduler->schedule(ScheduleDefinition)`
   - `ScheduleManager` creates `ScheduledJob` value object
   - Persists via `ScheduleRepositoryInterface`

2. **Execution Phase** (Cron → Queue → Handler)
   - External cron job calls `ProcessScheduledJobs` command
   - Command retrieves due jobs via `$manager->getDueJobs()`
   - For each job, dispatches to queue via `JobQueueInterface`
   - Queue worker invokes appropriate `JobHandlerInterface::handle()`
   - Handler returns `JobResult` with retry intent
   - `ExecutionEngine` updates status and re-queues if needed

3. **Retry Strategy** (Hybrid Delegation)
   - Handler decides: "Should retry? Custom delay?"
   - Engine executes: Status update, queue dispatch, exponential backoff
   - Clean separation: Domain logic (intent) vs. Infrastructure (mechanism)

## Installation

### 1. Install Package

```bash
composer require nexus/scheduler:*@dev
```

### 2. Optional: Install Cron Expression Support

```bash
composer require dragonmantank/cron-expression
```

### 3. Implement Required Contracts in Your Application

```php
// app/Repositories/DbScheduleRepository.php
class DbScheduleRepository implements ScheduleRepositoryInterface
{
    public function save(ScheduledJobInterface $job): void { /* ... */ }
    public function findDue(DateTimeImmutable $asOf): array { /* ... */ }
    // ... other methods
}

// app/Services/LaravelJobQueue.php
class LaravelJobQueue implements JobQueueInterface
{
    public function dispatch(ScheduledJobInterface $job, ?int $delaySeconds = null): void
    {
        // Dispatch to Laravel queue
    }
}

// app/Services/SystemClock.php
class SystemClock implements ClockInterface
{
    public function now(): DateTimeImmutable
    {
        return new DateTimeImmutable();
    }
}
```

### 4. Bind Interfaces in Service Provider

```php
// app/Providers/SchedulerServiceProvider.php
public function register(): void
{
    // Bind infrastructure
    $this->app->singleton(ScheduleRepositoryInterface::class, DbScheduleRepository::class);
    $this->app->singleton(JobQueueInterface::class, LaravelJobQueue::class);
    $this->app->singleton(ClockInterface::class, SystemClock::class);
    $this->app->singleton(CalendarExporterInterface::class, NullCalendarExporter::class);
    
    // Inject tagged handlers into ScheduleManager
    $this->app->singleton(ScheduleManager::class, function ($app) {
        return new ScheduleManager(
            repository: $app->make(ScheduleRepositoryInterface::class),
            queue: $app->make(JobQueueInterface::class),
            clock: $app->make(ClockInterface::class),
            handlers: $app->tagged('scheduler.handlers'),
            logger: $app->make(LoggerInterface::class)
        );
    });
}
```

## Usage

### 1. Schedule a Job

```php
use Nexus\Scheduler\Services\ScheduleManager;
use Nexus\Scheduler\ValueObjects\ScheduleDefinition;
use Nexus\Scheduler\Enums\JobType;
use Nexus\Scheduler\ValueObjects\ScheduleRecurrence;

$scheduler = app(ScheduleManager::class);

// One-time job
$job = $scheduler->schedule(new ScheduleDefinition(
    jobType: JobType::EXPORT_REPORT,
    targetId: '01JCV9X...',  // ULID of entity
    runAt: new DateTimeImmutable('+1 hour'),
    payload: ['format' => 'pdf', 'templateId' => '01JCV...']
));

// Recurring job (daily at 9 AM)
$job = $scheduler->schedule(new ScheduleDefinition(
    jobType: JobType::DOCUMENT_SHREDDING,
    targetId: '01JCV9Y...',
    runAt: new DateTimeImmutable('tomorrow 09:00'),
    recurrence: new ScheduleRecurrence(
        type: RecurrenceType::DAILY,
        interval: 1
    ),
    payload: ['retentionDays' => 90]
));

// Cron-based recurrence (every Monday at 8 AM)
$job = $scheduler->schedule(new ScheduleDefinition(
    jobType: JobType::WORK_ORDER_START,
    targetId: '01JCV9Z...',
    runAt: new DateTimeImmutable('next Monday 08:00'),
    recurrence: new ScheduleRecurrence(
        type: RecurrenceType::CRON,
        cronExpression: '0 8 * * 1'
    )
));
```

### 2. Implement a Job Handler

```php
namespace App\Handlers;

use Nexus\Scheduler\Contracts\JobHandlerInterface;
use Nexus\Scheduler\ValueObjects\ScheduledJob;
use Nexus\Scheduler\ValueObjects\JobResult;
use Nexus\Scheduler\Enums\JobType;

class ExportReportHandler implements JobHandlerInterface
{
    public function __construct(
        private readonly ExportService $exporter
    ) {}
    
    public function supports(JobType $jobType): bool
    {
        return $jobType === JobType::EXPORT_REPORT;
    }
    
    public function handle(ScheduledJob $job): JobResult
    {
        try {
            $this->exporter->generate(
                templateId: $job->payload['templateId'],
                format: $job->payload['format']
            );
            
            return JobResult::success(
                output: ['fileUrl' => 'https://...']
            );
            
        } catch (TemporaryFailureException $e) {
            // Retry with custom 5-minute delay
            return JobResult::failure(
                error: $e->getMessage(),
                shouldRetry: true,
                retryDelaySeconds: 300
            );
            
        } catch (PermanentFailureException $e) {
            // Don't retry
            return JobResult::failure(
                error: $e->getMessage(),
                shouldRetry: false
            );
        }
    }
}
```

### 3. Register Handler (Tagged Service)

```php
// app/Providers/ExportServiceProvider.php
public function register(): void
{
    // Tag handler for automatic discovery
    $this->app->tag([ExportReportHandler::class], 'scheduler.handlers');
}
```

### 4. Process Scheduled Jobs (Cron)

```php
// app/Console/Commands/ProcessScheduledJobs.php
public function handle(ScheduleManager $scheduler): int
{
    $dueJobs = $scheduler->getDueJobs();
    
    foreach ($dueJobs as $job) {
        // Dispatches to queue, queue worker invokes handler
        $scheduler->executeJob($job->id);
    }
    
    return 0;
}
```

Add to your cron:
```
* * * * * php artisan schedule:process
```

## Handler Registration Pattern

The package uses **tagged services** for handler discovery, avoiding tight coupling to concrete domain classes.

### Domain Package Responsibility

1. Implement `JobHandlerInterface`
2. Tag in service provider: `$app->tag([YourHandler::class], 'scheduler.handlers')`

### Scheduler Package Responsibility

1. Receive `iterable $handlers` via constructor injection
2. Build internal `JobType => HandlerInterface` mapping
3. Dispatch to appropriate handler based on job type

**Zero coupling**: The scheduler never knows concrete handler class names.

## Retry Strategy

### Hybrid Delegation Model

**Handler (Domain Logic)**: Decides retry *intent*
```php
return JobResult::failure(
    error: 'API rate limit exceeded',
    shouldRetry: true,
    retryDelaySeconds: 300  // Custom 5-minute delay
);
```

**ExecutionEngine (Infrastructure)**: Manages retry *mechanism*
- Updates job status (`FAILED` → `PENDING`)
- Increments `retry_count`
- Dispatches to queue with specified delay
- Applies exponential backoff if no custom delay
- Marks as `FAILED_PERMANENT` if `shouldRetry === false`

### Decision Flowchart

```
JobHandler::handle() returns JobResult
              │
              ▼
    ┌─────────────────────┐
    │  shouldRetry: bool  │
    └─────────────────────┘
              │
         ┌────┴────┐
         │         │
        YES        NO
         │         │
         │         └──► Mark FAILED_PERMANENT
         │
         ▼
┌────────────────────────┐
│ retryDelaySeconds: ?int│
└────────────────────────┘
         │
    ┌────┴────┐
    │         │
  NULL     CUSTOM
    │         │
    │         └──► Re-queue with custom delay
    │
    └──► Apply exponential backoff
         (60s, 120s, 240s, ...)
```

## Value Objects

### ScheduledJob

Immutable representation of a scheduled job with business logic methods:

```php
$job->isDue($clock);           // bool: Is it time to run?
$job->isOverdue($clock);       // bool: Past runAt time?
$job->getNextRunTime($clock);  // ?DateTimeImmutable: Next recurrence
$job->canExecute();            // bool: Is status PENDING?
```

### ScheduleRecurrence

Defines repetition rules:

```php
// Simple interval
new ScheduleRecurrence(
    type: RecurrenceType::DAILY,
    interval: 2  // Every 2 days
);

// Cron expression (requires dragonmantank/cron-expression)
new ScheduleRecurrence(
    type: RecurrenceType::CRON,
    cronExpression: '0 9 * * 1-5'  // Weekdays at 9 AM
);
```

### JobResult

Handler's response with retry intent:

```php
// Success
JobResult::success(output: ['recordsProcessed' => 150]);

// Retriable failure
JobResult::failure(
    error: 'Connection timeout',
    shouldRetry: true,
    retryDelaySeconds: 60
);

// Permanent failure
JobResult::failure(
    error: 'Invalid configuration',
    shouldRetry: false
);
```

## Enums

### JobType

Extensible enum for domain-specific job types:

```php
enum JobType: string
{
    case EXPORT_REPORT = 'export_report';
    case DOCUMENT_SHREDDING = 'document_shredding';
    case WORK_ORDER_START = 'work_order_start';
    case SEND_REMINDER = 'send_reminder';
    // Domain packages add their own types
}
```

### JobStatus

Lifecycle state with transition validation:

```php
enum JobStatus: string
{
    case PENDING = 'pending';
    case RUNNING = 'running';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case FAILED_PERMANENT = 'failed_permanent';
    case CANCELED = 'canceled';
    
    public function canTransitionTo(JobStatus $newStatus): bool;
    public function canExecute(): bool;
    public function isFinal(): bool;
}
```

## Testing

### Time Control

Use `ClockInterface` for deterministic testing:

```php
$mockClock = new class implements ClockInterface {
    private DateTimeImmutable $now;
    
    public function now(): DateTimeImmutable {
        return $this->now;
    }
    
    public function setTime(DateTimeImmutable $time): void {
        $this->now = $time;
    }
};

// Test isDue() logic
$mockClock->setTime(new DateTimeImmutable('2025-01-15 08:55:00'));
$this->assertFalse($job->isDue($mockClock));

$mockClock->setTime(new DateTimeImmutable('2025-01-15 09:00:00'));
$this->assertTrue($job->isDue($mockClock));
```

### Running the Package Test Suite

The Scheduler package now ships with a dedicated PHPUnit 11 smoke suite plus deterministic in-memory adapters under `tests/Support/`. Run it locally before contributing new functionality:

```bash
cd packages/Scheduler
composer install        # once, to pull phpunit/phpunit via require-dev
composer test           # executes phpunit -c phpunit.xml.dist
# composer test:coverage  # optional: generates code coverage when Xdebug/PCOV is enabled
```

What the suite covers today:

- `ScheduleManagerTest` validates successful dispatch, permanent-failure handling, and recurring job rollover.
- Support doubles (`MutableClock`, `InMemoryScheduleRepository`, `TrackingJobQueue`, `CallbackJobHandler`) keep tests framework-agnostic.

Future work will extend coverage across value objects, the recurrence engine, and execution engine edge cases. Contributions should include corresponding tests that reuse the provided adapters or add new deterministic doubles inside `tests/Support/`.

## Future Features (v2)

### Calendar Export

The `CalendarExporterInterface` is defined but bound to `NullCalendarExporter` (throws `FeatureNotImplementedException`) in v1.

Planned v2 features:
- Generate iCal files for scheduled jobs
- Google Calendar URL generation
- Outlook integration

## 📖 Documentation

### Package Documentation
- [Getting Started Guide](docs/getting-started.md)
- [API Reference](docs/api-reference.md)
- [Integration Guide](docs/integration-guide.md)
- [Basic Usage Example](docs/examples/basic-usage.php)
- [Advanced Usage Example](docs/examples/advanced-usage.php)

### Additional Resources
- `IMPLEMENTATION_SUMMARY.md` - Implementation progress
- `REQUIREMENTS.md` - Requirements
- `TEST_SUITE_SUMMARY.md` - Tests
- `VALUATION_MATRIX.md` - Valuation


## License

MIT License - see LICENSE file for details.

## Support

For issues, questions, or contributions, please refer to the main Nexus ERP repository.
