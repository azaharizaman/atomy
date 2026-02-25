<?php

declare(strict_types=1);

namespace Nexus\Sales\Contracts;

/**
 * Interface for Sales-specific settings.
 *
 * This allows the Sales package to retrieve required settings without
 * depending directly on the core Setting package.
 */
interface SalesSettingInterface
{
    /**
     * Get the base currency for a given tenant.
     *
     * @param string $tenantId The tenant ID
     * @return string The base currency code (e.g., 'MYR', 'USD')
     */
    public function getBaseCurrency(string $tenantId): string;
}
