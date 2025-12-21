<?php

declare(strict_types=1);

namespace Nexus\PaymentRails\Enums;

/**
 * Status of a check in its lifecycle.
 *
 * Tracks the check from issuance through clearing or cancellation.
 */
enum CheckStatus: string
{
    /**
     * Check has been created in system but not yet printed.
     */
    case PENDING = 'pending';

    /**
     * Check has been printed and is ready for mailing.
     */
    case PRINTED = 'printed';

    /**
     * Check has been mailed to the payee.
     */
    case MAILED = 'mailed';

    /**
     * Check has been presented and cleared.
     */
    case CLEARED = 'cleared';

    /**
     * Check has been voided before clearing.
     */
    case VOIDED = 'voided';

    /**
     * Stop payment has been placed on the check.
     */
    case STOP_PAYMENT = 'stop_payment';

    /**
     * Check has expired (stale-dated).
     * Typically checks are valid for 180 days.
     */
    case EXPIRED = 'expired';

    /**
     * Check was returned unpaid.
     */
    case RETURNED = 'returned';

    /**
     * Check was reissued after void or stop.
     */
    case REISSUED = 'reissued';

    /**
     * Get a human-readable label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::PRINTED => 'Printed',
            self::MAILED => 'Mailed',
            self::CLEARED => 'Cleared',
            self::VOIDED => 'Voided',
            self::STOP_PAYMENT => 'Stop Payment',
            self::EXPIRED => 'Expired',
            self::RETURNED => 'Returned',
            self::REISSUED => 'Reissued',
        };
    }

    /**
     * Check if this status represents an active (outstanding) check.
     */
    public function isOutstanding(): bool
    {
        return match ($this) {
            self::PENDING,
            self::PRINTED,
            self::MAILED => true,
            self::CLEARED,
            self::VOIDED,
            self::STOP_PAYMENT,
            self::EXPIRED,
            self::RETURNED,
            self::REISSUED => false,
        };
    }

    /**
     * Check if this status represents a final state.
     */
    public function isFinal(): bool
    {
        return match ($this) {
            self::CLEARED,
            self::VOIDED,
            self::EXPIRED => true,
            self::PENDING,
            self::PRINTED,
            self::MAILED,
            self::STOP_PAYMENT,
            self::RETURNED,
            self::REISSUED => false,
        };
    }

    /**
     * Check if the check can be voided from this status.
     */
    public function canVoid(): bool
    {
        return match ($this) {
            self::PENDING,
            self::PRINTED,
            self::MAILED => true,
            self::CLEARED,
            self::VOIDED,
            self::STOP_PAYMENT,
            self::EXPIRED,
            self::RETURNED,
            self::REISSUED => false,
        };
    }

    /**
     * Check if stop payment can be issued from this status.
     */
    public function canStopPayment(): bool
    {
        return match ($this) {
            self::PRINTED,
            self::MAILED => true,
            self::PENDING,
            self::CLEARED,
            self::VOIDED,
            self::STOP_PAYMENT,
            self::EXPIRED,
            self::RETURNED,
            self::REISSUED => false,
        };
    }

    /**
     * Check if the check can be reissued from this status.
     */
    public function canReissue(): bool
    {
        return match ($this) {
            self::VOIDED,
            self::STOP_PAYMENT,
            self::EXPIRED,
            self::RETURNED => true,
            self::PENDING,
            self::PRINTED,
            self::MAILED,
            self::CLEARED,
            self::REISSUED => false,
        };
    }

    /**
     * Get valid transitions from this status.
     *
     * @return array<self>
     */
    public function validTransitions(): array
    {
        return match ($this) {
            self::PENDING => [self::PRINTED, self::VOIDED],
            self::PRINTED => [self::MAILED, self::VOIDED, self::STOP_PAYMENT],
            self::MAILED => [self::CLEARED, self::VOIDED, self::STOP_PAYMENT, self::RETURNED, self::EXPIRED],
            self::STOP_PAYMENT => [self::REISSUED],
            self::RETURNED => [self::REISSUED],
            self::VOIDED => [self::REISSUED],
            self::EXPIRED => [self::REISSUED],
            self::CLEARED,
            self::REISSUED => [],
        };
    }
}
