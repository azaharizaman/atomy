<?php

declare(strict_types=1);

namespace Nexus\Reporting\ValueObjects;

final readonly class ScheduleDefinition
{
    /** @param array<string, mixed> $payload */
    public function __construct(
        public JobType $jobType,
        public string $targetId,
        public \DateTimeImmutable $runAt,
        public array $payload = [],
        public int $priority = 5,
        public ?ScheduleRecurrence $recurrence = null
    ) {}
}
