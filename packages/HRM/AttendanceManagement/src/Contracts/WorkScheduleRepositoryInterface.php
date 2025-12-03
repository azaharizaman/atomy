<?php

declare(strict_types=1);

namespace Nexus\AttendanceManagement\Contracts;

interface WorkScheduleRepositoryInterface
{
    public function findByEmployeeId(string $employeeId): ?object;
    
    public function save(object $schedule): string;
}
