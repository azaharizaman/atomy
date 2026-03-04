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
        if ($interval < 1) {
            throw new \InvalidArgumentException('Interval must be at least 1');
        }
    }

    /**
     * Validates that the recurrence is valid for new creations.
     * 
     * @throws \InvalidArgumentException If endsAt is in the past
     */
    public function validateForCreation(): void
    {
        if ($this->endsAt !== null && $this->endsAt <= new \DateTimeImmutable()) {
            throw new \InvalidArgumentException('End date must be in the future');
        }
    }

    /**
     * Hydration factory for existing records.
     */
    public static function fromStoredData(array $data): self
    {
        return new self(
            type: RecurrenceType::from($data['type']),
            interval: $data['interval'] ?? 1,
            cronExpression: $data['cron_expression'] ?? null,
            endsAt: isset($data['ends_at']) ? new \DateTimeImmutable($data['ends_at']) : null,
            maxOccurrences: $data['max_occurrences'] ?? null
        );
    }
}
