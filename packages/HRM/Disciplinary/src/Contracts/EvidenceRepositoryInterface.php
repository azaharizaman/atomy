<?php

declare(strict_types=1);

namespace Nexus\Disciplinary\Contracts;

use Nexus\Disciplinary\Entities\Evidence;

interface EvidenceRepositoryInterface
{
    public function findById(string $evidenceId): ?Evidence;
    
    public function findByCaseId(string $caseId): array;
    
    public function save(Evidence $evidence): void;
    
    public function delete(string $evidenceId): void;
}
