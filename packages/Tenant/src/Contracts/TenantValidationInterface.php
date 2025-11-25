<?php

declare(strict_types=1);

namespace Nexus\Tenant\Contracts;

/**
 * Tenant Validation Interface
 *
 * Provides validation operations for tenant business rules (uniqueness checks).
 *
 * Following Interface Segregation Principle (ISP): Focused only on validation logic.
 *
 * @package Nexus\Tenant\Contracts
 */
interface TenantValidationInterface
{
    /**
     * Check if a tenant code is already in use.
     *
     * @param string $code Tenant code to check
     * @param string|null $excludeId Tenant ID to exclude from check (for updates)
     * @return bool True if code exists, false otherwise
     */
    public function codeExists(string $code, ?string $excludeId = null): bool;

    /**
     * Check if a tenant domain is already in use.
     *
     * @param string $domain Domain to check
     * @param string|null $excludeId Tenant ID to exclude from check (for updates)
     * @return bool True if domain exists, false otherwise
     */
    public function domainExists(string $domain, ?string $excludeId = null): bool;
}
