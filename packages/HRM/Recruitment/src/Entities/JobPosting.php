<?php

declare(strict_types=1);

namespace Nexus\Recruitment\Entities;

use Nexus\Recruitment\ValueObjects\JobCode;

final readonly class JobPosting
{
    public function __construct(
        public string $id,
        public JobCode $jobCode,
        public string $title,
        public string $description,
        public string $department,
        public \DateTimeImmutable $postedAt,
        public ?\DateTimeImmutable $closesAt = null,
        public bool $isActive = true,
    ) {}
}
