<?php

declare(strict_types=1);

namespace Nexus\Backoffice\Contracts\Persistence;

use Nexus\Backoffice\Contracts\UnitInterface;

/**
 * Persistence interface for Unit write operations (CQRS - Command).
 * Handles creation, updates, and deletions.
 */
interface UnitPersistenceInterface
{
    /**
     * Create a new unit.
     *
     * @param array<string, mixed> $data
     */
    public function save(array $data): UnitInterface;

    /**
     * Update an existing unit.
     *
     * @param array<string, mixed> $data
     */
    public function update(string $id, array $data): UnitInterface;

    /**
     * Delete a unit.
     */
    public function delete(string $id): bool;

    /**
     * Add a member to a unit.
     */
    public function addMember(string $unitId, string $staffId, string $role): void;

    /**
     * Remove a member from a unit.
     */
    public function removeMember(string $unitId, string $staffId): void;
}
