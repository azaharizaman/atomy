<?php

declare(strict_types=1);

namespace Nexus\Analytics\Contracts;

/**
 * Interface for handling authorization and permission checks
 */
interface AnalyticsAuthorizerInterface
{
    /**
     * Check if a user can perform an action on a query
     *
     * @param string $userId
     * @param string $action (e.g., 'execute', 'view', 'modify', 'delete')
     * @param string $queryId
     */
    public function can(string $userId, string $action, string $queryId): bool;

    /**
     * Check delegation chain permissions
     *
     * @param string $userId
     * @param string $queryId
     * @return array<string, mixed> Delegation chain information
     */
    public function checkDelegationChain(string $userId, string $queryId): array;

    /**
     * Get all permissions for a user on a specific query
     *
     * @param string $userId
     * @param string $queryId
     * @return array<int, string> List of allowed actions
     */
    public function getPermissions(string $userId, string $queryId): array;

    /**
     * Verify tenant isolation for a query execution
     *
     * @param string $tenantId
     * @param string $queryId
     */
    public function verifyTenantIsolation(string $tenantId, string $queryId): bool;

    /**
     * Check if user can view sensitive data
     *
     * @param string $userId
     * @param string $dataType
     */
    public function canViewSensitiveData(string $userId, string $dataType): bool;
}
