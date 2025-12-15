<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Exceptions;

/**
 * Exception for approval limits management errors.
 */
final class ApprovalLimitsException extends \RuntimeException
{
    /**
     * Create exception for configuration not found.
     */
    public static function configurationNotFound(string $tenantId): self
    {
        return new self(sprintf(
            'Approval limits configuration not found for tenant: %s',
            $tenantId
        ));
    }

    /**
     * Create exception for invalid limit value.
     */
    public static function invalidLimitValue(string $documentType, int $value): self
    {
        return new self(sprintf(
            'Invalid approval limit value for document type "%s": %d (must be non-negative)',
            $documentType,
            $value
        ));
    }

    /**
     * Create exception for invalid document type.
     */
    public static function invalidDocumentType(string $documentType, array $validTypes): self
    {
        return new self(sprintf(
            'Invalid document type "%s". Valid types: %s',
            $documentType,
            implode(', ', $validTypes)
        ));
    }

    /**
     * Create exception for role not found.
     */
    public static function roleNotFound(string $roleId): self
    {
        return new self(sprintf(
            'Role not found: %s',
            $roleId
        ));
    }

    /**
     * Create exception for user not found.
     */
    public static function userNotFound(string $userId): self
    {
        return new self(sprintf(
            'User not found: %s',
            $userId
        ));
    }

    /**
     * Create exception for department not found.
     */
    public static function departmentNotFound(string $departmentId): self
    {
        return new self(sprintf(
            'Department not found: %s',
            $departmentId
        ));
    }

    /**
     * Create exception for configuration save failure.
     */
    public static function saveFailed(string $tenantId, string $reason): self
    {
        return new self(sprintf(
            'Failed to save approval limits configuration for tenant %s: %s',
            $tenantId,
            $reason
        ));
    }

    /**
     * Create exception for user having no authority.
     */
    public static function noApprovalAuthority(string $userId): self
    {
        return new self(sprintf(
            'User %s has no approval authority configured',
            $userId
        ));
    }

    /**
     * Create exception for exceeding approval limit.
     */
    public static function limitExceeded(
        string $userId,
        string $documentType,
        int $requestedCents,
        int $limitCents
    ): self {
        return new self(sprintf(
            'User %s approval limit exceeded for %s: requested %d cents, limit is %d cents',
            $userId,
            $documentType,
            $requestedCents,
            $limitCents
        ));
    }

    /**
     * Create exception for authority not effective.
     */
    public static function authorityNotEffective(
        string $userId,
        \DateTimeImmutable $effectiveFrom,
        ?\DateTimeImmutable $effectiveUntil
    ): self {
        $untilStr = $effectiveUntil?->format('Y-m-d H:i:s') ?? 'indefinite';

        return new self(sprintf(
            'User %s approval authority is not currently effective. Valid from %s until %s',
            $userId,
            $effectiveFrom->format('Y-m-d H:i:s'),
            $untilStr
        ));
    }
}
