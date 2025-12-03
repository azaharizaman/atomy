<?php

declare(strict_types=1);

namespace Nexus\Disciplinary\Contracts;

use Nexus\Disciplinary\Entities\DisciplinaryCase;
use Nexus\Disciplinary\ValueObjects\SanctionLevel;

interface SanctionDecisionEngineInterface
{
    public function calculateSanction(DisciplinaryCase $case): SanctionLevel;
    
    public function requiresApproval(SanctionLevel $level): bool;
    
    public function getApproverRoleForLevel(SanctionLevel $level): string;
}
