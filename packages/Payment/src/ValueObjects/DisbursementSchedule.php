<?php

declare(strict_types=1);

namespace Nexus\Payment\ValueObjects;

use Nexus\Payment\Enums\RecurrenceFrequency;
use Nexus\Payment\Enums\ScheduleType;
use Nexus\Payment\Exceptions\InvalidScheduleException;

/**
 * Value object representing a disbursement schedule.
 *
 * Used for PAY-034: Disbursement scheduling support.
 * Supports immediate, scheduled (one-time future), and recurring disbursements.
 */
final class DisbursementSchedule
{
    private function __construct(
        public readonly ScheduleType $scheduleType,
        public readonly ?\DateTimeImmutable $scheduledDate,
        public readonly ?RecurrenceFrequency $recurrenceFrequency,
        public readonly ?\DateTimeImmutable $recurrenceEndDate,
        public readonly ?int $maxOccurrences,
        public readonly int $currentOccurrence = 0,
    ) {}

    /**
     * Create an immediate schedule (process now).
     */
    public static function immediate(): self
    {
        return new self(
            scheduleType: ScheduleType::IMMEDIATE,
            scheduledDate: null,
            recurrenceFrequency: null,
            recurrenceEndDate: null,
            maxOccurrences: null,
            currentOccurrence: 0,
        );
    }

    /**
     * Create a one-time scheduled disbursement.
     *
     * @throws InvalidScheduleException If scheduled date is in the past
     */
    public static function scheduled(\DateTimeImmutable $scheduledDate): self
    {
        $now = new \DateTimeImmutable();
        if ($scheduledDate < $now) {
            throw InvalidScheduleException::scheduledDateInPast($scheduledDate);
        }

        return new self(
            scheduleType: ScheduleType::SCHEDULED,
            scheduledDate: $scheduledDate,
            recurrenceFrequency: null,
            recurrenceEndDate: null,
            maxOccurrences: null,
            currentOccurrence: 0,
        );
    }

    /**
     * Create a recurring schedule.
     *
     * @param \DateTimeImmutable $startDate First occurrence date
     * @param RecurrenceFrequency $frequency How often to recur
     * @param \DateTimeImmutable|null $endDate Optional end date for recurrence
     * @param int|null $maxOccurrences Optional max number of occurrences
     *
     * @throws InvalidScheduleException If configuration is invalid
     */
    public static function recurring(
        \DateTimeImmutable $startDate,
        RecurrenceFrequency $frequency,
        ?\DateTimeImmutable $endDate = null,
        ?int $maxOccurrences = null,
    ): self {
        $now = new \DateTimeImmutable();
        if ($startDate < $now) {
            throw InvalidScheduleException::scheduledDateInPast($startDate);
        }

        if ($endDate !== null && $endDate < $startDate) {
            throw InvalidScheduleException::endDateBeforeStartDate($startDate, $endDate);
        }

        if ($maxOccurrences !== null && $maxOccurrences < 1) {
            throw InvalidScheduleException::invalidMaxOccurrences($maxOccurrences);
        }

        return new self(
            scheduleType: ScheduleType::RECURRING,
            scheduledDate: $startDate,
            recurrenceFrequency: $frequency,
            recurrenceEndDate: $endDate,
            maxOccurrences: $maxOccurrences,
            currentOccurrence: 0,
        );
    }

    /**
     * Check if the schedule is ready for processing now.
     */
    public function isReadyForProcessing(?\DateTimeImmutable $referenceTime = null): bool
    {
        $now = $referenceTime ?? new \DateTimeImmutable();

        return match ($this->scheduleType) {
            ScheduleType::IMMEDIATE => true,
            ScheduleType::SCHEDULED => $this->scheduledDate !== null && $this->scheduledDate <= $now,
            ScheduleType::RECURRING => $this->isRecurrenceReady($now),
        };
    }

    /**
     * Check if recurring schedule has more occurrences.
     */
    public function hasMoreOccurrences(): bool
    {
        if ($this->scheduleType !== ScheduleType::RECURRING) {
            return false;
        }

        if ($this->maxOccurrences !== null && $this->currentOccurrence >= $this->maxOccurrences) {
            return false;
        }

        if ($this->recurrenceEndDate !== null) {
            $nextDate = $this->calculateNextOccurrence();
            if ($nextDate !== null && $nextDate > $this->recurrenceEndDate) {
                return false;
            }
        }

        return true;
    }

    /**
     * Calculate the next occurrence date.
     */
    public function calculateNextOccurrence(): ?\DateTimeImmutable
    {
        if ($this->scheduleType !== ScheduleType::RECURRING || $this->recurrenceFrequency === null) {
            return null;
        }

        if ($this->scheduledDate === null) {
            return null;
        }

        // If no occurrences yet, next is the start date
        if ($this->currentOccurrence === 0) {
            return $this->scheduledDate;
        }

        // Calculate next based on frequency
        $interval = $this->recurrenceFrequency->toDateInterval();
        $nextDate = $this->scheduledDate;

        for ($i = 0; $i < $this->currentOccurrence; $i++) {
            $nextDate = $nextDate->add($interval);
        }

        // Check if we've exceeded end date
        if ($this->recurrenceEndDate !== null && $nextDate > $this->recurrenceEndDate) {
            return null;
        }

        return $nextDate;
    }

    /**
     * Increment the occurrence counter (for recurring schedules).
     */
    public function incrementOccurrence(): self
    {
        if ($this->scheduleType !== ScheduleType::RECURRING) {
            return $this;
        }

        return new self(
            scheduleType: $this->scheduleType,
            scheduledDate: $this->scheduledDate,
            recurrenceFrequency: $this->recurrenceFrequency,
            recurrenceEndDate: $this->recurrenceEndDate,
            maxOccurrences: $this->maxOccurrences,
            currentOccurrence: $this->currentOccurrence + 1,
        );
    }

    /**
     * Get remaining occurrences (null if unlimited).
     */
    public function getRemainingOccurrences(): ?int
    {
        if ($this->scheduleType !== ScheduleType::RECURRING) {
            return null;
        }

        if ($this->maxOccurrences === null) {
            return null;
        }

        return max(0, $this->maxOccurrences - $this->currentOccurrence);
    }

    /**
     * Check if schedule is recurring.
     */
    public function isRecurring(): bool
    {
        return $this->scheduleType === ScheduleType::RECURRING;
    }

    /**
     * Check if schedule is immediate.
     */
    public function isImmediate(): bool
    {
        return $this->scheduleType === ScheduleType::IMMEDIATE;
    }

    /**
     * Convert to array for serialization.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'schedule_type' => $this->scheduleType->value,
            'scheduled_date' => $this->scheduledDate?->format(\DateTimeInterface::ATOM),
            'recurrence_frequency' => $this->recurrenceFrequency?->value,
            'recurrence_end_date' => $this->recurrenceEndDate?->format(\DateTimeInterface::ATOM),
            'max_occurrences' => $this->maxOccurrences,
            'current_occurrence' => $this->currentOccurrence,
        ];
    }

    /**
     * Create from array.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $scheduleType = ScheduleType::from($data['schedule_type']);
        $scheduledDate = isset($data['scheduled_date'])
            ? new \DateTimeImmutable($data['scheduled_date'])
            : null;
        $recurrenceFrequency = isset($data['recurrence_frequency'])
            ? RecurrenceFrequency::from($data['recurrence_frequency'])
            : null;
        $recurrenceEndDate = isset($data['recurrence_end_date'])
            ? new \DateTimeImmutable($data['recurrence_end_date'])
            : null;

        return new self(
            scheduleType: $scheduleType,
            scheduledDate: $scheduledDate,
            recurrenceFrequency: $recurrenceFrequency,
            recurrenceEndDate: $recurrenceEndDate,
            maxOccurrences: $data['max_occurrences'] ?? null,
            currentOccurrence: $data['current_occurrence'] ?? 0,
        );
    }

    /**
     * Check if recurring schedule is ready.
     */
    private function isRecurrenceReady(\DateTimeImmutable $now): bool
    {
        if (!$this->hasMoreOccurrences()) {
            return false;
        }

        $nextDate = $this->calculateNextOccurrence();

        return $nextDate !== null && $nextDate <= $now;
    }
}
