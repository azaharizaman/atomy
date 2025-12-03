<?php

declare(strict_types=1);

namespace Nexus\HumanResourceOperations\UseCases\Disciplinary;

use Nexus\Disciplinary\Contracts\SanctionDecisionEngineInterface;

final readonly class CalculateSanctionHandler
{
    public function __construct(
        private SanctionDecisionEngineInterface $sanctionEngine
    ) {}
    
    public function handle(string $caseId): array
    {
        // Calculate appropriate sanction for case
        throw new \RuntimeException('Implementation pending');
    }
}
