<?php

declare(strict_types=1);

namespace Nexus\Scheduler\Exceptions;

/**
 * Job Not Found Exception
 *
 * Thrown when a requested job does not exist.
 */
class JobNotFoundException extends SchedulingException
{
    public function __construct(string $jobId)
    {
        parent::__construct("Scheduled job not found: {$jobId}");
    }
}
