<?php

declare(strict_types=1);

use Nexus\Scheduler\Contracts\ClockInterface;
use Nexus\Scheduler\Contracts\JobHandlerInterface;
use Nexus\Scheduler\Contracts\JobQueueInterface;
use Nexus\Scheduler\Contracts\ScheduleRepositoryInterface;
use Nexus\Scheduler\Enums\JobStatus;
use Nexus\Scheduler\Enums\JobType;
use Nexus\Scheduler\Services\ScheduleManager;
use Nexus\Scheduler\ValueObjects\JobResult;
use Nexus\Scheduler\ValueObjects\ScheduleDefinition;
use Nexus\Scheduler\ValueObjects\ScheduledJob;
use Nexus\Scheduler\ValueObjects\ScheduleRecurrence;
use Psr\Log\AbstractLogger;

require dirname(__DIR__, 4) . '/vendor/autoload.php';

printHeading('Nexus Scheduler — Advanced Usage (Retries + Recurrence)');

$clock = new MutableClock(new DateTimeImmutable('2024-11-02T06:00:00+00:00'));
$repository = new InMemoryScheduleRepository($clock);
$queue = new InspectableJobQueue();
$logger = new ConsoleLogger();
$telemetry = new InlineTelemetryTracker();

$handlers = [new InventoryCleanupHandler($logger, $telemetry)];

$manager = new ScheduleManager(
	repository: $repository,
	queue: $queue,
	clock: $clock,
	handlers: $handlers,
	logger: $logger,
);

$definition = new ScheduleDefinition(
	jobType: JobType::DATA_CLEANUP,
	targetId: '01HF8D4SENHX3YQK9C0W1JBQ12',
	runAt: $clock->now()->add(new DateInterval('PT1M')),
	payload: ['scope' => 'inventory-stale-records', 'chunkSize' => 5000],
	recurrence: ScheduleRecurrence::daily(),
	maxRetries: 5,
	priority: 5,
	metadata: ['region' => 'apac-east'],
);

$job = $manager->schedule($definition);
printSection('Initial Job Snapshot', $job->toArray());

// FIRST EXECUTION (FAILS + REQUESTS RETRY)
$clock->advance('+1 minute');
$firstAttempt = $manager->executeJob($job->id);

printSection('First Attempt Result', $firstAttempt->toArray());
printSection('Job After Failure (Pending Retry)', $manager->getJob($job->id)?->toArray() ?? []);
printSection('Queue Dispatches', $queue->dispatchedJobs());

// SECOND EXECUTION (SUCCESS)
$clock->advance('+2 minutes'); // respects retry delay (120s)
$secondAttempt = $manager->executeJob($job->id);

printSection('Second Attempt Result', $secondAttempt->toArray());
printSection('Job After Success', $manager->getJob($job->id)?->toArray() ?? []);

// RECURRING OCCURRENCE
$clock->advance('+1 day');
$nextDueJobs = $manager->getDueJobs();

foreach ($nextDueJobs as $nextDue) {
	$manager->executeJob($nextDue->id);
}

printSection('Recurring Occurrence Snapshot', array_map(fn (ScheduledJob $due) => $due->toArray(), $nextDueJobs));
printSection('Telemetry Metrics', $telemetry->export());

// ---- Helper Classes -------------------------------------------------------

final class MutableClock implements ClockInterface
{
	public function __construct(private DateTimeImmutable $now)
	{
	}

	public function now(): DateTimeImmutable
	{
		return $this->now;
	}

	public function advance(string $modifier): void
	{
		$this->now = $this->now->modify($modifier);
	}
}

final class InMemoryScheduleRepository implements ScheduleRepositoryInterface
{
	/** @var array<string, ScheduledJob> */
	private array $jobs = [];

	public function __construct(private ClockInterface $clock)
	{
	}

	public function find(string $id): ?ScheduledJob
	{
		return $this->jobs[$id] ?? null;
	}

	public function findDue(DateTimeImmutable $asOf): array
	{
		return array_values(array_filter(
			$this->jobs,
			fn (ScheduledJob $job): bool => $job->isDue($this->clock)
		));
	}

	public function findByType(JobType $jobType): array
	{
		return array_values(array_filter(
			$this->jobs,
			fn (ScheduledJob $job): bool => $job->jobType === $jobType
		));
	}

	public function findByTarget(string $targetId): array
	{
		return array_values(array_filter(
			$this->jobs,
			fn (ScheduledJob $job): bool => $job->targetId === $targetId
		));
	}

	public function findByStatus(JobStatus $status, int $limit = 100): array
	{
		return array_slice(
			array_values(array_filter(
				$this->jobs,
				fn (ScheduledJob $job): bool => $job->status === $status
			)),
			0,
			$limit,
		);
	}

	public function save(ScheduledJob $job): void
	{
		$this->jobs[$job->id] = $job;
	}

	public function delete(string $id): bool
	{
		if (!isset($this->jobs[$id])) {
			return false;
		}
		unset($this->jobs[$id]);
		return true;
	}

	public function count(?JobStatus $status = null): int
	{
		if ($status === null) {
			return count($this->jobs);
		}

		return count(array_filter(
			$this->jobs,
			fn (ScheduledJob $job): bool => $job->status === $status
		));
	}
}

final class InspectableJobQueue implements JobQueueInterface
{
	/** @var array<int, array{jobId: string, jobType: string, delay: int|null}> */
	private array $dispatches = [];

	public function dispatch(ScheduledJob $job, ?int $delaySeconds = null): void
	{
		$this->dispatches[] = [
			'jobId' => $job->id,
			'jobType' => $job->jobType->value,
			'delay' => $delaySeconds,
		];
	}

	public function size(): int
	{
		return count($this->dispatches);
	}

	/**
	 * @return array<int, array{jobId: string, jobType: string, delay: int|null}>
	 */
	public function dispatchedJobs(): array
	{
		return $this->dispatches;
	}
}

final class InventoryCleanupHandler implements JobHandlerInterface
{
	private bool $hasRetried = false;

	public function __construct(
		private ConsoleLogger $logger,
		private InlineTelemetryTracker $telemetry,
	) {}

	public function supports(JobType $jobType): bool
	{
		return $jobType === JobType::DATA_CLEANUP;
	}

	public function handle(ScheduledJob $job): JobResult
	{
		$this->telemetry->increment('scheduler.cleanup.attempt', [
			'target' => $job->targetId,
			'occurrence' => $job->occurrenceCount,
		]);

		if (!$this->hasRetried) {
			$this->hasRetried = true;
			$this->logger->warning('Cleanup throttled, requesting retry');

			return JobResult::failure(
				error: 'S3 throughput throttled, retry later',
				shouldRetry: true,
				retryDelaySeconds: 120,
			);
		}

		$this->logger->info('Cleanup completed', [
			'itemsPurged' => 3487,
			'durationSeconds' => 42,
		]);

		$this->telemetry->increment('scheduler.cleanup.success');

		return JobResult::success([
			'itemsPurged' => 3487,
		]);
	}
}

final class InlineTelemetryTracker
{
	/** @var array<string, int> */
	private array $counters = [];

	/** @param array<string, scalar|array<int|string, scalar>> $tags */
	public function increment(string $metric, array $tags = []): void
	{
		$key = $metric . ':' . md5(json_encode($tags, JSON_THROW_ON_ERROR));
		$this->counters[$key] = ($this->counters[$key] ?? 0) + 1;
	}

	/** @return array<string, int> */
	public function export(): array
	{
		return $this->counters;
	}
}

final class ConsoleLogger extends AbstractLogger
{
	public function log($level, $message, array $context = []): void
	{
		$payload = $context === [] ? '' : json_encode($context, JSON_THROW_ON_ERROR);
		printf('[%s] %s %s' . PHP_EOL, strtoupper((string) $level), $message, $payload);
	}
}

function printHeading(string $message): void
{
	printf("\n==== %s ====\n", $message);
}

/**
 * @param array<string, mixed>|array<int, mixed> $payload
 */
function printSection(string $title, array $payload): void
{
	printf("\n-- %s --\n%s\n", $title, json_encode($payload, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
}
