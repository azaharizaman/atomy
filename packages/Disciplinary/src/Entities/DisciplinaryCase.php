<?php

declare(strict_types=1);

namespace Nexus\Disciplinary\Entities;

use Nexus\Disciplinary\ValueObjects\CaseNumber;
use Nexus\Disciplinary\ValueObjects\OffenceType;
use Nexus\Disciplinary\ValueObjects\CaseOutcome;

final readonly class DisciplinaryCase
{
    public function __construct(
        public string $id,
        public CaseNumber $caseNumber,
        public string $employeeId,
        public OffenceType $offenceType,
        public string $description,
        public \DateTimeImmutable $incidentDate,
        public string $reportedBy,
        public string $status,
        public ?CaseOutcome $outcome = null,
        public ?\DateTimeImmutable $closedAt = null,
    ) {}
}
