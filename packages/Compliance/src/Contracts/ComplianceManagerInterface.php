<?php

declare(strict_types=1);

namespace Nexus\Compliance\Contracts;

/**
 * Interface for compliance scheme management.
 * 
 * Manages the lifecycle of compliance schemes (ISO 14001, SOX, etc.) including activation,
 * deactivation, and configuration management.
 */
interface ComplianceManagerInterface
{
    /**
     * Activate a compliance scheme for a tenant.
     *
     * @param string $tenantId The tenant identifier
     * @param string $schemeName The scheme name (e.g., 'ISO14001', 'SOX')
     * @param array<string, mixed> $configuration Scheme-specific configuration
     * @return string The scheme activation ID
     * @throws \Nexus\Compliance\Exceptions\SchemeAlreadyActiveException
     * @throws \Nexus\Compliance\Exceptions\InvalidSchemeException
     */
    public function activateScheme(string $tenantId, string $schemeName, array $configuration = []): string;

    /**
     * Deactivate a compliance scheme for a tenant.
     *
     * @param string $tenantId The tenant identifier
     * @param string $schemeName The scheme name
     * @return void
     * @throws \Nexus\Compliance\Exceptions\SchemeNotFoundException
     */
    public function deactivateScheme(string $tenantId, string $schemeName): void;

    /**
     * Check if a compliance scheme is active for a tenant.
     *
     * @param string $tenantId The tenant identifier
     * @param string $schemeName The scheme name
     * @return bool True if the scheme is active
     */
    public function isSchemeActive(string $tenantId, string $schemeName): bool;

    /**
     * Get all active compliance schemes for a tenant.
     *
     * @param string $tenantId The tenant identifier
     * @return array<ComplianceSchemeInterface>
     */
    public function getActiveSchemes(string $tenantId): array;

    /**
     * Update configuration for an active compliance scheme.
     *
     * @param string $tenantId The tenant identifier
     * @param string $schemeName The scheme name
     * @param array<string, mixed> $configuration New configuration
     * @return void
     * @throws \Nexus\Compliance\Exceptions\SchemeNotFoundException
     */
    public function updateSchemeConfiguration(string $tenantId, string $schemeName, array $configuration): void;

    /**
     * Validate that all required compliance features are enabled.
     *
     * @param string $tenantId The tenant identifier
     * @param string $schemeName The scheme name
     * @return array<string> Array of validation errors (empty if valid)
     */
    public function validateSchemeRequirements(string $tenantId, string $schemeName): array;
}
