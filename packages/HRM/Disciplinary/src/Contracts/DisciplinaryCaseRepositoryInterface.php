<?php

declare(strict_types=1);

namespace Nexus\Disciplinary\Contracts;

use Nexus\Disciplinary\Entities\DisciplinaryCase;

interface DisciplinaryCaseRepositoryInterface
{
    public function findById(string $caseId): ?DisciplinaryCase;
    
    public function findByEmployeeId(string $employeeId): array;
    
    public function save(DisciplinaryCase $case): void;
    
    public function findPendingCases(): array;
    
    public function findByStatus(string $status): array;
}
