<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Contracts;

/**
 * Tax Exemption Repository Interface
 * 
 * Defines the contract for retrieval of tax exemption status for vendors.
 */
interface TaxExemptionRepositoryInterface
{
    /**
     * Check if a vendor has a valid tax exemption for a tenant and exemption type
     */
    public function findValidExemption(string $tenantId, string $vendorId, string $exemptionType): ?array;

    /**
     * Check if an exemption is currently valid
     */
    public function isExemptionValid(string $tenantId, string $vendorId, string $exemptionType): bool;
}
