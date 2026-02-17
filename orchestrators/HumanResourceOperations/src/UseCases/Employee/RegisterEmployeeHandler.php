<?php

declare(strict_types=1);

namespace Nexus\HumanResourceOperations\UseCases\Employee;

use Nexus\HumanResourceOperations\Services\EmployeeRegistrationService;

final readonly class RegisterEmployeeHandler
{
    public function __construct(
        private EmployeeRegistrationService $registrationService
    ) {}

    /**
     * @param array<string,mixed> $payload
     * @return array{employeeId:string,userId:string,partyId:string}
     */
    public function handle(array $payload): array
    {
        return $this->registrationService->registerNewEmployee(
            applicationId: (string) $payload['applicationId'],
            candidateName: (string) $payload['candidateName'],
            candidateEmail: (string) $payload['candidateEmail'],
            positionId: (string) $payload['positionId'],
            departmentId: (string) $payload['departmentId'],
            startDate: (string) $payload['startDate'],
            reportsTo: isset($payload['reportsTo']) ? (string) $payload['reportsTo'] : null,
            metadata: isset($payload['metadata']) && is_array($payload['metadata']) ? $payload['metadata'] : [],
        );
    }
}
