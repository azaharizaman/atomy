<?php

declare(strict_types=1);

namespace Nexus\Backoffice\Contracts\Persistence;

use Nexus\Backoffice\Contracts\StaffInterface;

/**
 * Persistence interface for Staff write operations (CQRS - Command).
 * Handles creation, updates, and deletions.
 */
interface StaffPersistenceInterface
{
    /**
     * Create a new staff member.
     *
     * @param array<string, mixed> $data
     */
    public function save(array $data): StaffInterface;

    /**
     * Update an existing staff member.
     *
     * @param array<string, mixed> $data
     */
    public function update(string $id, array $data): StaffInterface;

    /**
     * Delete a staff member.
     */
    public function delete(string $id): bool;
}
