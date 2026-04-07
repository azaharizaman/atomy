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
     * @param string $email
     * @param string $domain
     * @param string|null $timezone
     * @param string|null $locale
     * @param string|null $currency
     * @param array<string, mixed>|null $metadata
     * @return string The created tenant ID
     */
    public function create(
        string $code,
        string $name,
        string $email,
        string $domain,
        ?string $timezone = null,
        ?string $locale = null,
        ?string $currency = null,
        ?array $metadata = null,
    ): string;
}
