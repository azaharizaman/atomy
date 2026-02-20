<?php

declare(strict_types=1);

namespace Nexus\Leave\Contracts;

interface LeaveAccrualEngineInterface
{
    public function processAccrual(string $employeeId, string $leaveTypeId, \DateTimeImmutable $periodStart, \DateTimeImmutable $periodEnd): float;
}
