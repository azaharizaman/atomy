<?php

declare(strict_types=1);

namespace Nexus\Payment\Enums;

/**
 * Disbursement (outbound payment) status lifecycle.
 *
 * Represents the current state of a disbursement through its lifecycle.
 * Approval status is tracked separately via isApproved()/isRejected() on the entity.
 *
 * Lifecycle:
 * DRAFT → PENDING_APPROVAL → APPROVED → PROCESSING → COMPLETED
 *    ↓          ↓               ↓           ↓
 * CANCELLED   REJECTED       CANCELLED   FAILED
 */
enum DisbursementStatus: string
{
    /**
     * Disbursement created as draft, not yet submitted for approval.
     */
    case DRAFT = 'draft';

    /**
     * Disbursement submitted and awaiting approval.
     */
    case PENDING_APPROVAL = 'pending_approval';

    /**
     * Disbursement approved, ready for processing.
     */
    case APPROVED = 'approved';

    /**
     * Disbursement rejected by approver.
     */
    case REJECTED = 'rejected';

    /**
     * Disbursement is being processed for payment.
     */
    case PROCESSING = 'processing';

    /**
     * Disbursement completed successfully.
     */
    case COMPLETED = 'completed';

    /**
     * Disbursement processing failed.
     */
    case FAILED = 'failed';

    /**
     * Disbursement was cancelled.
     */
    case CANCELLED = 'cancelled';

    /**
     * Check if this status is a terminal state.
     */
    public function isTerminal(): bool
    {
        return match ($this) {
            self::COMPLETED, self::FAILED, self::CANCELLED, self::REJECTED => true,
            default => false,
        };
    }

    /**
     * Check if this status represents an approved state.
     */
    public function isApproved(): bool
    {
        return match ($this) {
            self::APPROVED, self::PROCESSING, self::COMPLETED => true,
            default => false,
        };
    }

    /**
     * Check if this status allows cancellation.
     */
    public function canCancel(): bool
    {
        return match ($this) {
            self::DRAFT, self::PENDING_APPROVAL, self::APPROVED => true,
            default => false,
        };
    }

    /**
     * Check if this status allows submission for approval.
     */
    public function canSubmitForApproval(): bool
    {
        return $this === self::DRAFT;
    }

    /**
     * Check if transition to another status is valid.
     */
    public function canTransitionTo(self $target): bool
    {
        return match ($this) {
            self::DRAFT => in_array($target, [self::PENDING_APPROVAL, self::CANCELLED], true),
            self::PENDING_APPROVAL => in_array($target, [self::APPROVED, self::REJECTED, self::CANCELLED], true),
            self::APPROVED => in_array($target, [self::PROCESSING, self::CANCELLED], true),
            self::REJECTED => false,
            self::PROCESSING => in_array($target, [self::COMPLETED, self::FAILED], true),
            self::COMPLETED, self::FAILED, self::CANCELLED => false,
        };
    }

    /**
     * Get valid next statuses from this status.
     *
     * @return array<self>
     */
    public function getValidTransitions(): array
    {
        return match ($this) {
            self::DRAFT => [self::PENDING_APPROVAL, self::CANCELLED],
            self::PENDING_APPROVAL => [self::APPROVED, self::REJECTED, self::CANCELLED],
            self::APPROVED => [self::PROCESSING, self::CANCELLED],
            self::REJECTED => [],
            self::PROCESSING => [self::COMPLETED, self::FAILED],
            self::COMPLETED, self::FAILED, self::CANCELLED => [],
        };
    }

    /**
     * Get human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::PENDING_APPROVAL => 'Pending Approval',
            self::APPROVED => 'Approved',
            self::REJECTED => 'Rejected',
            self::PROCESSING => 'Processing',
            self::COMPLETED => 'Completed',
            self::FAILED => 'Failed',
            self::CANCELLED => 'Cancelled',
        };
    }
}
