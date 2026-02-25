<?php

declare(strict_types=1);

namespace Nexus\TenantOperations\Contracts;

/**
 * Adapter interface for creating the primary tenant admin user.
 * 
 * Must be implemented by Layer 3 (Adapters) using Nexus\Identity package.
 */
interface AdminCreatorAdapterInterface
{
    /**
     * Create an admin user for a tenant.
     *
     * @param string $tenantId
     * @param string $email
     * @param string $password
     * @param bool $isAdmin
     * @return string The created admin user ID
     */
    public function create(string $tenantId, string $email, string $password, bool $isAdmin = false): string;
}
