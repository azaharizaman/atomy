<?php

declare(strict_types=1);

namespace Nexus\Reporting\Exceptions;

/**
 * Thrown when a user attempts to generate a report they're not authorized to access.
 *
 * Implements SEC-REP-0401: Permission Inheritance from Analytics.
 */
class UnauthorizedReportException extends ReportingException
{
    /**
     * Create exception for unauthorized query execution.
     */
    public static function cannotExecuteQuery(
        string $userId,
        string $queryId
    ): self {
        return new self(
            "User '{$userId}' is not authorized to execute Analytics query '{$queryId}'"
        );
    }

    /**
     * Create exception for unauthorized report access.
     */
    public static function cannotAccessReport(
        string $userId,
        string $reportId
    ): self {
        return new self(
            "User '{$userId}' is not authorized to access report '{$reportId}'"
        );
    }

    /**
     * Create exception for tenant isolation violation.
     */
    public static function tenantMismatch(
        string $currentTenantId,
        string $reportTenantId
    ): self {
        return new self(
            "Tenant mismatch: current tenant '{$currentTenantId}' cannot access report from tenant '{$reportTenantId}'"
        );
    }

    /**
     * Create exception for missing permissions.
     */
    public static function missingPermission(
        string $userId,
        string $permission,
        string $reportId
    ): self {
        return new self(
            "User '{$userId}' lacks permission '{$permission}' for report '{$reportId}'"
        );
    }
}
