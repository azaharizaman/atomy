<?php

declare(strict_types=1);

namespace Nexus\PaymentRails\Enums;

/**
 * Status of an individual payment entry within a batch.
 *
 * Tracks each entry from creation through settlement or return.
 */
enum EntryStatus: string
{
    /**
     * Entry has been created and is pending inclusion in a batch.
     */
    case PENDING = 'pending';

    /**
     * Entry has been included in a batch.
     */
    case BATCHED = 'batched';

    /**
     * Entry has been transmitted as part of a file.
     */
    case TRANSMITTED = 'transmitted';

    /**
     * Entry is being processed by the receiving bank.
     */
    case PROCESSING = 'processing';

    /**
     * Entry has been settled.
     */
    case SETTLED = 'settled';

    /**
     * Entry was returned by the receiving bank.
     */
    case RETURNED = 'returned';

    /**
     * Entry was rejected before processing.
     */
    case REJECTED = 'rejected';

    /**
     * Entry has a Notification of Change (NOC).
     * Account information needs to be updated.
     */
    case NOC_RECEIVED = 'noc_received';

    /**
     * Entry has been cancelled before transmission.
     */
    case CANCELLED = 'cancelled';

    /**
     * Entry is on hold pending review.
     */
    case ON_HOLD = 'on_hold';

    /**
     * Get a human-readable label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::BATCHED => 'Batched',
            self::TRANSMITTED => 'Transmitted',
            self::PROCESSING => 'Processing',
            self::SETTLED => 'Settled',
            self::RETURNED => 'Returned',
            self::REJECTED => 'Rejected',
            self::NOC_RECEIVED => 'NOC Received',
            self::CANCELLED => 'Cancelled',
            self::ON_HOLD => 'On Hold',
        };
    }

    /**
     * Check if this is a success status.
     */
    public function isSuccess(): bool
    {
        return $this === self::SETTLED;
    }

    /**
     * Check if this is a failure status.
     */
    public function isFailure(): bool
    {
        return match ($this) {
            self::RETURNED,
            self::REJECTED,
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
            self::SETTLED,
            self::RETURNED,
            self::REJECTED,
            self::CANCELLED => true,
            default => false,
        };
    }

    /**
     * Check if this status requires action.
     */
    public function requiresAction(): bool
    {
        return match ($this) {
            self::RETURNED,
            self::NOC_RECEIVED,
            self::ON_HOLD => true,
            default => false,
        };
    }

    /**
     * Check if the entry can be cancelled from this status.
     */
    public function canCancel(): bool
    {
        return match ($this) {
            self::PENDING,
            self::ON_HOLD => true,
            default => false,
        };
    }

    /**
     * Check if the entry can be retried from this status.
     */
    public function canRetry(): bool
    {
        return match ($this) {
            self::RETURNED,
            self::REJECTED => true,
            default => false,
        };
    }
}
