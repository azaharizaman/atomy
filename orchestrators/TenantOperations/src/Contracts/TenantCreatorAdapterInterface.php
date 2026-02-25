<?php

declare(strict_types=1);

namespace Nexus\TenantOperations\Contracts;

/**
 * Adapter interface for creating a tenant.
 * 
 * Must be implemented by Layer 3 (Adapters) using Nexus\Tenant package.
 */
interface TenantCreatorAdapterInterface
{
    /**
     * Create a new tenant record.
     *
     * @param string $code
     * @param string $name
     * @param string $domain
     * @return string The created tenant ID
     */
    public function create(string $code, string $name, string $domain): string;
}
