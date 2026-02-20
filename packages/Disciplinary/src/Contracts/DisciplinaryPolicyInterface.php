<?php

declare(strict_types=1);

namespace Nexus\Disciplinary\Contracts;

use Nexus\Disciplinary\ValueObjects\OffenceType;
use Nexus\Disciplinary\ValueObjects\SanctionLevel;

interface DisciplinaryPolicyInterface
{
    public function isViolation(OffenceType $offenceType, array $context): bool;
    
    public function getMinimumSanctionLevel(OffenceType $offenceType): SanctionLevel;
    
    public function getMaximumSanctionLevel(OffenceType $offenceType): SanctionLevel;
    
    public function requiresInvestigation(OffenceType $offenceType): bool;
}
