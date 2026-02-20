<?php

declare(strict_types=1);

namespace Nexus\Disciplinary\Services;

use Nexus\Disciplinary\Entities\DisciplinaryCase;

final readonly class CaseValidationService
{
    public function validate(DisciplinaryCase $case): bool
    {
        return !empty($case->description) && !empty($case->employeeId);
    }
}
