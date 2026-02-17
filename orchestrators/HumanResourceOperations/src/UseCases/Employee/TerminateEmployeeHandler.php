<?php

declare(strict_types=1);

namespace Nexus\HumanResourceOperations\UseCases\Employee;

use Nexus\EmployeeProfile\Contracts\EmployeeRepositoryInterface;

final readonly class TerminateEmployeeHandler
{
    public function __construct(
        private EmployeeRepositoryInterface $employeeRepository
    ) {}

    public function handle(string $employeeId, string $reason, ?\DateTimeImmutable $terminationDate = null): string
    {
        $employee = $this->employeeRepository->findById($employeeId);
        if ($employee === null) {
            throw new \RuntimeException('Employee not found: ' . $employeeId);
        }

        $employee->employmentStatus = 'terminated';
        $employee->terminationReason = $reason;
        $employee->terminationDate = $terminationDate ?? new \DateTimeImmutable();

        return $this->employeeRepository->save($employee);
    }
}
