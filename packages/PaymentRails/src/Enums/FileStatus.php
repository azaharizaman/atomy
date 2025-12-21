<?php

declare(strict_types=1);

namespace Nexus\PaymentRails\Enums;

/**
 * Status of a generated payment file (ACH, Wire, etc.).
 *
 * Tracks the file from generation through transmission and acknowledgment.
 */
enum FileStatus: string
{
    /**
     * File has been generated but not yet transmitted.
     */
    case GENERATED = 'generated';

    /**
     * File is pending review/approval before transmission.
     */
    case PENDING_APPROVAL = 'pending_approval';

    /**
     * File has been approved for transmission.
     */
    case APPROVED = 'approved';

    /**
     * File transmission is in progress.
     */
    case TRANSMITTING = 'transmitting';

    /**
     * File has been successfully transmitted to the bank.
     */
    case TRANSMITTED = 'transmitted';

    /**
     * File has been acknowledged by the receiving system.
     */
    case ACKNOWLEDGED = 'acknowledged';

    /**
     * File was accepted by the bank/processor.
     */
    case ACCEPTED = 'accepted';

    /**
     * File was partially accepted (some entries rejected).
     */
    case PARTIALLY_ACCEPTED = 'partially_accepted';

    /**
     * File was rejected by the bank/processor.
     */
    case REJECTED = 'rejected';

    /**
     * File transmission failed.
     */
    case FAILED = 'failed';

    /**
     * File has been cancelled.
     */
    case CANCELLED = 'cancelled';

    /**
     * Get a human-readable label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::GENERATED => 'Generated',
            self::PENDING_APPROVAL => 'Pending Approval',
            self::APPROVED => 'Approved',
            self::TRANSMITTING => 'Transmitting',
            self::TRANSMITTED => 'Transmitted',
            self::ACKNOWLEDGED => 'Acknowledged',
            self::ACCEPTED => 'Accepted',
            self::PARTIALLY_ACCEPTED => 'Partially Accepted',
            self::REJECTED => 'Rejected',
            self::FAILED => 'Failed',
            self::CANCELLED => 'Cancelled',
        };
    }

    /**
     * Check if this is a success status.
     */
    public function isSuccess(): bool
    {
        return match ($this) {
            self::TRANSMITTED,
            self::ACKNOWLEDGED,
            self::ACCEPTED => true,
            default => false,
        };
    }

    /**
     * Check if this is a failure status.
     */
    public function isFailure(): bool
    {
        return match ($this) {
            self::REJECTED,
            self::FAILED,
            self::CANCELLED => true,
            default => false,
        };
    }

    /**
     * Check if this is a final status.
     */
    public function isFinal(): bool
    {
        return match ($this) {
            self::ACCEPTED,
            self::PARTIALLY_ACCEPTED,
            self::REJECTED,
            self::CANCELLED => true,
            default => false,
        };
    }

    /**
     * Check if the file can be retransmitted from this status.
     */
    public function canRetransmit(): bool
    {
        return match ($this) {
            self::FAILED,
            self::REJECTED => true,
            default => false,
        };
    }

    /**
     * Check if the file can be cancelled from this status.
     */
    public function canCancel(): bool
    {
        return match ($this) {
            self::GENERATED,
            self::PENDING_APPROVAL,
            self::APPROVED => true,
            default => false,
        };
    }

    /**
     * Check if the file is in a processing state.
     */
    public function isProcessing(): bool
    {
        return match ($this) {
            self::TRANSMITTING,
            self::TRANSMITTED,
            self::ACKNOWLEDGED => true,
            default => false,
        };
    }
}
