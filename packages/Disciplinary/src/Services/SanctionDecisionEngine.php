<?php

declare(strict_types=1);

namespace Nexus\Disciplinary\Services;

use Nexus\Disciplinary\Contracts\SanctionDecisionEngineInterface;
use Nexus\Disciplinary\Entities\DisciplinaryCase;
use Nexus\Disciplinary\ValueObjects\SanctionLevel;

final readonly class SanctionDecisionEngine implements SanctionDecisionEngineInterface
{
    public function calculateSanction(DisciplinaryCase $case): SanctionLevel
    {
        // Business logic to calculate appropriate sanction level
        // based on offence type, severity, previous offences, etc.
        return SanctionLevel::WRITTEN_WARNING;
    }
    
    public function requiresApproval(SanctionLevel $level): bool
    {
        return $level->getSeverity() >= SanctionLevel::SUSPENSION->getSeverity();
    }
    
    public function getApproverRoleForLevel(SanctionLevel $level): string
    {
        return match (true) {
            $level === SanctionLevel::TERMINATION => 'CEO',
            $level === SanctionLevel::DEMOTION || $level === SanctionLevel::SUSPENSION => 'HR_DIRECTOR',
            default => 'MANAGER',
        };
    }
}
