<?php

declare(strict_types=1);

namespace Nexus\Reporting\Contracts;

use Nexus\Reporting\ValueObjects\JobResult;
use Nexus\Reporting\ValueObjects\JobType;
use Nexus\Reporting\ValueObjects\ScheduledJob;

interface JobHandlerInterface
{
    public function supports(JobType $jobType): bool;

    public function handle(ScheduledJob $job): JobResult;
}
