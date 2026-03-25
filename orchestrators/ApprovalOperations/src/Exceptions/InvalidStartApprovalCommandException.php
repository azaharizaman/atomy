<?php

declare(strict_types=1);

namespace Nexus\ApprovalOperations\Exceptions;

final class InvalidStartApprovalCommandException extends \DomainException
{
    public static function fromEmptyTenantId(): self
    {
        return new self('tenantId cannot be empty.');
    }

    public static function fromEmptyInitiatorPrincipalId(): self
    {
        return new self('initiatorPrincipalId cannot be empty.');
    }
}
