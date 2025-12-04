<?php

declare(strict_types=1);

namespace Nexus\HumanResourceOperations\UseCases\Training;

use Nexus\Training\Contracts\EnrollmentRepositoryInterface;

final readonly class ApproveTrainingRequestHandler
{
    public function __construct(
        private EnrollmentRepositoryInterface $enrollmentRepository
    ) {}
    
    public function handle(string $requestId, bool $approved): void
    {
        // Approve or reject training request
        throw new \RuntimeException('Implementation pending');
    }
}
