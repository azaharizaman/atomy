<?php

declare(strict_types=1);

namespace Nexus\Compliance\Contracts;

/**
 * Repository interface for compliance scheme persistence.
 */
interface ComplianceSchemeRepositoryInterface
{
    /**
     * Find a compliance scheme by ID.
     *
     * @param string $id The scheme ID
     * @return ComplianceSchemeInterface|null
     */
    public function findById(string $id): ?ComplianceSchemeInterface;

    /**
     * Find a compliance scheme by tenant and scheme name.
     *
     * @param string $tenantId The tenant identifier
     * @param string $schemeName The scheme name
     * @return ComplianceSchemeInterface|null
     */
    public function findByTenantAndName(string $tenantId, string $schemeName): ?ComplianceSchemeInterface;

    /**
     * Get all active schemes for a tenant.
     *
     * @param string $tenantId The tenant identifier
     * @return array<ComplianceSchemeInterface>
     */
    public function getActiveSchemes(string $tenantId): array;

    /**
     * Get all schemes for a tenant (active and inactive).
     *
     * @param string $tenantId The tenant identifier
     * @return array<ComplianceSchemeInterface>
     */
    public function getAllSchemes(string $tenantId): array;

    /**
     * Save a compliance scheme.
     *
     * @param ComplianceSchemeInterface $scheme The scheme to save
     * @return void
     */
    public function save(ComplianceSchemeInterface $scheme): void;

    /**
     * Delete a compliance scheme.
     *
     * @param string $id The scheme ID
     * @return void
     */
    public function delete(string $id): void;
}
