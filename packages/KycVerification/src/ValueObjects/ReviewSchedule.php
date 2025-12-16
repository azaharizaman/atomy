<?php

declare(strict_types=1);

namespace Nexus\KycVerification\ValueObjects;

use Nexus\KycVerification\Enums\ReviewTrigger;

/**
 * Represents a scheduled KYC review.
 * 
 * @immutable
 */
final readonly class ReviewSchedule
{
    /**
     * @param array<string> $requiredActions
     * @param array<string> $assignedReviewers
     */
    public function __construct(
        public string $partyId,
        public ReviewTrigger $trigger,
        public \DateTimeImmutable $scheduledDate,
        public \DateTimeImmutable $dueDate,
        public int $priority,
        public string $status,
        public array $requiredActions = [],
        public array $assignedReviewers = [],
        public ?string $notes = null,
        public ?\DateTimeImmutable $startedAt = null,
        public ?\DateTimeImmutable $completedAt = null,
        public ?string $completedBy = null,
        public ?string $outcome = null,
    ) {}

    public const string STATUS_SCHEDULED = 'scheduled';
    public const string STATUS_IN_PROGRESS = 'in_progress';
    public const string STATUS_COMPLETED = 'completed';
    public const string STATUS_CANCELLED = 'cancelled';
    public const string STATUS_OVERDUE = 'overdue';

    /**
     * Check if review is overdue
     */
    public function isOverdue(): bool
    {
        if ($this->status === self::STATUS_COMPLETED || $this->status === self::STATUS_CANCELLED) {
            return false;
        }

        return $this->dueDate < new \DateTimeImmutable();
    }

    /**
     * Check if review is due soon (within days)
     */
    public function isDueSoon(int $withinDays = 7): bool
    {
        if ($this->status === self::STATUS_COMPLETED || $this->status === self::STATUS_CANCELLED) {
            return false;
        }

        $threshold = (new \DateTimeImmutable())->modify("+{$withinDays} days");
        return $this->dueDate <= $threshold && !$this->isOverdue();
    }

    /**
     * Get days until due
     */
    public function daysUntilDue(): int
    {
        $now = new \DateTimeImmutable();
        $diff = $now->diff($this->dueDate);

        return $diff->invert === 1 ? -$diff->days : $diff->days;
    }

    /**
     * Check if review is in progress
     */
    public function isInProgress(): bool
    {
        return $this->status === self::STATUS_IN_PROGRESS;
    }

    /**
     * Check if review is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if review is urgent
     */
    public function isUrgent(): bool
    {
        return $this->trigger->isUrgent() || $this->isOverdue();
    }

    /**
     * Check if can be automated
     */
    public function canBeAutomated(): bool
    {
        return $this->trigger->canAutomate();
    }

    /**
     * Get SLA hours for this review
     */
    public function getSlaHours(): int
    {
        return $this->trigger->slaHours();
    }

    /**
     * Calculate SLA status
     * 
     * @return array{within_sla: bool, hours_remaining: int, percentage_used: float}
     */
    public function getSlaStatus(): array
    {
        $slaHours = $this->getSlaHours();
        $scheduledTime = $this->scheduledDate->getTimestamp();
        $dueTime = $this->scheduledDate->modify("+{$slaHours} hours")->getTimestamp();
        $nowTime = time();

        $totalSeconds = $dueTime - $scheduledTime;
        $elapsedSeconds = $nowTime - $scheduledTime;
        $remainingSeconds = max(0, $dueTime - $nowTime);

        return [
            'within_sla' => $nowTime <= $dueTime,
            'hours_remaining' => (int) ($remainingSeconds / 3600),
            'percentage_used' => min(100, ($elapsedSeconds / $totalSeconds) * 100),
        ];
    }

    /**
     * Create a scheduled review
     */
    public static function schedule(
        string $partyId,
        ReviewTrigger $trigger,
        ?\DateTimeImmutable $scheduledDate = null,
    ): self {
        $scheduled = $scheduledDate ?? new \DateTimeImmutable();
        $slaHours = $trigger->slaHours();
        $dueDate = $scheduled->modify("+{$slaHours} hours");

        return new self(
            partyId: $partyId,
            trigger: $trigger,
            scheduledDate: $scheduled,
            dueDate: $dueDate,
            priority: $trigger->priority(),
            status: self::STATUS_SCHEDULED,
        );
    }

    /**
     * Create a started review
     */
    public function start(?string $reviewer = null): self
    {
        return new self(
            partyId: $this->partyId,
            trigger: $this->trigger,
            scheduledDate: $this->scheduledDate,
            dueDate: $this->dueDate,
            priority: $this->priority,
            status: self::STATUS_IN_PROGRESS,
            requiredActions: $this->requiredActions,
            assignedReviewers: $reviewer !== null 
                ? array_merge($this->assignedReviewers, [$reviewer])
                : $this->assignedReviewers,
            notes: $this->notes,
            startedAt: new \DateTimeImmutable(),
        );
    }

    /**
     * Mark review as completed
     */
    public function complete(string $completedBy, string $outcome): self
    {
        return new self(
            partyId: $this->partyId,
            trigger: $this->trigger,
            scheduledDate: $this->scheduledDate,
            dueDate: $this->dueDate,
            priority: $this->priority,
            status: self::STATUS_COMPLETED,
            requiredActions: $this->requiredActions,
            assignedReviewers: $this->assignedReviewers,
            notes: $this->notes,
            startedAt: $this->startedAt,
            completedAt: new \DateTimeImmutable(),
            completedBy: $completedBy,
            outcome: $outcome,
        );
    }

    /**
     * Convert to array
     * 
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'party_id' => $this->partyId,
            'trigger' => $this->trigger->value,
            'trigger_label' => $this->trigger->label(),
            'scheduled_date' => $this->scheduledDate->format('c'),
            'due_date' => $this->dueDate->format('c'),
            'priority' => $this->priority,
            'status' => $this->status,
            'required_actions' => $this->requiredActions,
            'assigned_reviewers' => $this->assignedReviewers,
            'notes' => $this->notes,
            'started_at' => $this->startedAt?->format('c'),
            'completed_at' => $this->completedAt?->format('c'),
            'completed_by' => $this->completedBy,
            'outcome' => $this->outcome,
            'is_overdue' => $this->isOverdue(),
            'is_urgent' => $this->isUrgent(),
            'days_until_due' => $this->daysUntilDue(),
            'sla_status' => $this->getSlaStatus(),
        ];
    }
}
