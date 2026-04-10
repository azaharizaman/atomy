<?php

declare(strict_types=1);

namespace Nexus\TenantOperations\Contracts;

/**
 * Adapter interface for creating the primary tenant admin user.
 *
 * Implemented by Layer 3 adapters that can speak to the Identity system.
 */
interface AdminCreatorAdapterInterface
{
    /**
     * Create an admin user for a tenant.
     *
     * @param string $tenantId
     * @param string $email
     * @param string $password
     * @param string $firstName
     * @param string $lastName
     * @param bool $isAdmin
     * @param string|null $locale
     * @param string|null $timezone
     * @param array<string, mixed>|null $metadata
     * @return string The created admin user ID
     */
    public function create(
        string $tenantId,
        string $email,
        string $password,
        string $firstName,
        string $lastName,
        bool $isAdmin = true,
        ?string $locale = null,
        ?string $timezone = null,
        ?array $metadata = null
    ): string;
}
