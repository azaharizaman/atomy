<?php

declare(strict_types=1);

namespace Nexus\Backoffice\Contracts;

use Nexus\Backoffice\Contracts\Persistence\UnitPersistenceInterface;
use Nexus\Backoffice\Contracts\Query\UnitQueryInterface;
use Nexus\Backoffice\Contracts\Validation\UnitValidationInterface;

/**
 * Repository interface for Unit persistence operations.
 *
 * @deprecated This interface violates ISP and CQRS principles.
 *             Use segregated interfaces instead:
 *             - UnitPersistenceInterface for write operations
 *             - UnitQueryInterface for read operations
 *             - UnitValidationInterface for validation operations
 *             - UnitManagementService for business logic
 *             This interface will be removed in v2.0.
 */
interface UnitRepositoryInterface extends UnitPersistenceInterface, UnitQueryInterface, UnitValidationInterface
{
    public function findById(string $id): ?UnitInterface;

    public function findByCode(string $companyId, string $code): ?UnitInterface;

    /**
     * @return array<UnitInterface>
     */
    public function getByCompany(string $companyId): array;

    /**
     * Get all active units for a company.
     *
     * @deprecated Use UnitManagementService::getActiveByCompany() instead
     * @return array<UnitInterface>
     */
    public function getActiveByCompany(string $companyId): array;

    /**
     * Get units by type within a company.
     *
     * @return array<UnitInterface>
     */
    public function getByType(string $companyId, string $type): array;

    /**
     * @return array<string>
     */
    public function getUnitMembers(string $unitId): array;

    /**
     * @param array<string, mixed> $data
     */
    public function save(array $data): UnitInterface;

    /**
     * @param array<string, mixed> $data
     */
    public function update(string $id, array $data): UnitInterface;

    public function delete(string $id): bool;

    public function codeExists(string $companyId, string $code, ?string $excludeId = null): bool;

    public function addMember(string $unitId, string $staffId, string $role): void;

    public function removeMember(string $unitId, string $staffId): void;

    public function isMember(string $unitId, string $staffId): bool;

    public function getMemberRole(string $unitId, string $staffId): ?string;
}
