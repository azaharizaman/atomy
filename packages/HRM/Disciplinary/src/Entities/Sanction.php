<?php

declare(strict_types=1);

namespace Nexus\Disciplinary\Entities;

use Nexus\Disciplinary\ValueObjects\SanctionLevel;

final readonly class Sanction
{
    public function __construct(
        public string $id,
        public string $caseId,
        public SanctionLevel $level,
        public string $description,
        public \DateTimeImmutable $effectiveDate,
        public ?\DateTimeImmutable $expiresAt = null,
        public ?string $approvedBy = null,
    ) {}
}
