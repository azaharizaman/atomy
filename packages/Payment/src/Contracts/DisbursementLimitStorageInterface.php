<?php

declare(strict_types=1);

namespace Nexus\Payment\Contracts;

use Nexus\Payment\ValueObjects\DisbursementLimits;

/**
 * Interface for disbursement limit storage.
 *
 * This interface must be implemented by the consuming application
 * to provide persistence for disbursement limits (PAY-035).
 *
 * Example implementation using a database:
 * ```php
 * final readonly class EloquentDisbursementLimitStorage implements DisbursementLimitStorageInterface
 * {
 *     public function getTenantLimits(string $tenantId): ?DisbursementLimits
 *     {
 *         $record = DisbursementLimit::where('tenant_id', $tenantId)
 *             ->whereNull('user_id')
 *             ->first();
 *
 *         return $record ? DisbursementLimits::fromArray($record->limits) : null;
 *     }
 * }
 * ```
 */
interface DisbursementLimitStorageInterface
{
    /**
     * Get limits for a tenant.
     *
     * Returns the default limits applied to all disbursements for the tenant.
     *
     * @param string $tenantId The tenant identifier
     * @return DisbursementLimits|null The limits or null if none configured
     */
    public function getTenantLimits(string $tenantId): ?DisbursementLimits;

    /**
     * Set limits for a tenant.
     *
     * These limits apply to all users unless overridden with user-specific limits.
     *
     * @param string $tenantId The tenant identifier
     * @param DisbursementLimits $limits The limits to set
     */
    public function setTenantLimits(string $tenantId, DisbursementLimits $limits): void;

    /**
     * Get user-specific limits.
     *
     * User-specific limits override tenant defaults.
     *
     * @param string $tenantId The tenant identifier
     * @param string $userId The user identifier
     * @return DisbursementLimits|null The limits or null if none configured
     */
    public function getUserLimits(string $tenantId, string $userId): ?DisbursementLimits;

    /**
     * Set user-specific limits.
     *
     * User-specific limits override tenant defaults for the specified user.
     *
     * @param string $tenantId The tenant identifier
     * @param string $userId The user identifier
     * @param DisbursementLimits $limits The limits to set
     */
    public function setUserLimits(string $tenantId, string $userId, DisbursementLimits $limits): void;

    /**
     * Remove tenant limits.
     *
     * @param string $tenantId The tenant identifier
     */
    public function removeTenantLimits(string $tenantId): void;

    /**
     * Remove user-specific limits.
     *
     * @param string $tenantId The tenant identifier
     * @param string $userId The user identifier
     */
    public function removeUserLimits(string $tenantId, string $userId): void;

    /**
     * Check if tenant has limits configured.
     *
     * @param string $tenantId The tenant identifier
     * @return bool True if limits are configured
     */
    public function hasTenantLimits(string $tenantId): bool;

    /**
     * Check if user has limits configured.
     *
     * @param string $tenantId The tenant identifier
     * @param string $userId The user identifier
     * @return bool True if limits are configured
     */
    public function hasUserLimits(string $tenantId, string $userId): bool;
}
