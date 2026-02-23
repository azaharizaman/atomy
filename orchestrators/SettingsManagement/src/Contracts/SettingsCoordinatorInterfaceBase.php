<?php

declare(strict_types=1);

namespace Nexus\SettingsManagement\Contracts;

/**
 * Base interface for all settings management coordinators.
 */
interface SettingsCoordinatorInterfaceBase
{
    /**
     * Get the coordinator name.
     */
    public function getName(): string;

    /**
     * Check if the coordinator has all required data for configuration.
     */
    public function hasRequiredData(string $tenantId): bool;
}
