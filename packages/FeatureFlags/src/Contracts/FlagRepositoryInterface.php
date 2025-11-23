<?php

declare(strict_types=1);

namespace Nexus\FeatureFlags\Contracts;

/**
 * Repository interface for storing and retrieving feature flag definitions.
 *
 * Implementations must support:
 * - Tenant isolation (tenant-specific flags override global flags)
 * - Bulk operations for performance
 * - Atomic save/delete operations
 */
interface FlagRepositoryInterface
{
    /**
     * Find a flag definition by name.
     *
     * Implements tenant inheritance:
     * 1. Check for tenant-specific flag (where tenant_id = $tenantId)
     * 2. If not found, check for global flag (where tenant_id IS NULL)
     * 3. Return null if neither exists
     *
     * @param string $name The flag name
     * @param string|null $tenantId The tenant ID for scoping (null for global)
     * @return FlagDefinitionInterface|null The flag definition or null if not found
     */
    public function find(string $name, ?string $tenantId = null): ?FlagDefinitionInterface;

    /**
     * Find multiple flag definitions by names (bulk operation).
     *
     * Applies tenant inheritance for each flag independently.
     * Returns only found flags - missing flags are omitted from result.
     *
     * @param array<string> $names Array of flag names to find
     * @param string|null $tenantId The tenant ID for scoping (null for global)
     * @return array<string, FlagDefinitionInterface> Map of flag name => definition
     */
    public function findMany(array $names, ?string $tenantId = null): array;

    /**
     * Save a flag definition (create or update).
     *
     * @param FlagDefinitionInterface $flag The flag to save
     * @return void
     */
    public function save(FlagDefinitionInterface $flag): void;

    /**
     * Delete a flag definition.
     *
     * @param string $name The flag name to delete
     * @param string|null $tenantId The tenant ID for scoping (null for global)
     * @return void
     */
    public function delete(string $name, ?string $tenantId = null): void;

    /**
     * Get all flag definitions.
     *
     * @param string|null $tenantId The tenant ID for scoping (null for all global flags)
     * @return array<FlagDefinitionInterface> Array of all flags
     */
    public function all(?string $tenantId = null): array;
}
