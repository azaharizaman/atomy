<?php

declare(strict_types=1);

namespace Nexus\Hrm\Contracts;

/**
 * Repository contract for employment contract persistence operations.
 */
interface ContractRepositoryInterface
{
    /**
     * Find contract by ID.
     *
     * @param string $id Contract ULID
     * @return ContractInterface|null
     */
    public function findById(string $id): ?ContractInterface;
    
    /**
     * Get active contract for employee.
     *
     * @param string $employeeId Employee ULID
     * @return ContractInterface|null
     */
    public function getActiveContract(string $employeeId): ?ContractInterface;
    
    /**
     * Get all contracts for employee.
     *
     * @param string $employeeId Employee ULID
     * @return array<ContractInterface>
     */
    public function getEmployeeContracts(string $employeeId): array;
    
    /**
     * Get expiring contracts within days.
     *
     * @param string $tenantId Tenant ULID
     * @param int $days Number of days ahead
     * @return array<ContractInterface>
     */
    public function getExpiringContracts(string $tenantId, int $days): array;
    
    /**
     * Create a new contract.
     *
     * @param array<string, mixed> $data
     * @return ContractInterface
     * @throws \Nexus\Hrm\Exceptions\ContractValidationException
     * @throws \Nexus\Hrm\Exceptions\ContractOverlapException
     */
    public function create(array $data): ContractInterface;
    
    /**
     * Update a contract.
     *
     * @param string $id Contract ULID
     * @param array<string, mixed> $data
     * @return ContractInterface
     * @throws \Nexus\Hrm\Exceptions\ContractNotFoundException
     * @throws \Nexus\Hrm\Exceptions\ContractValidationException
     */
    public function update(string $id, array $data): ContractInterface;
    
    /**
     * Delete a contract.
     *
     * @param string $id Contract ULID
     * @return bool
     * @throws \Nexus\Hrm\Exceptions\ContractNotFoundException
     */
    public function delete(string $id): bool;
}
