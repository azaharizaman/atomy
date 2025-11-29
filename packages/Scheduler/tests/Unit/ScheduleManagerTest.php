<?php

declare(strict_types=1);

namespace Nexus\Scheduler\Tests\Unit;

use DateInterval;
use DateTimeImmutable;
use DateTimeInterface;
use Nexus\Scheduler\Enums\JobStatus;
use Nexus\Scheduler\Enums\JobType;
use Nexus\Scheduler\Services\ScheduleManager;
use Nexus\Scheduler\Tests\Support\CallbackJobHandler;
use Nexus\Scheduler\Tests\Support\InMemoryScheduleRepository;
use Nexus\Scheduler\Tests\Support\MutableClock;
use Nexus\Scheduler\Tests\Support\TrackingJobQueue;
use Nexus\Scheduler\ValueObjects\JobResult;
use Nexus\Scheduler\ValueObjects\ScheduleDefinition;
use Nexus\Scheduler\ValueObjects\ScheduleRecurrence;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class ScheduleManagerTest extends TestCase
{
    private MutableClock $clock;
    private InMemoryScheduleRepository $repository;
    private TrackingJobQueue $queue;
    private NullLogger $logger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->clock = new MutableClock(new DateTimeImmutable('2024-01-01T00:00:00+00:00'));
        $this->repository = new InMemoryScheduleRepository($this->clock);
        $this->queue = new TrackingJobQueue();
        $this->logger = new NullLogger();
    }

    public function test_schedule_and_execute_single_job(): void
    {
        $handler = new CallbackJobHandler(
            JobType::SEND_REMINDER,
            static fn () => JobResult::success(['status' => 'sent'])
        );

        $manager = $this->createManager([$handler]);

        $definition = ScheduleDefinition::once(
            jobType: JobType::SEND_REMINDER,
            targetId: '01HXYZQ1VABCD234567890MNOP',
            runAt: $this->clock->now()->add(new DateInterval('PT2M')),
            payload: ['recipient' => 'billing@example.com'],
        );

        $job = $manager->schedule($definition);
        self::assertSame(JobStatus::PENDING, $job->status);

        $this->clock->advance('+2 minutes');
        $dueJobs = $manager->getDueJobs();
        self::assertCount(1, $dueJobs);

        $result = $manager->executeJob($job->id);

        self::assertTrue($result->success);
        $storedJob = $this->repository->find($job->id);
        self::assertNotNull($storedJob);
        self::assertSame(JobStatus::COMPLETED, $storedJob->status);
        self::assertSame('sent', $storedJob->lastResult?->output['status']);
    }

    public function test_failed_job_is_marked_permanent_when_handler_does_not_request_retry(): void
    {
        $attempts = 0;
        $handler = new CallbackJobHandler(
            JobType::DATA_CLEANUP,
            static function () use (&$attempts): JobResult {
                $attempts++;
                return JobResult::failure('transient-error');
            }
        );

        $manager = $this->createManager([$handler]);

        $definition = ScheduleDefinition::once(
            jobType: JobType::DATA_CLEANUP,
            targetId: '01HXYZQ1VABCD234567890MNOP',
            runAt: $this->clock->now()->add(new DateInterval('PT1M')),
            payload: ['batch' => 'cleanup-42'],
        );

        $job = $manager->schedule($definition);
        $this->clock->advance('+1 minute');
        $result = $manager->executeJob($job->id);

        self::assertFalse($result->success);
        self::assertSame(1, $attempts);

        $storedJob = $this->repository->find($job->id);
        self::assertNotNull($storedJob);
        self::assertSame(JobStatus::FAILED_PERMANENT, $storedJob->status, 'Job should enter a terminal state when retry is not requested');
        self::assertSame('transient-error', $storedJob->lastResult?->error);

        self::assertCount(0, $this->queue->dispatches(), 'Permanent failures must not be re-queued');
    }

    public function test_recurring_job_creates_next_occurrence_after_success(): void
    {
        $handler = new CallbackJobHandler(
            JobType::EXPORT_REPORT,
            static fn () => JobResult::success()
        );

        $manager = $this->createManager([$handler]);

        $definition = ScheduleDefinition::recurring(
            jobType: JobType::EXPORT_REPORT,
            targetId: '01HXYZQ1VABCD234567890MNOP',
            runAt: $this->clock->now()->add(new DateInterval('PT1H')),
            recurrence: ScheduleRecurrence::daily(),
            payload: ['report' => 'revenue-daily'],
        );

        $job = $manager->schedule($definition);
        $this->clock->advance('+1 hour');

        $manager->executeJob($job->id);

        $updatedJob = $this->repository->find($job->id);
        self::assertNotNull($updatedJob);
        self::assertSame(JobStatus::PENDING, $updatedJob->status, 'Recurring jobs are reset for the next run');
        self::assertSame(1, $updatedJob->occurrenceCount);
        self::assertSame(
            $job->runAt->modify('+1 day')->format(DateTimeInterface::ATOM),
            $updatedJob->runAt->format(DateTimeInterface::ATOM)
        );
    }

    private function createManager(iterable $handlers): ScheduleManager
    {
        return new ScheduleManager(
            repository: $this->repository,
            queue: $this->queue,
            clock: $this->clock,
            handlers: $handlers,
            logger: $this->logger,
        );
    }
}
