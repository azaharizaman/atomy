<?php

declare(strict_types=1);

namespace Nexus\ApprovalOperations\Exceptions;

/**
 * Instance row exists but workflow correlation was never set (should not occur after successful start()).
 */
final class OperationalApprovalWorkflowMissingException extends \DomainException
{
    public static function forInstance(string $instanceId): self
    {
        return new self(\sprintf('Operational approval instance %s has no workflow correlation.', $instanceId));
    }
}
