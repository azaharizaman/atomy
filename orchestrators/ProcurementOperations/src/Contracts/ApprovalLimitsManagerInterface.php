<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Contracts;

use Nexus\ProcurementOperations\DTOs\ApprovalLimitCheckRequest;
use Nexus\ProcurementOperations\DTOs\ApprovalLimitCheckResult;
use Nexus\ProcurementOperations\DTOs\ApprovalLimitConfig;
use Nexus\ProcurementOperations\ValueObjects\ApprovalAuthority;

/**
 * Manages approval limit configurations for procurement documents.
 *
 * This interface defines operations for:
 * - Configuring approval limits by role, department, and document type
 * - Validating user approval authority
 * - Retrieving effective limits for a given context
 *
 * The ApprovalLimitsManager handles configuration CRUD, while
 * ApprovalRoutingService uses these configurations to determine routing.
 *
 * Following Advanced Orchestrator Pattern v1.1:
 * - Service owns limit configuration management
 * - Integrates with Setting and Identity packages
 * - Returns structured results for coordinator use
 */
interface ApprovalLimitsManagerInterface
{
    /**
     * Get the approval limit configuration for a tenant.
     *
     * @param string $tenantId Tenant identifier
     * @return ApprovalLimitConfig Current configuration
     */
    public function getConfiguration(string $tenantId): ApprovalLimitConfig;

    /**
     * Save approval limit configuration for a tenant.
     *
     * @param string $tenantId Tenant identifier
     * @param ApprovalLimitConfig $config Configuration to save
     */
    public function saveConfiguration(string $tenantId, ApprovalLimitConfig $config): void;

    /**
     * Get the approval authority for a specific user.
     *
     * Returns the effective approval limits for a user based on their
     * roles, department, and any override configurations.
     *
     * @param string $tenantId Tenant identifier
     * @param string $userId User identifier
     * @param string|null $departmentId Optional department context
     * @return ApprovalAuthority User's approval authority
     */
    public function getUserAuthority(
        string $tenantId,
        string $userId,
        ?string $departmentId = null
    ): ApprovalAuthority;

    /**
     * Check if a user can approve a document amount.
     *
     * @param ApprovalLimitCheckRequest $request Check request
     * @return ApprovalLimitCheckResult Validation result
     */
    public function checkApprovalLimit(ApprovalLimitCheckRequest $request): ApprovalLimitCheckResult;

    /**
     * Get limits for a specific role.
     *
     * @param string $tenantId Tenant identifier
     * @param string $roleId Role identifier
     * @return array<string, int> Document type => limit in cents
     */
    public function getRoleLimits(string $tenantId, string $roleId): array;

    /**
     * Set limits for a specific role.
     *
     * @param string $tenantId Tenant identifier
     * @param string $roleId Role identifier
     * @param array<string, int> $limits Document type => limit in cents
     */
    public function setRoleLimits(string $tenantId, string $roleId, array $limits): void;

    /**
     * Get limits for a specific department.
     *
     * @param string $tenantId Tenant identifier
     * @param string $departmentId Department identifier
     * @return array<string, int> Document type => limit in cents
     */
    public function getDepartmentLimits(string $tenantId, string $departmentId): array;

    /**
     * Set limits for a specific department.
     *
     * @param string $tenantId Tenant identifier
     * @param string $departmentId Department identifier
     * @param array<string, int> $limits Document type => limit in cents
     */
    public function setDepartmentLimits(string $tenantId, string $departmentId, array $limits): void;

    /**
     * Get user-specific limit overrides.
     *
     * @param string $tenantId Tenant identifier
     * @param string $userId User identifier
     * @return array<string, int>|null Document type => limit in cents, or null if no overrides
     */
    public function getUserOverrides(string $tenantId, string $userId): ?array;

    /**
     * Set user-specific limit overrides.
     *
     * @param string $tenantId Tenant identifier
     * @param string $userId User identifier
     * @param array<string, int>|null $limits Document type => limit in cents, or null to clear
     */
    public function setUserOverrides(string $tenantId, string $userId, ?array $limits): void;

    /**
     * Reset configuration to default values.
     *
     * @param string $tenantId Tenant identifier
     */
    public function resetToDefaults(string $tenantId): void;
}
