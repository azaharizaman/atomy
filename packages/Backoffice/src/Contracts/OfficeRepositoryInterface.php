<?php

declare(strict_types=1);

namespace Nexus\Backoffice\Contracts;

/**
 * Repository interface for Office persistence operations.
 */
interface OfficeRepositoryInterface
{
    public function findById(string $id): ?OfficeInterface;

    public function findByCode(string $companyId, string $code): ?OfficeInterface;

    /**
     * @return array<OfficeInterface>
     */
    public function getByCompany(string $companyId): array;

    /**
     * @return array<OfficeInterface>
     */
    public function getActiveByCompany(string $companyId): array;

    /**
     * @return array<OfficeInterface>
     */
    public function getByLocation(string $country, ?string $city = null): array;

    /**
     * @param array<string, mixed> $data
     */
    public function save(array $data): OfficeInterface;

    /**
     * @param array<string, mixed> $data
     */
    public function update(string $id, array $data): OfficeInterface;

    public function delete(string $id): bool;

    public function codeExists(string $companyId, string $code, ?string $excludeId = null): bool;

    public function hasActiveStaff(string $officeId): bool;

    public function getHeadOffice(string $companyId): ?OfficeInterface;

    public function hasHeadOffice(string $companyId, ?string $excludeId = null): bool;
}
