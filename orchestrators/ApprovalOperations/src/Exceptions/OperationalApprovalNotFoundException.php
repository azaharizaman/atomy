<?php

declare(strict_types=1);

namespace Nexus\ApprovalOperations\Exceptions;

/**
 * Instance missing or not visible for tenant (wrong-tenant and missing both collapse to 404 at L3).
 */
final class OperationalApprovalNotFoundException extends \RuntimeException
{
    public static function forInstance(string $instanceId): self
    {
        return new self(\sprintf('Operational approval instance %s not found.', $instanceId));
    }
}
