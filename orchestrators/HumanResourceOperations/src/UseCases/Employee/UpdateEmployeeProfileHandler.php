<?php

declare(strict_types=1);

namespace Nexus\HumanResourceOperations\UseCases\Employee;

use Nexus\EmployeeProfile\Contracts\EmployeeRepositoryInterface;

final readonly class UpdateEmployeeProfileHandler
{
    public function __construct(
        private EmployeeRepositoryInterface $employeeRepository
    ) {}

    /** @param array<string,mixed> $profileData */
    public function handle(string $employeeId, array $profileData): string
    {
        $employee = $this->employeeRepository->findById($employeeId) ?? (object) ['id' => $employeeId];

        foreach ($profileData as $key => $value) {
            $employee->{$key} = $value;
        }

        return $this->employeeRepository->save($employee);
    }
}
