<?php

declare(strict_types=1);

namespace Nexus\Disciplinary\Policies;

use Nexus\Disciplinary\Contracts\DisciplinaryPolicyInterface;
use Nexus\Disciplinary\ValueObjects\OffenceType;
use Nexus\Disciplinary\ValueObjects\SanctionLevel;

final readonly class FraudPolicy implements DisciplinaryPolicyInterface
{
    public function isViolation(OffenceType $offenceType, array $context): bool
    {
        return $offenceType === OffenceType::FRAUD || $offenceType === OffenceType::THEFT;
    }
    
    public function getMinimumSanctionLevel(OffenceType $offenceType): SanctionLevel
    {
        return SanctionLevel::SUSPENSION;
    }
    
    public function getMaximumSanctionLevel(OffenceType $offenceType): SanctionLevel
    {
        return SanctionLevel::TERMINATION;
    }
    
    public function requiresInvestigation(OffenceType $offenceType): bool
    {
        return true;
    }
}
