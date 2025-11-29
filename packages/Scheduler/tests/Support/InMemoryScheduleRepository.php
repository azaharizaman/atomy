<?php

declare(strict_types=1);

namespace Nexus\Scheduler\Tests\Support;

use DateTimeImmutable;
use Nexus\Scheduler\Contracts\ClockInterface;
use Nexus\Scheduler\Contracts\ScheduleRepositoryInterface;
use Nexus\Scheduler\Enums\JobStatus;
use Nexus\Scheduler\Enums\JobType;
use Nexus\Scheduler\ValueObjects\ScheduledJob;

/**
 * Lightweight in-memory repository for PHPUnit smoke tests.
 */
final class InMemoryScheduleRepository implements ScheduleRepositoryInterface
{
    /** @var array<string, ScheduledJob> */
    private array $jobs = [];

    public function __construct(private readonly ClockInterface $clock)
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

    /**
     * Helper exposed for assertions inside the smoke tests.
     */
    public function all(): array
    {
        return array_values($this->jobs);
    }
}
