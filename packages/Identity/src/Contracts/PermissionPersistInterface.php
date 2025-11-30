<?php

declare(strict_types=1);

namespace Nexus\Identity\Contracts;

/**
 * Permission persist interface (CQRS Write Model)
 *
 * Handles write operations for permissions.
 * Consuming applications provide concrete implementations.
 */
interface PermissionPersistInterface
{
    /**
     * Create a new permission
     *
     * @param array<string, mixed> $data Permission data
     */
    public function create(array $data): PermissionInterface;

    /**
     * Update an existing permission
     *
     * @param string $id Permission identifier
     * @param array<string, mixed> $data Updated permission data
     */
    public function update(string $id, array $data): PermissionInterface;

    /**
     * Delete a permission
     */
    public function delete(string $id): bool;
}
