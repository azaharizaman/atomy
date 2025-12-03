<?php

declare(strict_types=1);

namespace Nexus\HumanResourceOperations\UseCases\Training;

use Nexus\TrainingManagement\Contracts\CertificationRepositoryInterface;

final readonly class GrantCertificationHandler
{
    public function __construct(
        private CertificationRepositoryInterface $certificationRepository
    ) {}
    
    public function handle(string $employeeId, string $programId): void
    {
        // Grant certification upon program completion
        throw new \RuntimeException('Implementation pending');
    }
}
