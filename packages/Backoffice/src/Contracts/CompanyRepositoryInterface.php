<?php

declare(strict_types=1);

namespace Nexus\Backoffice\Contracts;

use Nexus\Backoffice\Contracts\Persistence\CompanyPersistenceInterface;
use Nexus\Backoffice\Contracts\Query\CompanyQueryInterface;
use Nexus\Backoffice\Contracts\Validation\CompanyValidationInterface;

/**
 * Repository interface for Company persistence operations.
 *
 * @deprecated This fat interface violates ISP and CQRS. Use the segregated interfaces instead:
 *             - CompanyPersistenceInterface for write operations
 *             - CompanyQueryInterface for read operations
 *             - CompanyValidationInterface for validation operations
 *             - CompanyHierarchyService for business logic (getActive, getSubsidiaries, etc.)
 *
 * This interface is kept for backward compatibility and now extends the new interfaces.
 * It will be removed in v2.0.
 */
interface CompanyRepositoryInterface extends
    CompanyPersistenceInterface,
    CompanyQueryInterface,
    CompanyValidationInterface
{
    /**
     * Find a company by its unique identifier.
     */
    public function findById(string $id): ?CompanyInterface;

    /**
     * Find a company by its unique code.
     */
    public function findByCode(string $code): ?CompanyInterface;

    /**
     * Find a company by its registration number.
     */
    public function findByRegistrationNumber(string $registrationNumber): ?CompanyInterface;

    /**
     * Get all companies.
     *
     * @return array<CompanyInterface>
     */
    public function getAll(): array;

    /**
     * Get all active companies.
     *
     * @deprecated Use CompanyHierarchyService::getActive() instead. Business logic should not be in repositories.
     * @return array<CompanyInterface>
     */
    public function getActive(): array;

    /**
     * Get all subsidiaries of a parent company.
     *
     * @deprecated Use CompanyHierarchyService::getSubsidiaries() instead. Business logic should not be in repositories.
     * @return array<CompanyInterface>
     */
    public function getSubsidiaries(string $parentCompanyId): array;

    /**
     * Get the parent company chain for a company.
     *
     * @deprecated Use CompanyHierarchyService::getParentChain() instead. Business logic should not be in repositories.
     * @return array<CompanyInterface>
     */
    public function getParentChain(string $companyId): array;

    /**
     * Save a company.
     *
     * @param array<string, mixed> $data
     */
    public function save(array $data): CompanyInterface;

    /**
     * Update a company.
     *
     * @param array<string, mixed> $data
     */
    public function update(string $id, array $data): CompanyInterface;

    /**
     * Delete a company.
     */
    public function delete(string $id): bool;

    /**
     * Check if a company code exists.
     */
    public function codeExists(string $code, ?string $excludeId = null): bool;

    /**
     * Check if a registration number exists.
     */
    public function registrationNumberExists(string $registrationNumber, ?string $excludeId = null): bool;

    /**
     * Check for circular parent reference.
     *
     * @deprecated Use CompanyHierarchyService::hasCircularReference() instead. Business logic should not be in repositories.
     */
    public function hasCircularReference(string $companyId, string $proposedParentId): bool;
}
