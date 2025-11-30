<?php

declare(strict_types=1);

namespace Nexus\Scheduler\Exceptions;

/**
 * Feature Not Implemented Exception
 *
 * Thrown when attempting to use a feature not yet implemented (e.g., calendar export in v1).
 */
class FeatureNotImplementedException extends SchedulingException
{
    public function __construct(string $feature)
    {
        parent::__construct("Feature not implemented: {$feature}. This feature is planned for a future release.");
    }
}
