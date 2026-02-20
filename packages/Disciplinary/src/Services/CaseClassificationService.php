<?php

declare(strict_types=1);

namespace Nexus\Disciplinary\Services;

use Nexus\Disciplinary\Entities\DisciplinaryCase;
use Nexus\Disciplinary\ValueObjects\OffenceType;

final readonly class CaseClassificationService
{
    public function classify(DisciplinaryCase $case): string
    {
        return match ($case->offenceType) {
            OffenceType::FRAUD, OffenceType::THEFT => 'CRITICAL',
            OffenceType::WORKPLACE_HARASSMENT, OffenceType::SAFETY_VIOLATION => 'MAJOR',
            default => 'MINOR',
        };
    }
}
