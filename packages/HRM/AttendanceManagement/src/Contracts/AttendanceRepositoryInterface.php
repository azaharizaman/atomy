<?php

declare(strict_types=1);

namespace Nexus\AttendanceManagement\Contracts;

interface AttendanceRepositoryInterface
{
    public function findById(string $id): ?object;
    
    public function findByEmployeeAndDate(string $employeeId, \DateTimeImmutable $date): ?object;
    
    public function save(object $attendance): string;
}
