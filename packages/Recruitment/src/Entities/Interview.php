<?php

declare(strict_types=1);

namespace Nexus\Recruitment\Entities;

use Nexus\Recruitment\ValueObjects\InterviewResult;

final readonly class Interview
{
    public function __construct(
        public string $id,
        public string $applicantId,
        public string $interviewerId,
        public \DateTimeImmutable $scheduledAt,
        public string $type,
        public ?InterviewResult $result = null,
        public ?\DateTimeImmutable $completedAt = null,
    ) {}
}
