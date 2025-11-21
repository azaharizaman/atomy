<?php

declare(strict_types=1);

namespace Nexus\Audit\Exceptions;

/**
 * Thrown when invalid retention policy is specified
 */
class InvalidRetentionPolicyException extends AuditException
{
    public function __construct(int $days)
    {
        parent::__construct("Invalid retention policy: {$days} days (must be non-negative)");
    }
}
