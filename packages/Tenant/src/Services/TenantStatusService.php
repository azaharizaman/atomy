<?php

declare(strict_types=1);

namespace Nexus\Tenant\Services;

use Nexus\Tenant\Contracts\TenantInterface;
use Nexus\Tenant\Contracts\TenantQueryInterface;

/**
 * Tenant Status Service
 *
 * Domain service containing business logic for tenant status filtering and analysis.
 * This logic was previously (incorrectly) placed in the repository interface.
 *
 * Following Single Responsibility Principle: All status-related business logic is centralized here.
 *
 * @package Nexus\Tenant\Services
 */
final readonly class TenantStatusService
{
    public function __construct(
        private TenantQueryInterface $query
    ) {
    }

    /**
     * Get all active tenants.
     *
     * Business logic: Filters tenants where status is 'active'.
     *
     * @return array<TenantInterface>
     */
    public function getActiveTenants(): array
    {
        $allTenants = $this->query->all();

        return array_filter(
            $allTenants,
            fn(TenantInterface $tenant) => $tenant->isActive()
        );
    }

    /**
     * Get all suspended tenants.
     *
     * Business logic: Filters tenants where status is 'suspended'.
     *
     * @return array<TenantInterface>
     */
    public function getSuspendedTenants(): array
    {
        $allTenants = $this->query->all();

        return array_filter(
            $allTenants,
            fn(TenantInterface $tenant) => $tenant->isSuspended()
        );
    }

    /**
     * Get all trial tenants.
     *
     * Business logic: Filters tenants where status is 'trial'.
     *
     * @return array<TenantInterface>
     */
    public function getTrialTenants(): array
    {
        $allTenants = $this->query->all();

        return array_filter(
            $allTenants,
            fn(TenantInterface $tenant) => $tenant->isTrial()
        );
    }

    /**
     * Get all expired trial tenants.
     *
     * Business logic: Filters tenants where status is 'trial' AND trial period has expired.
     *
     * @return array<TenantInterface>
     */
    public function getExpiredTrials(): array
    {
        $allTenants = $this->query->all();

        return array_filter(
            $allTenants,
            fn(TenantInterface $tenant) => $tenant->isTrial() && $tenant->isTrialExpired()
        );
    }

    /**
     * Get tenant statistics.
     *
     * Business logic: Aggregates tenant counts by status.
     *
     * @return array{total: int, active: int, suspended: int, trial: int, archived: int}
     */
    public function getStatistics(): array
    {
        $allTenants = $this->query->all();

        $stats = [
            'total' => count($allTenants),
            'active' => 0,
            'suspended' => 0,
            'trial' => 0,
            'archived' => 0,
        ];

        foreach ($allTenants as $tenant) {
            if ($tenant->isActive()) {
                $stats['active']++;
            } elseif ($tenant->isSuspended()) {
                $stats['suspended']++;
            } elseif ($tenant->isTrial()) {
                $stats['trial']++;
            } elseif ($tenant->isArchived()) {
                $stats['archived']++;
            }
        }

        return $stats;
    }
}
