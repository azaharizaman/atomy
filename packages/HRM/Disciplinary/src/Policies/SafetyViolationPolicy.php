<?php

declare(strict_types=1);

namespace Nexus\Disciplinary\Policies;

use Nexus\Disciplinary\Contracts\DisciplinaryPolicyInterface;
use Nexus\Disciplinary\ValueObjects\OffenceType;
use Nexus\Disciplinary\ValueObjects\SanctionLevel;

final readonly class SafetyViolationPolicy implements DisciplinaryPolicyInterface
{
    public function isViolation(OffenceType $offenceType, array $context): bool
    {
        return $offenceType === OffenceType::SAFETY_VIOLATION;
    }
    
    public function getMinimumSanctionLevel(OffenceType $offenceType): SanctionLevel
    {
        return SanctionLevel::WRITTEN_WARNING;
    }
    
    public function getMaximumSanctionLevel(OffenceType $offenceType): SanctionLevel
    {
        return SanctionLevel::SUSPENSION;
    }
    
    public function requiresInvestigation(OffenceType $offenceType): bool
    {
        return true;
    }
}
