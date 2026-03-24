<?php

declare(strict_types=1);

namespace Nexus\ApprovalOperations\Exceptions;

final class RecordApprovalDecisionValidationException extends \DomainException
{
    public static function emptyTenantId(): self
    {
        return new self('tenantId cannot be empty.');
    }

    public static function emptyInstanceId(): self
    {
        return new self('instanceId cannot be empty.');
    }

    public static function emptyActorPrincipalId(): self
    {
        return new self('actorPrincipalId cannot be empty.');
    }
}
