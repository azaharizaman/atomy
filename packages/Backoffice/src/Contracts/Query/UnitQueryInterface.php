<?php

declare(strict_types=1);

namespace Nexus\Backoffice\Contracts\Query;

use Nexus\Backoffice\Contracts\UnitInterface;

/**
 * Query interface for Unit read operations (CQRS - Query).
 * Handles data retrieval without side effects.
 */
interface UnitQueryInterface
{
    /**
     * Find unit by ID.
     */
    public function findById(string $id): ?UnitInterface;

    /**
     * Find unit by code within a company.
     */
    public function findByCode(string $companyId, string $code): ?UnitInterface;

    /**
     * Get all units for a company.
     *
     * @return array<UnitInterface>
     */
    public function getByCompany(string $companyId): array;

    /**
     * Get units by type within a company.
     *
     * @return array<UnitInterface>
     */
    public function getByType(string $companyId, string $type): array;

    /**
     * Get member IDs for a unit.
     *
     * @return array<string>
     */
    public function getUnitMembers(string $unitId): array;

    /**
     * Get role of a member in a unit.
     */
    public function getMemberRole(string $unitId, string $staffId): ?string;
}
