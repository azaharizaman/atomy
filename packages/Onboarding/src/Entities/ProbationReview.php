<?php

declare(strict_types=1);

namespace Nexus\Onboarding\Entities;

use Nexus\Onboarding\ValueObjects\ProbationStatus;

final readonly class ProbationReview
{
    public function __construct(
        public string $id,
        public string $employeeId,
        public \DateTimeImmutable $reviewDate,
        public ProbationStatus $status,
        public string $reviewedBy,
        public ?string $comments = null,
    ) {}
}
