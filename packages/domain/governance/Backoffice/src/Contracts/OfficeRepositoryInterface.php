<?php

declare(strict_types=1);

namespace Nexus\Backoffice\Contracts;

use Nexus\Backoffice\Contracts\Persistence\OfficePersistenceInterface;
use Nexus\Backoffice\Contracts\Query\OfficeQueryInterface;
use Nexus\Backoffice\Contracts\Validation\OfficeValidationInterface;

/**
 * Repository interface for Office persistence operations.
 *
 * @deprecated This interface violates ISP and CQRS principles.
 *             Use segregated interfaces instead:
 *             - OfficePersistenceInterface for write operations
 *             - OfficeQueryInterface for read operations
 *             - OfficeValidationInterface for validation operations
 *             - OfficeHierarchyService for business logic
 *             This interface will be removed in v2.0.
 */
interface OfficeRepositoryInterface extends OfficePersistenceInterface, OfficeQueryInterface, OfficeValidationInterface
{
    public function findById(string $id): ?OfficeInterface;

    public function findByCode(string $companyId, string $code): ?OfficeInterface;

    /**
     * @return array<OfficeInterface>
     */
    public function getByCompany(string $companyId): array;

    /**
     * Get all active offices for a company.
     *
     * @deprecated Use OfficeHierarchyService::getActiveByCompany() instead
     * @return array<OfficeInterface>
     */
    public function getActiveByCompany(string $companyId): array;

    /**
     * Get the head office for a company.
     *
     * @deprecated Use OfficeHierarchyService::getHeadOffice() instead
     */
    public function getHeadOffice(string $companyId): ?OfficeInterface;

    public function hasHeadOffice(string $companyId, ?string $excludeId = null): bool;
}
