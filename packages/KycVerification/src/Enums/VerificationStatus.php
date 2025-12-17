<?php

declare(strict_types=1);

namespace Nexus\KycVerification\Enums;

/**
 * Verification status for KYC verification processes.
 * 
 * Represents the lifecycle states of a verification request.
 */
enum VerificationStatus: string
{
    /**
     * Verification has been initiated but not started
     */
    case PENDING = 'pending';

    /**
     * Verification is currently in progress
     */
    case IN_PROGRESS = 'in_progress';

    /**
     * Additional documents or information required
     */
    case DOCUMENTS_REQUIRED = 'documents_required';

    /**
     * Under manual review by compliance officer
     */
    case UNDER_REVIEW = 'under_review';

    /**
     * Verification completed successfully
     */
    case VERIFIED = 'verified';

    /**
     * Verification completed but with conditions
     */
    case CONDITIONALLY_VERIFIED = 'conditionally_verified';

    /**
     * Verification failed - party rejected
     */
    case REJECTED = 'rejected';

    /**
     * Verification expired - needs renewal
     */
    case EXPIRED = 'expired';

    /**
     * Verification suspended pending investigation
     */
    case SUSPENDED = 'suspended';

    /**
     * Check if this status represents an active verification
     */
    public function isActive(): bool
    {
        return in_array($this, [
            self::VERIFIED,
            self::CONDITIONALLY_VERIFIED,
        ], true);
    }

    /**
     * Check if this status allows transactions
     */
    public function allowsTransactions(): bool
    {
        return in_array($this, [
            self::VERIFIED,
            self::CONDITIONALLY_VERIFIED,
        ], true);
    }

    /**
     * Check if this status requires action
     */
    public function requiresAction(): bool
    {
        return in_array($this, [
            self::PENDING,
            self::DOCUMENTS_REQUIRED,
            self::UNDER_REVIEW,
            self::EXPIRED,
        ], true);
    }

    /**
     * Check if this status is terminal
     */
    public function isTerminal(): bool
    {
        return in_array($this, [
            self::REJECTED,
            self::EXPIRED,
        ], true);
    }

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::IN_PROGRESS => 'In Progress',
            self::DOCUMENTS_REQUIRED => 'Documents Required',
            self::UNDER_REVIEW => 'Under Review',
            self::VERIFIED => 'Verified',
            self::CONDITIONALLY_VERIFIED => 'Conditionally Verified',
            self::REJECTED => 'Rejected',
            self::EXPIRED => 'Expired',
            self::SUSPENDED => 'Suspended',
        };
    }

    /**
     * Get allowed transitions from this status
     * 
     * @return array<VerificationStatus>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::PENDING => [self::IN_PROGRESS, self::DOCUMENTS_REQUIRED, self::REJECTED],
            self::IN_PROGRESS => [self::UNDER_REVIEW, self::DOCUMENTS_REQUIRED, self::VERIFIED, self::REJECTED],
            self::DOCUMENTS_REQUIRED => [self::IN_PROGRESS, self::REJECTED, self::EXPIRED],
            self::UNDER_REVIEW => [self::VERIFIED, self::CONDITIONALLY_VERIFIED, self::REJECTED, self::DOCUMENTS_REQUIRED],
            self::VERIFIED => [self::EXPIRED, self::SUSPENDED],
            self::CONDITIONALLY_VERIFIED => [self::VERIFIED, self::EXPIRED, self::SUSPENDED, self::REJECTED],
            self::REJECTED => [], // Terminal state
            self::EXPIRED => [self::PENDING], // Can restart verification
            self::SUSPENDED => [self::UNDER_REVIEW, self::REJECTED],
        };
    }

    /**
     * Check if transition to given status is allowed
     */
    public function canTransitionTo(VerificationStatus $newStatus): bool
    {
        return in_array($newStatus, $this->allowedTransitions(), true);
    }
}
