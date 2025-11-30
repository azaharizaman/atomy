<?php

declare(strict_types=1);

namespace Nexus\Payroll\Contracts;

/**
 * Persistence contract for payroll component write operations.
 *
 * Implements CQRS pattern - write operations only.
 */
interface ComponentPersistInterface
{
    /**
     * Create a new component.
     *
     * @param array<string, mixed> $data Component data
     * @return ComponentInterface Created component
     */
    public function create(array $data): ComponentInterface;

    /**
     * Update an existing component.
     *
     * @param string $id Component ULID
     * @param array<string, mixed> $data Updated data
     * @return ComponentInterface Updated component
     */
    public function update(string $id, array $data): ComponentInterface;

    /**
     * Delete a component.
     *
     * @param string $id Component ULID
     * @return bool True if deleted successfully
     */
    public function delete(string $id): bool;
}
