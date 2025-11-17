<?php

declare(strict_types=1);

namespace Nexus\Backoffice\Contracts;

/**
 * Repository interface for Company persistence operations.
 *
 * Defines all data access methods needed for company management.
 */
interface CompanyRepositoryInterface
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
     * @return array<CompanyInterface>
     */
    public function getActive(): array;

    /**
     * Get all subsidiaries of a parent company.
     *
     * @return array<CompanyInterface>
     */
    public function getSubsidiaries(string $parentCompanyId): array;

    /**
     * Get the parent company chain for a company.
     *
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
     */
    public function hasCircularReference(string $companyId, string $proposedParentId): bool;
}
