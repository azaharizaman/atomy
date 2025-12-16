<?php

declare(strict_types=1);

namespace Nexus\AmlCompliance\Enums;

/**
 * Suspicious Activity Report (SAR) Status
 * 
 * Workflow states for SAR lifecycle management per FinCEN/FIU requirements.
 */
enum SarStatus: string
{
    /**
     * SAR is being drafted, not yet submitted
     */
    case DRAFT = 'draft';

    /**
     * SAR is pending internal review
     */
    case PENDING_REVIEW = 'pending_review';

    /**
     * SAR has been approved by compliance officer
     */
    case APPROVED = 'approved';

    /**
     * SAR has been submitted to regulatory authority (FIU/FinCEN)
     */
    case SUBMITTED = 'submitted';

    /**
     * SAR is under review by regulatory authority
     */
    case UNDER_INVESTIGATION = 'under_investigation';

    /**
     * Additional information requested by authority
     */
    case INFORMATION_REQUESTED = 'information_requested';

    /**
     * SAR case has been closed
     */
    case CLOSED = 'closed';

    /**
     * SAR was rejected during internal review
     */
    case REJECTED = 'rejected';

    /**
     * SAR was cancelled before submission
     */
    case CANCELLED = 'cancelled';

    /**
     * Check if transition to target status is allowed
     */
    public function canTransitionTo(self $targetStatus): bool
    {
        $allowedTransitions = $this->getAllowedTransitions();

        return in_array($targetStatus, $allowedTransitions, true);
    }

    /**
     * Get all allowed status transitions from current status
     * 
     * @return array<self>
     */
    public function getAllowedTransitions(): array
    {
        return match ($this) {
            self::DRAFT => [
                self::PENDING_REVIEW,
                self::CANCELLED,
            ],
            self::PENDING_REVIEW => [
                self::APPROVED,
                self::REJECTED,
                self::DRAFT, // Return for corrections
            ],
            self::APPROVED => [
                self::SUBMITTED,
                self::DRAFT, // Rare, but possible for corrections
            ],
            self::SUBMITTED => [
                self::UNDER_INVESTIGATION,
                self::INFORMATION_REQUESTED,
                self::CLOSED,
            ],
            self::UNDER_INVESTIGATION => [
                self::INFORMATION_REQUESTED,
                self::CLOSED,
            ],
            self::INFORMATION_REQUESTED => [
                self::SUBMITTED, // Re-submit with additional info
                self::UNDER_INVESTIGATION,
                self::CLOSED,
            ],
            self::CLOSED => [], // Terminal state
            self::REJECTED => [
                self::DRAFT, // Re-open for corrections
                self::CANCELLED,
            ],
            self::CANCELLED => [], // Terminal state
        };
    }

    /**
     * Check if status requires approval before proceeding
     */
    public function requiresApproval(): bool
    {
        return match ($this) {
            self::DRAFT => false,
            self::PENDING_REVIEW => true,
            self::APPROVED => false,
            self::SUBMITTED => false,
            self::UNDER_INVESTIGATION => false,
            self::INFORMATION_REQUESTED => true, // Response needs approval
            self::CLOSED => false,
            self::REJECTED => false,
            self::CANCELLED => false,
        };
    }

    /**
     * Check if this is a terminal/final state
     */
    public function isFinal(): bool
    {
        return match ($this) {
            self::CLOSED => true,
            self::CANCELLED => true,
            default => false,
        };
    }

    /**
     * Check if SAR is editable in this status
     */
    public function isEditable(): bool
    {
        return match ($this) {
            self::DRAFT => true,
            self::REJECTED => true,
            default => false,
        };
    }

    /**
     * Check if SAR has been submitted to authority
     */
    public function isSubmitted(): bool
    {
        return match ($this) {
            self::SUBMITTED => true,
            self::UNDER_INVESTIGATION => true,
            self::INFORMATION_REQUESTED => true,
            self::CLOSED => true,
            default => false,
        };
    }

    /**
     * Check if SAR is in an active workflow state
     */
    public function isActive(): bool
    {
        return !$this->isFinal() && $this !== self::REJECTED;
    }

    /**
     * Check if SAR is pending action
     */
    public function isPending(): bool
    {
        return match ($this) {
            self::PENDING_REVIEW => true,
            self::INFORMATION_REQUESTED => true,
            default => false,
        };
    }

    /**
     * Get the SLA deadline in hours for this status
     */
    public function getSlaHours(): int
    {
        return match ($this) {
            self::DRAFT => 168,              // 7 days to complete draft
            self::PENDING_REVIEW => 48,       // 2 days for review
            self::APPROVED => 24,             // 1 day to submit
            self::INFORMATION_REQUESTED => 72, // 3 days to respond
            default => 0, // No SLA for other statuses
        };
    }

    /**
     * Get the regulatory filing deadline in days (from suspicious activity)
     * Based on FinCEN requirements: 30 days, extendable to 60
     */
    public function getFilingDeadlineDays(): int
    {
        return 30; // Standard FinCEN filing deadline
    }

    /**
     * Get human-readable description
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::DRAFT => 'SAR is being drafted',
            self::PENDING_REVIEW => 'Awaiting compliance officer review',
            self::APPROVED => 'Approved for submission',
            self::SUBMITTED => 'Submitted to regulatory authority',
            self::UNDER_INVESTIGATION => 'Under investigation by authority',
            self::INFORMATION_REQUESTED => 'Additional information requested',
            self::CLOSED => 'SAR case closed',
            self::REJECTED => 'Rejected during internal review',
            self::CANCELLED => 'SAR cancelled',
        };
    }

    /**
     * Get the workflow phase
     */
    public function getPhase(): string
    {
        return match ($this) {
            self::DRAFT => 'preparation',
            self::PENDING_REVIEW => 'review',
            self::APPROVED => 'review',
            self::REJECTED => 'review',
            self::SUBMITTED => 'regulatory',
            self::UNDER_INVESTIGATION => 'regulatory',
            self::INFORMATION_REQUESTED => 'regulatory',
            self::CLOSED => 'completed',
            self::CANCELLED => 'completed',
        };
    }

    /**
     * Get priority level for dashboard display
     */
    public function getPriority(): int
    {
        return match ($this) {
            self::INFORMATION_REQUESTED => 1, // Highest priority
            self::PENDING_REVIEW => 2,
            self::APPROVED => 3,
            self::DRAFT => 4,
            self::SUBMITTED => 5,
            self::UNDER_INVESTIGATION => 6,
            self::CLOSED => 7,
            self::REJECTED => 8,
            self::CANCELLED => 9,
        };
    }

    /**
     * Get icon name for UI display
     */
    public function getIcon(): string
    {
        return match ($this) {
            self::DRAFT => 'document-text',
            self::PENDING_REVIEW => 'clock',
            self::APPROVED => 'check-circle',
            self::SUBMITTED => 'paper-airplane',
            self::UNDER_INVESTIGATION => 'magnifying-glass',
            self::INFORMATION_REQUESTED => 'exclamation-circle',
            self::CLOSED => 'archive-box',
            self::REJECTED => 'x-circle',
            self::CANCELLED => 'trash',
        };
    }

    /**
     * Get color code for UI display
     */
    public function getColor(): string
    {
        return match ($this) {
            self::DRAFT => 'gray',
            self::PENDING_REVIEW => 'yellow',
            self::APPROVED => 'green',
            self::SUBMITTED => 'blue',
            self::UNDER_INVESTIGATION => 'purple',
            self::INFORMATION_REQUESTED => 'orange',
            self::CLOSED => 'gray',
            self::REJECTED => 'red',
            self::CANCELLED => 'gray',
        };
    }

    /**
     * Get all statuses in workflow order
     * 
     * @return array<self>
     */
    public static function workflowOrder(): array
    {
        return [
            self::DRAFT,
            self::PENDING_REVIEW,
            self::APPROVED,
            self::SUBMITTED,
            self::UNDER_INVESTIGATION,
            self::INFORMATION_REQUESTED,
            self::CLOSED,
        ];
    }

    /**
     * Get all terminal statuses
     * 
     * @return array<self>
     */
    public static function terminalStatuses(): array
    {
        return [self::CLOSED, self::CANCELLED];
    }

    /**
     * Get all statuses requiring action
     * 
     * @return array<self>
     */
    public static function actionRequired(): array
    {
        return [self::DRAFT, self::PENDING_REVIEW, self::APPROVED, self::INFORMATION_REQUESTED];
    }
}
