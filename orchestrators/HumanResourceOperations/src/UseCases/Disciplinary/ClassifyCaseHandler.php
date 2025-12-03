<?php

declare(strict_types=1);

namespace Nexus\HumanResourceOperations\UseCases\Disciplinary;

use Nexus\Disciplinary\Services\CaseClassificationService;

final readonly class ClassifyCaseHandler
{
    public function __construct(
        private CaseClassificationService $classificationService
    ) {}
    
    public function handle(string $caseId): string
    {
        // Classify disciplinary case severity
        throw new \RuntimeException('Implementation pending');
    }
}
