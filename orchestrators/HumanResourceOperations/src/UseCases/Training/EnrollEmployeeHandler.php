<?php

declare(strict_types=1);

namespace Nexus\HumanResourceOperations\UseCases\Training;

use Nexus\TrainingManagement\Contracts\EnrollmentRepositoryInterface;

final readonly class EnrollEmployeeHandler
{
    public function __construct(
        private EnrollmentRepositoryInterface $enrollmentRepository
    ) {}
    
    public function handle(string $employeeId, string $programId): void
    {
        // Enroll employee in training program
        throw new \RuntimeException('Implementation pending');
    }
}
