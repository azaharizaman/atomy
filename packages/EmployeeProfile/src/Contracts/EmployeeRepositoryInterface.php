<?php

declare(strict_types=1);

namespace Nexus\EmployeeProfile\Contracts;

interface EmployeeRepositoryInterface
{
    public function findById(string $id): ?object;
    
    public function findByEmployeeNumber(string $employeeNumber): ?object;
    
    public function save(object $employee): string;
    
    public function delete(string $id): void;
}
