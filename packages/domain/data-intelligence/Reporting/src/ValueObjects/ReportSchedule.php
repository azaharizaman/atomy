<?php

declare(strict_types=1);

namespace Nexus\Reporting\ValueObjects;

/**
 * Immutable value object representing a report schedule configuration.
 */
final readonly class ReportSchedule
{
    /**
     * @param ScheduleType $type The schedule frequency type
     * @param string|null $cronExpression Required if type is CRON
     * @param \DateTimeImmutable|null $startsAt When the schedule begins (null = immediately)
     * @param \DateTimeImmutable|null $endsAt When the schedule ends (null = indefinite)
     * @param int|null $maxOccurrences Maximum number of executions (null = unlimited)
     * @throws \InvalidArgumentException If cron expression is missing for CRON type
     */
    public function __construct(
        public ScheduleType $type,
        public ?string $cronExpression = null,
        public ?\DateTimeImmutable $startsAt = null,
        public ?\DateTimeImmutable $endsAt = null,
        public ?int $maxOccurrences = null,
    ) {
        if ($this->type->requiresCronExpression() && $this->cronExpression === null) {
            throw new \InvalidArgumentException('Cron expression is required for CRON schedule type');
        }

        if ($this->startsAt && $this->endsAt && $this->startsAt >= $this->endsAt) {
            throw new \InvalidArgumentException('Start date must be before end date');
        }

        if ($this->maxOccurrences !== null && $this->maxOccurrences < 1) {
            throw new \InvalidArgumentException('Max occurrences must be at least 1');
        }
    }

    /**
     * Create a one-time schedule.
     */
    public static function once(\DateTimeImmutable $runAt): self
    {
        return new self(
            type: ScheduleType::ONCE,
            startsAt: $runAt,
            maxOccurrences: 1
        );
    }

    /**
     * Create a daily schedule.
     */
    public static function daily(
        int $interval = 1,
        ?\DateTimeImmutable $startsAt = null,
        ?\DateTimeImmutable $endsAt = null
    ): self {
        $cron = sprintf('0 9 */%d * *', $interval); // Every N days at 9 AM
        return new self(
            type: ScheduleType::DAILY,
            cronExpression: $cron,
            startsAt: $startsAt,
            endsAt: $endsAt
        );
    }

    /**
     * Create a weekly schedule.
     */
    public static function weekly(
        int $dayOfWeek = 1, // 1 = Monday
        ?\DateTimeImmutable $startsAt = null,
        ?\DateTimeImmutable $endsAt = null
    ): self {
        $cron = sprintf('0 9 * * %d', $dayOfWeek);
        return new self(
            type: ScheduleType::WEEKLY,
            cronExpression: $cron,
            startsAt: $startsAt,
            endsAt: $endsAt
        );
    }

    /**
     * Create a monthly schedule.
     */
    public static function monthly(
        int $dayOfMonth = 1,
        ?\DateTimeImmutable $startsAt = null,
        ?\DateTimeImmutable $endsAt = null
    ): self {
        $cron = sprintf('0 9 %d * *', $dayOfMonth);
        return new self(
            type: ScheduleType::MONTHLY,
            cronExpression: $cron,
            startsAt: $startsAt,
            endsAt: $endsAt
        );
    }

    /**
     * Create a custom cron schedule.
     */
    public static function cron(
        string $cronExpression,
        ?\DateTimeImmutable $startsAt = null,
        ?\DateTimeImmutable $endsAt = null
    ): self {
        return new self(
            type: ScheduleType::CRON,
            cronExpression: $cronExpression,
            startsAt: $startsAt,
            endsAt: $endsAt
        );
    }

    /**
     * Convert to array for storage.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type->value,
            'cron_expression' => $this->cronExpression,
            'starts_at' => $this->startsAt?->format('Y-m-d H:i:s'),
            'ends_at' => $this->endsAt?->format('Y-m-d H:i:s'),
            'max_occurrences' => $this->maxOccurrences,
        ];
    }

    /**
     * Create from array (for database retrieval).
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            type: ScheduleType::from($data['type']),
            cronExpression: $data['cron_expression'] ?? null,
            startsAt: isset($data['starts_at']) ? new \DateTimeImmutable($data['starts_at']) : null,
            endsAt: isset($data['ends_at']) ? new \DateTimeImmutable($data['ends_at']) : null,
            maxOccurrences: $data['max_occurrences'] ?? null
        );
    }
}
