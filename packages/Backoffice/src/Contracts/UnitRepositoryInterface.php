<?php

declare(strict_types=1);

namespace Nexus\Backoffice\Contracts;

/**
 * Repository interface for Unit persistence operations.
 */
interface UnitRepositoryInterface
{
    public function findById(string $id): ?UnitInterface;

    public function findByCode(string $companyId, string $code): ?UnitInterface;

    /**
     * @return array<UnitInterface>
     */
    public function getByCompany(string $companyId): array;

    /**
     * @return array<UnitInterface>
     */
    public function getActiveByCompany(string $companyId): array;

    /**
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
