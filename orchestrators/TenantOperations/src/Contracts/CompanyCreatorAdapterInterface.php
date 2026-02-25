<?php

declare(strict_types=1);

namespace Nexus\TenantOperations\Contracts;

/**
 * Adapter interface for creating initial company structure.
 * 
 * Must be implemented by Layer 3 (Adapters) using Nexus\Backoffice package.
 */
interface CompanyCreatorAdapterInterface
{
    /**
     * Create default company structure for a tenant.
     *
     * @param string $tenantId
     * @param string $companyName
     * @return string The created company ID
     */
    public function createDefaultStructure(string $tenantId, string $companyName): string;
}
