<?php

declare(strict_types=1);

namespace Nexus\Backoffice\Contracts\Persistence;

use Nexus\Backoffice\Contracts\OfficeInterface;

/**
 * Persistence interface for Office write operations (CQRS - Command).
 * Handles creation, updates, and deletions.
 */
interface OfficePersistenceInterface
{
    /**
     * Create a new office.
     *
     * @param array<string, mixed> $data
     */
    public function save(array $data): OfficeInterface;

    /**
     * Update an existing office.
     *
     * @param array<string, mixed> $data
     */
    public function update(string $id, array $data): OfficeInterface;

    /**
     * Delete an office.
     */
    public function delete(string $id): bool;
}
