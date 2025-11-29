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
use Psr\Log\LoggerInterface;

require dirname(__DIR__, 4) . '/vendor/autoload.php';

printHeading('Nexus Scheduler — Basic Usage');

$clock = new MutableClock(new DateTimeImmutable('2024-11-01T08:00:00+00:00'));
$repository = new InMemoryScheduleRepository($clock);
$queue = new InlineJobQueue();
$logger = new ConsoleLogger();
$handlers = [new SendReminderHandler($logger)];

$manager = new ScheduleManager(
	repository: $repository,
	queue: $queue,
	clock: $clock,
	handlers: $handlers,
	logger: $logger,
);

// 1. Create a one-off reminder job for an upcoming invoice
$definition = ScheduleDefinition::once(
	jobType: JobType::SEND_REMINDER,
	targetId: '01HF7Y33K8HKS3QEWJ6Y1Z9C8T',
	runAt: $clock->now()->add(new DateInterval('PT5M')),
	payload: [
		'recipient' => 'billing@example.com',
		'template' => 'invoice-overdue',
		'invoice_id' => 'INV-2024-00023',
	],
);

$job = $manager->schedule($definition);
printSection('Scheduled Job', $job->toArray());

// 2. Advance the clock so the job becomes due and pull due jobs
$clock->advance('+5 minutes');
$dueJobs = $manager->getDueJobs();
printSection('Due Jobs', array_map(fn (ScheduledJob $dueJob) => $dueJob->toArray(), $dueJobs));

// 3. Execute the due job via the manager (engine picks the handler automatically)
foreach ($dueJobs as $dueJob) {
	$result = $manager->executeJob($dueJob->id);
	printSection("Execution Result for {$dueJob->id}", $result->toArray());
}

// 4. Reschedule the completed job as a weekly recurring reminder
$recurringDefinition = new ScheduleDefinition(
	jobType: JobType::SEND_REMINDER,
	targetId: '01HF7Y33K8HKS3QEWJ6Y1Z9C8T',
	runAt: $clock->now()->add(new DateInterval('P1D')),
	payload: ['recipient' => 'billing@example.com'],
	recurrence: ScheduleRecurrence::weekly(),
);

$recurringJob = $manager->schedule($recurringDefinition);
printSection('Recurring Job', $recurringJob->toArray());

// ---- Helper Classes Below --------------------------------------------------

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

final class InlineJobQueue implements JobQueueInterface
{
	public function dispatch(ScheduledJob $job, ?int $delaySeconds = null): void
	{
		printf(
			"[queue] Dispatched job %s (%s) with delay %s seconds" . PHP_EOL,
			$job->id,
			$job->jobType->value,
			$delaySeconds ?? 0,
		);
	}

	public function size(): int
	{
		return 0;
	}
}

final class SendReminderHandler implements JobHandlerInterface
{
	public function __construct(private LoggerInterface $logger)
	{
	}

	public function supports(JobType $jobType): bool
	{
		return $jobType === JobType::SEND_REMINDER;
	}

	public function handle(ScheduledJob $job): JobResult
	{
		$this->logger->info('Sending reminder', $job->payload);

		// Simulate successful delivery
		return JobResult::success([
			'delivered_at' => (new DateTimeImmutable())->format(DATE_ATOM),
		]);
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
