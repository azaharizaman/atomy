<?php

declare(strict_types=1);

namespace Nexus\HumanResourceOperations\UseCases\Disciplinary;

use Nexus\Disciplinary\Contracts\DisciplinaryCaseRepositoryInterface;

final readonly class CloseDisciplinaryCaseHandler
{
    public function __construct(
        private DisciplinaryCaseRepositoryInterface $caseRepository
    ) {}
    
    public function handle(string $caseId, string $outcome): void
    {
        // Close disciplinary case with outcome
        throw new \RuntimeException('Implementation pending');
    }
}
