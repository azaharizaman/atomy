<?php

declare(strict_types=1);

namespace Nexus\Disciplinary\Policies;

use Nexus\Disciplinary\Contracts\DisciplinaryPolicyInterface;
use Nexus\Disciplinary\ValueObjects\OffenceType;
use Nexus\Disciplinary\ValueObjects\SanctionLevel;

final readonly class CodeOfConductPolicy implements DisciplinaryPolicyInterface
{
    public function isViolation(OffenceType $offenceType, array $context): bool
    {
        return $offenceType === OffenceType::CODE_OF_CONDUCT;
    }
    
    public function getMinimumSanctionLevel(OffenceType $offenceType): SanctionLevel
    {
        return SanctionLevel::VERBAL_WARNING;
    }
    
    public function getMaximumSanctionLevel(OffenceType $offenceType): SanctionLevel
    {
        return SanctionLevel::FINAL_WARNING;
    }
    
    public function requiresInvestigation(OffenceType $offenceType): bool
    {
        return true;
    }
}
