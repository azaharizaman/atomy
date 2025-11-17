<?php

declare(strict_types=1);

namespace Nexus\Payroll\Contracts;

/**
 * Repository contract for employee component persistence.
 */
interface EmployeeComponentRepositoryInterface
{
    public function findById(string $id): ?EmployeeComponentInterface;
    
    public function getActiveComponentsForEmployee(string $employeeId): array;
    
    public function create(array $data): EmployeeComponentInterface;
    
    public function update(string $id, array $data): EmployeeComponentInterface;
    
    public function delete(string $id): bool;
}
