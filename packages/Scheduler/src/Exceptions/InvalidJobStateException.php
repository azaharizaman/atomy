<?php

declare(strict_types=1);

namespace Nexus\Scheduler\Exceptions;

/**
 * Invalid Job State Exception
 *
 * Thrown when attempting an operation on a job in an invalid state.
 */
class InvalidJobStateException extends SchedulingException
{
    public function __construct(string $message)
    {
        parent::__construct($message);
    }
}
