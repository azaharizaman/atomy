<?php

declare(strict_types=1);

namespace Nexus\Analytics\Exceptions;

/**
 * Thrown when a guard condition fails
 */
class GuardConditionFailedException extends AnalyticsException
{
    public function __construct(string $guardName, string $reason)
    {
        parent::__construct("Guard condition '{$guardName}' failed: {$reason}");
    }
}
