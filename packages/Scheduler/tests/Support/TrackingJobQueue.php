<?php

declare(strict_types=1);

namespace Nexus\Scheduler\Tests\Support;

use Nexus\Scheduler\Contracts\JobQueueInterface;
use Nexus\Scheduler\ValueObjects\ScheduledJob;

final class TrackingJobQueue implements JobQueueInterface
{
    /** @var array<int, array{job: ScheduledJob, delay: ?int}> */
    private array $dispatches = [];

    public function dispatch(ScheduledJob $job, ?int $delaySeconds = null): void
    {
        $this->dispatches[] = [
            'job' => $job,
            'delay' => $delaySeconds,
        ];
    }

    public function size(): int
    {
        return count($this->dispatches);
    }

    /**
     * @return array<int, array{job: ScheduledJob, delay: ?int}>
     */
    public function dispatches(): array
    {
        return $this->dispatches;
    }

    public function lastDispatch(): ?array
    {
        if ($this->dispatches === []) {
            return null;
        }

        return $this->dispatches[count($this->dispatches) - 1];
    }
}
