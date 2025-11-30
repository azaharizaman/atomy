<?php

declare(strict_types=1);

namespace Nexus\Scheduler\Exceptions;

use Nexus\Scheduler\Enums\JobType;

/**
 * No Handler Found Exception
 *
 * Thrown when no handler is available for a job type.
 */
class NoHandlerFoundException extends SchedulingException
{
    public function __construct(JobType $jobType)
    {
        parent::__construct("No handler found for job type: {$jobType->value}");
    }
}
