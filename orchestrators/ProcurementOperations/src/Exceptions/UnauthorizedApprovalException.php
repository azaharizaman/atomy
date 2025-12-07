<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Exceptions;

/**
 * Exception for unauthorized approval attempts.
 */
class UnauthorizedApprovalException extends ProcurementOperationsException
{
    /**
     * Create exception for user not authorized to approve.
     */
    public static function notAuthorized(string $userId, string $entityType, string $entityId): self
    {
        return new self(
            sprintf(
                'User %s is not authorized to approve %s: %s',
                $userId,
                $entityType,
                $entityId
            )
        );
    }

    /**
     * Create exception for approval amount exceeds limit.
     */
    public static function amountExceedsLimit(
        string $userId,
        int $amountCents,
        int $limitCents
    ): self {
        return new self(
            sprintf(
                'User %s cannot approve amount %d cents (limit: %d cents)',
                $userId,
                $amountCents,
                $limitCents
            )
        );
    }

    /**
     * Create exception for self-approval not allowed.
     */
    public static function selfApprovalNotAllowed(string $userId): self
    {
        return new self(
            sprintf('User %s cannot approve their own request', $userId)
        );
    }

    /**
     * Create exception for delegation expired.
     */
    public static function delegationExpired(string $userId, string $delegatedFrom): self
    {
        return new self(
            sprintf(
                'Delegation from %s to %s has expired',
                $delegatedFrom,
                $userId
            )
        );
    }
}
