<?php

declare(strict_types=1);

namespace Nexus\HumanResourceOperations\UseCases\Training;

use Nexus\TrainingManagement\Contracts\EnrollmentRepositoryInterface;

final readonly class CompleteTrainingHandler
{
    public function __construct(
        private EnrollmentRepositoryInterface $enrollmentRepository
    ) {}
    
    public function handle(string $enrollmentId): void
    {
        // Mark training as complete
        throw new \RuntimeException('Implementation pending');
    }
}
