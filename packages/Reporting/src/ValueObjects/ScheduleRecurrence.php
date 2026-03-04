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
     * 
     * @throws \Nexus\Reporting\Exceptions\ScheduleRecurrenceHydrationException
     */
    public static function fromStoredData(array $data): self
    {
        try {
            return new self(
                type: RecurrenceType::from($data['type']),
                interval: $data['interval'] ?? 1,
                cronExpression: $data['cron_expression'] ?? null,
                endsAt: isset($data['ends_at']) ? new \DateTimeImmutable($data['ends_at']) : null,
                maxOccurrences: $data['max_occurrences'] ?? null
            );
        } catch (\Throwable $e) {
            throw \Nexus\Reporting\Exceptions\ScheduleRecurrenceHydrationException::forMalformedData($data, $e);
        }
    }
}
