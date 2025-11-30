# API Reference: Nexus\Scheduler

This document lists the public contracts, services, value objects, enums, and exceptions shipped with the Scheduler package. All namespaces are rooted under `Nexus\Scheduler`.

## Contracts (src/Contracts)

### ScheduleManagerInterface
Coordinates scheduling, execution, and inspection of jobs.

```php
public function schedule(ScheduleDefinition $definition): ScheduledJob;
public function preview(ScheduleDefinition $definition): ScheduledJob;
public function getDueJobs(?\DateTimeImmutable $asOf = null): array;
public function executeJob(string $jobId): void;
public function cancel(string $jobId, string $reason): void;
```

**Throws:**
- `JobNotFoundException` if job does not exist
- `InvalidJobStateException` if attempting to execute/cancel a non-pending job

### ScheduleRepositoryInterface
Persistence abstraction for scheduled jobs.

```php
public function save(ScheduledJobInterface $job): void;
public function update(ScheduledJobInterface $job): void;
public function findById(string $id): ?ScheduledJobInterface;
/** @return array<ScheduledJobInterface> */
public function findDue(\DateTimeImmutable $asOf): array;
public function delete(string $id): void;
public function exists(string $id): bool;
```

Repositories must enforce tenant isolation and handle optimistic/pessimistic locking.

### JobQueueInterface
Dispatches due jobs to the application's worker infrastructure.

```php
public function dispatch(ScheduledJobInterface $job, ?int $delaySeconds = null): void;
```

### JobHandlerInterface
Implemented by domain packages to execute their job types.

```php
public function supports(JobType $jobType): bool;
public function handle(ScheduledJob $job): JobResult;
```

Handlers must be stateless and idempotent. `handle()` returns `JobResult::success()` or `JobResult::failure()` to guide retry logic.

### JobFinderInterface
Utility contract that allows `ScheduleManager` to resolve handlers lazily.

```php
public function findFor(JobType $jobType): JobHandlerInterface;
```

### ClockInterface
Abstraction over current time for deterministic testing.

```php
public function now(): \DateTimeImmutable;
```

### CalendarExporterInterface
Extension point (v2 roadmap) for emitting iCal/ICS artifacts. Default implementation (`NullCalendarExporter`) throws `FeatureNotImplementedException`.

## Services (src/Services)

### ScheduleManager
Concrete orchestrator implementing `ScheduleManagerInterface`.

**Constructor Dependencies:**
- `ScheduleRepositoryInterface $repository`
- `JobQueueInterface $queue`
- `ClockInterface $clock`
- `iterable<JobHandlerInterface> $handlers`
- `?LoggerInterface $logger`
- `CalendarExporterInterface $calendarExporter`

**Key Methods:**
- `schedule(ScheduleDefinition $definition): ScheduledJob` – Persists new job and schedules first occurrence
- `getDueJobs(?\DateTimeImmutable $asOf = null): array` – Returns pending jobs with `runAt <= asOf`
- `executeJob(string $jobId): void` – Fetches job, runs through `ExecutionEngine`, updates status, handles retries
- `preview(ScheduleDefinition $definition): ScheduledJob` – Builds job without persistence for UI preview
- `cancel(string $jobId, string $reason): void` – Sets status to `JobStatus::CANCELED`

### Core\Engine\ExecutionEngine
Internal component that:
- Validates job state transitions
- Invokes the resolved handler
- Applies retry policy based on `JobResult`
- Emits telemetry hooks (via logger)

### Core\Engine\RecurrenceEngine
Calculates next run occurrences for interval and cron recurrences. Accepts optional cron-expression dependency if available.

## Value Objects (src/ValueObjects)

### ScheduleDefinition
Immutable request model used when creating jobs.

```php
public function __construct(
	JobType $jobType,
	string $targetId,
	\DateTimeImmutable $runAt,
	array $payload = [],
	?ScheduleRecurrence $recurrence = null,
	?string $tenantId = null,
	?string $groupKey = null,
	?\DateInterval $timeToLive = null
)
```

Validates payload size, ensures future `runAt`, and normalizes tenant/group metadata.

### ScheduledJob
Represents persisted state.

Important methods:
- `id(): string`
- `status(): JobStatus`
- `isDue(ClockInterface $clock): bool`
- `withStatus(JobStatus $status): self`
- `nextRunTime(ClockInterface $clock): ?\DateTimeImmutable`
- `incrementRetryCount(): self`

### ScheduleRecurrence
Encapsulates recurrence intent.

Factory helpers:
- `ScheduleRecurrence::interval(RecurrenceType $type, int $interval)`
- `ScheduleRecurrence::cron(string $expression)`

### JobResult
Handler response controlling retries.

```php
JobResult::success(array $output = []);
JobResult::failure(string $error, bool $shouldRetry, ?int $retryDelaySeconds = null, array $context = []);
```

## Enums (src/Enums)

- `JobType` – Extendable catalog of job identifiers (string-backed). Consumers add domain-specific cases via composition.
- `JobStatus` – `PENDING`, `RUNNING`, `COMPLETED`, `FAILED`, `FAILED_PERMANENT`, `CANCELED`. Provides helpers `canTransitionTo()`, `canExecute()`, `isFinal()`.
- `RecurrenceType` – `NONE`, `MINUTES`, `HOURLY`, `DAILY`, `WEEKLY`, `MONTHLY`, `CRON`.

## Exceptions (src/Exceptions)

- `SchedulingException` – Base class for scheduler-specific errors.
- `JobNotFoundException` – Thrown when referencing missing job IDs.
- `InvalidJobStateException` – Raised on disallowed status transitions or execution requests.
- `InvalidRecurrenceException` – Input validation failure for recurrence definitions.
- `FeatureNotImplementedException` – Placeholder for future calendar export features.
- `NoHandlerFoundException` – Indicates missing `JobHandlerInterface` for requested `JobType`.

## Usage Patterns

### Scheduling a Job

```php
$definition = new ScheduleDefinition(
	jobType: JobType::EXPORT_REPORT,
	targetId: '01JDXVF9KBWQ3S9Y73X5YTX3R8',
	runAt: new \DateTimeImmutable('+10 minutes'),
	payload: ['format' => 'xlsx']
);

$job = $scheduleManager->schedule($definition);
```

### Handling Execution

```php
final readonly class ReportHandler implements JobHandlerInterface
{
	public function supports(JobType $jobType): bool
	{
		return $jobType === JobType::EXPORT_REPORT;
	}

	public function handle(ScheduledJob $job): JobResult
	{
		try {
			$this->exporter->run($job->payload);
			return JobResult::success();
		} catch (ExternalApiException $e) {
			return JobResult::failure($e->getMessage(), shouldRetry: true, retryDelaySeconds: 120);
		}
	}
}
```

Refer to the [Integration Guide](integration-guide.md) for framework-specific wiring instructions.
