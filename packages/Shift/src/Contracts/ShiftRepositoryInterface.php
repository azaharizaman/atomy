<?php

declare(strict_types=1);

namespace Nexus\Shift\Contracts;

interface ShiftRepositoryInterface
{
    public function findById(string $id): ?object;
    
    public function findByEmployeeAndDate(string $employeeId, \DateTimeImmutable $date): array;
    
    public function save(object $shift): string;
}
