<?php

declare(strict_types=1);

namespace Nexus\Scheduler\Tests\Support;

use Closure;
use Nexus\Scheduler\Contracts\JobHandlerInterface;
use Nexus\Scheduler\Enums\JobType;
use Nexus\Scheduler\ValueObjects\JobResult;
use Nexus\Scheduler\ValueObjects\ScheduledJob;

final class CallbackJobHandler implements JobHandlerInterface
{
    /**
     * @param Closure(ScheduledJob):JobResult $callback
     */
    public function __construct(
        private readonly JobType $supportedType,
        private readonly Closure $callback
    ) {
    }

    public function supports(JobType $jobType): bool
    {
        return $jobType === $this->supportedType;
    }

    public function handle(ScheduledJob $job): JobResult
    {
        return ($this->callback)($job);
    }
}
