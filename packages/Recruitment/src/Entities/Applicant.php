<?php

declare(strict_types=1);

namespace Nexus\Recruitment\Entities;

use Nexus\Recruitment\ValueObjects\ApplicantScore;

final readonly class Applicant
{
    public function __construct(
        public string $id,
        public string $jobPostingId,
        public string $fullName,
        public string $email,
        public string $status,
        public ?ApplicantScore $score = null,
        public \DateTimeImmutable $appliedAt = new \DateTimeImmutable(),
    ) {}
}
