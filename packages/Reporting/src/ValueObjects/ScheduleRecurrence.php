<?php

declare(strict_types=1);

namespace Nexus\Reporting\ValueObjects;

final readonly class ScheduleRecurrence
{
    public function __construct(
        public RecurrenceType $type,
        public int $interval = 1,
        public ?string $cronExpression = null,
        public ?\DateTimeImmutable $endsAt = null,
        public ?int $maxOccurrences = null
    ) {}
}
