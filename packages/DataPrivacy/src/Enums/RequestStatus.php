<?php

declare(strict_types=1);

namespace Nexus\DataPrivacy\Enums;

/**
 * Status of a data subject request throughout its lifecycle.
 */
enum RequestStatus: string
{
    /**
     * Request received but not yet being processed.
     */
    case PENDING = 'pending';

    /**
     * Identity verification in progress.
     */
    case VERIFYING_IDENTITY = 'verifying_identity';

    /**
     * Request is being actively processed.
     */
    case IN_PROGRESS = 'in_progress';

    /**
     * Awaiting additional information from data subject.
     */
    case AWAITING_INFO = 'awaiting_info';

    /**
     * Under review (e.g., checking legal exemptions).
     */
    case UNDER_REVIEW = 'under_review';

    /**
     * Request has been completed successfully.
     */
    case COMPLETED = 'completed';

    /**
     * Request was rejected (with valid reason).
     */
    case REJECTED = 'rejected';

    /**
     * Request was partially fulfilled.
     */
    case PARTIALLY_COMPLETED = 'partially_completed';

    /**
     * Request was cancelled by the data subject.
     */
    case CANCELLED = 'cancelled';

    /**
     * Request deadline has passed without completion.
     */
    case EXPIRED = 'expired';

    /**
     * Check if this status is a terminal state.
     */
    public function isTerminal(): bool
    {
        return match ($this) {
            self::COMPLETED,
            self::REJECTED,
            self::PARTIALLY_COMPLETED,
            self::CANCELLED,
            self::EXPIRED => true,
            default => false,
        };
    }

    /**
     * Check if this status indicates active processing.
     */
    public function isActive(): bool
    {
        return match ($this) {
            self::PENDING,
            self::VERIFYING_IDENTITY,
            self::IN_PROGRESS,
            self::AWAITING_INFO,
            self::UNDER_REVIEW => true,
            default => false,
        };
    }

    /**
     * Get allowed transitions from this status.
     *
     * @return array<RequestStatus>
     */
    public function getAllowedTransitions(): array
    {
        return match ($this) {
            self::PENDING => [
                self::VERIFYING_IDENTITY,
                self::IN_PROGRESS,
                self::REJECTED,
                self::CANCELLED,
            ],
            self::VERIFYING_IDENTITY => [
                self::IN_PROGRESS,
                self::REJECTED,
                self::CANCELLED,
            ],
            self::IN_PROGRESS => [
                self::AWAITING_INFO,
                self::UNDER_REVIEW,
                self::COMPLETED,
                self::PARTIALLY_COMPLETED,
                self::REJECTED,
                self::CANCELLED,
            ],
            self::AWAITING_INFO => [
                self::IN_PROGRESS,
                self::EXPIRED,
                self::CANCELLED,
            ],
            self::UNDER_REVIEW => [
                self::IN_PROGRESS,
                self::COMPLETED,
                self::PARTIALLY_COMPLETED,
                self::REJECTED,
            ],
            default => [], // Terminal states have no transitions
        };
    }

    /**
     * Check if transition to given status is allowed.
     */
    public function canTransitionTo(self $newStatus): bool
    {
        return in_array($newStatus, $this->getAllowedTransitions(), true);
    }

    /**
     * Get human-readable label.
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::VERIFYING_IDENTITY => 'Verifying Identity',
            self::IN_PROGRESS => 'In Progress',
            self::AWAITING_INFO => 'Awaiting Information',
            self::UNDER_REVIEW => 'Under Review',
            self::COMPLETED => 'Completed',
            self::REJECTED => 'Rejected',
            self::PARTIALLY_COMPLETED => 'Partially Completed',
            self::CANCELLED => 'Cancelled',
            self::EXPIRED => 'Expired',
        };
    }
}
