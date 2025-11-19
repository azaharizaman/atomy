<?php

declare(strict_types=1);

namespace Nexus\Scheduler\Exceptions;

/**
 * Invalid Recurrence Exception
 *
 * Thrown when recurrence configuration is invalid.
 */
class InvalidRecurrenceException extends SchedulingException
{
    public function __construct(string $message)
    {
        parent::__construct($message);
    }
}
