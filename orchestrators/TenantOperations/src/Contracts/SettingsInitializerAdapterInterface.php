<?php

declare(strict_types=1);

namespace Nexus\TenantOperations\Contracts;

/**
 * Adapter interface for initializing tenant settings.
 * 
 * Must be implemented by Layer 3 (Adapters) using Nexus\Setting package.
 */
interface SettingsInitializerAdapterInterface
{
    /**
     * Initialize settings for a tenant.
     *
     * @param string $tenantId
     * @param array<string, mixed> $settings
     * @return void
     */
    public function initialize(string $tenantId, array $settings): void;
}
