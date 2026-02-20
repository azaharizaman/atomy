<?php

declare(strict_types=1);

namespace Nexus\Leave\Contracts;

interface LeaveRepositoryInterface
{
    public function findById(string $id): ?object;
    
    public function findByEmployeeId(string $employeeId): array;
    
    public function save(object $leave): string;
    
    public function delete(string $id): void;
}
