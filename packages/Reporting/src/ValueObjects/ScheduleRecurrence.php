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
    ) {
        if ($this->interval < 1) {
            throw new \InvalidArgumentException('Recurrence interval must be at least 1');
        }

        if ($this->type === RecurrenceType::CRON) {
            if ($this->cronExpression === null || trim($this->cronExpression) === '') {
                throw new \InvalidArgumentException('Cron expression is required for CRON recurrence type');
            }
        } elseif ($this->cronExpression !== null) {
            throw new \InvalidArgumentException('Cron expression must be null for non-CRON recurrence types');
        }

        if ($this->maxOccurrences !== null && $this->maxOccurrences <= 0) {
            throw new \InvalidArgumentException('Max occurrences must be greater than 0');
        }

        if ($this->endsAt !== null && $this->endsAt <= new \DateTimeImmutable()) {
            throw new \InvalidArgumentException('End date must be in the future');
        }
    }
}
