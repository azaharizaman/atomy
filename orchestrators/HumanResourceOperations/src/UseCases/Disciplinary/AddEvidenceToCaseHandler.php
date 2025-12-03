<?php

declare(strict_types=1);

namespace Nexus\HumanResourceOperations\UseCases\Disciplinary;

use Nexus\Disciplinary\Contracts\EvidenceRepositoryInterface;

final readonly class AddEvidenceToCaseHandler
{
    public function __construct(
        private EvidenceRepositoryInterface $evidenceRepository
    ) {}
    
    public function handle(string $caseId, array $evidenceData): void
    {
        // Add evidence to disciplinary case
        throw new \RuntimeException('Implementation pending');
    }
}
