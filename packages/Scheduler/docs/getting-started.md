# Getting Started with Nexus\Scheduler

The Scheduler package coordinates **when** work should run without coupling to **what** the work does or **how** it is executed. Use this guide to wire the package into any PHP 8.3+ application.

## Prerequisites

- PHP 8.3 or newer with `declare(strict_types=1);`
- Composer 2.6+
- PSR-11 compliant container (Laravel, Symfony, Laminas, etc.)
- Queue/worker implementation (Laravel Queue, Symfony Messenger, custom worker)
- Optional: `dragonmantank/cron-expression` for cron-style recurrences

## Installation

```bash
composer require nexus/scheduler:"*@dev"

# Optional cron helper
composer require dragonmantank/cron-expression
```

## When to Use This Package

✅ Schedule business workflows (exports, reminders, maintenance)

✅ Centralize retry logic and recurring job definitions

✅ Keep domain packages free from cron/queue specifics

❌ Do **not** use for ad-hoc queue dispatch (use Nexus\Notifier or direct queue clients instead)

❌ Do **not** embed persistence logic inside the package (provide repositories from application layer)

## Core Concepts

### ScheduleManager
Facade service orchestrating scheduling, execution, retries, and recurrence expansion.

### ScheduleDefinition & ScheduledJob
Immutable value objects capturing requested job metadata (type, payload, run time, recurrence).

### JobHandlerInterface
Domain packages implement this contract to execute specific job types and return `JobResult` that signals retry intent.

### JobQueueInterface & ScheduleRepositoryInterface
Interfaces the consuming application must implement to persist jobs and enqueue work. Keeps package framework agnostic.

### ClockInterface
Injectable time source that makes due checks deterministic for testing and simulation.

## Basic Configuration

### 1. Implement Persistence

```php
namespace App\Scheduler;

use Nexus\Scheduler\Contracts\ScheduleRepositoryInterface;
use Nexus\Scheduler\ValueObjects\ScheduledJobInterface;

final readonly class DbScheduleRepository implements ScheduleRepositoryInterface
{
	public function __construct(private Connection $db) {}

	public function save(ScheduledJobInterface $job): void
	{
		// Upsert ULID + tenant scoped record, persist recurrence metadata
	}

	public function findDue(\DateTimeImmutable $asOf): array
	{
		// Return pending jobs where run_at <= $asOf and status = pending
	}

	// Implement remaining contract methods (delete, findById, updateStatus, etc.)
}
```

### 2. Implement Queue Adapter

```php
namespace App\Scheduler;

use Nexus\Scheduler\Contracts\JobQueueInterface;
use Nexus\Scheduler\ValueObjects\ScheduledJobInterface;

final readonly class LaravelJobQueue implements JobQueueInterface
{
	public function dispatch(ScheduledJobInterface $job, ?int $delaySeconds = null): void
	{
		dispatch((new ProcessScheduledJob($job->id()))->delay($delaySeconds ?? 0));
	}
}
```

### 3. Provide Clock Implementation

```php
use Nexus\Scheduler\Contracts\ClockInterface;

final readonly class SystemClock implements ClockInterface
{
	public function now(): \DateTimeImmutable
	{
		return new \DateTimeImmutable();
	}
}
```

### 4. Register Job Handlers

Domain packages implement `JobHandlerInterface` and are tagged (Laravel) or autowired (Symfony) so `ScheduleManager` receives them as an iterable.

### 5. Bind Interfaces in Container

```php
$this->app->singleton(ScheduleRepositoryInterface::class, DbScheduleRepository::class);
$this->app->singleton(JobQueueInterface::class, LaravelJobQueue::class);
$this->app->singleton(ClockInterface::class, SystemClock::class);
$this->app->tag([ExportReportHandler::class], 'scheduler.handlers');
```

## Your First Integration

```php
use Nexus\Scheduler\Services\ScheduleManager;
use Nexus\Scheduler\ValueObjects\ScheduleDefinition;
use Nexus\Scheduler\Enums\JobType;

final readonly class ExportController
{
	public function __construct(private ScheduleManager $scheduler) {}

	public function scheduleExport(string $reportId): void
	{
		$definition = new ScheduleDefinition(
			jobType: JobType::EXPORT_REPORT,
			targetId: $reportId,
			runAt: new \DateTimeImmutable('+15 minutes'),
			payload: ['format' => 'pdf']
		);

		$this->scheduler->schedule($definition);
	}
}
```

## Troubleshooting

| Issue | Cause | Resolution |
|-------|-------|------------|
| `NoHandlerFoundException` | Handler not tagged/registered | Ensure `JobHandlerInterface` implementations are tagged or autowired and reachable by the container |
| Jobs never execute | Cron/worker not running | Schedule recurring command (e.g., `php artisan scheduler:process`) and ensure queue workers listen for dispatched jobs |
| Recurrence drift | Wrong timezone handling | Always store `run_at` in UTC and pass a timezone-aware `ClockInterface` implementation |
| Duplicate executions | Repository lacks locking | Use database-level locking (`SELECT ... FOR UPDATE`) when fetching due jobs |

## Next Steps

1. Review the [API Reference](api-reference.md) for full contract details.
2. Follow the [Integration Guide](integration-guide.md) for Laravel and Symfony wiring.
3. Explore runnable scripts under [docs/examples](examples/) to simulate schedule lifecycles.
