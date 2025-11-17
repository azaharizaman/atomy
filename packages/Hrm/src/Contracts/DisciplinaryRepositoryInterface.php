<?php

declare(strict_types=1);

namespace Nexus\Hrm\Contracts;

/**
 * Repository contract for disciplinary case persistence operations.
 */
interface DisciplinaryRepositoryInterface
{
    /**
     * Find disciplinary case by ID.
     *
     * @param string $id Disciplinary ULID
     * @return DisciplinaryInterface|null
     */
    public function findById(string $id): ?DisciplinaryInterface;
    
    /**
     * Find disciplinary case by case number.
     *
     * @param string $tenantId Tenant ULID
     * @param string $caseNumber Unique case number
     * @return DisciplinaryInterface|null
     */
    public function findByCaseNumber(string $tenantId, string $caseNumber): ?DisciplinaryInterface;
    
    /**
     * Get all disciplinary cases for employee.
     *
     * @param string $employeeId Employee ULID
     * @param array<string, mixed> $filters
     * @return array<DisciplinaryInterface>
     */
    public function getEmployeeCases(string $employeeId, array $filters = []): array;
    
    /**
     * Get open cases for tenant.
     *
     * @param string $tenantId Tenant ULID
     * @return array<DisciplinaryInterface>
     */
    public function getOpenCases(string $tenantId): array;
    
    /**
     * Get cases requiring follow-up.
     *
     * @param string $tenantId Tenant ULID
     * @return array<DisciplinaryInterface>
     */
    public function getCasesRequiringFollowUp(string $tenantId): array;
    
    /**
     * Create a disciplinary case.
     *
     * @param array<string, mixed> $data
     * @return DisciplinaryInterface
     * @throws \Nexus\Hrm\Exceptions\DisciplinaryValidationException
     * @throws \Nexus\Hrm\Exceptions\DisciplinaryDuplicateException
     */
    public function create(array $data): DisciplinaryInterface;
    
    /**
     * Update a disciplinary case.
     *
     * @param string $id Disciplinary ULID
     * @param array<string, mixed> $data
     * @return DisciplinaryInterface
     * @throws \Nexus\Hrm\Exceptions\DisciplinaryNotFoundException
     * @throws \Nexus\Hrm\Exceptions\DisciplinaryValidationException
     */
    public function update(string $id, array $data): DisciplinaryInterface;
    
    /**
     * Delete a disciplinary case.
     *
     * @param string $id Disciplinary ULID
     * @return bool
     * @throws \Nexus\Hrm\Exceptions\DisciplinaryNotFoundException
     */
    public function delete(string $id): bool;
}
