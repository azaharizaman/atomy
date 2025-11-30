<?php

declare(strict_types=1);

namespace Nexus\Receivable\Enums;

/**
 * Payment Receipt Status Lifecycle
 *
 * Tracks the state of a customer payment from receipt through application to invoices.
 */
enum PaymentReceiptStatus: string
{
    case PENDING = 'pending';                  // Awaiting bank clearance
    case CLEARED = 'cleared';                  // Bank cleared
    case APPLIED = 'applied';                  // Applied to invoices
    case RECONCILED = 'reconciled';            // Bank reconciliation complete
    case BOUNCED = 'bounced';                  // Cheque/payment bounced
    case VOIDED = 'voided';                    // Payment reversed

    /**
     * Can this receipt be applied to invoices?
     */
    public function canBeApplied(): bool
    {
        return in_array($this, [self::CLEARED, self::APPLIED], true);
    }

    /**
     * Is this receipt in a final (immutable) state?
     */
    public function isFinal(): bool
    {
        return in_array($this, [self::RECONCILED, self::BOUNCED, self::VOIDED], true);
    }

    /**
     * Can this receipt be voided?
     */
    public function canBeVoided(): bool
    {
        return in_array($this, [self::PENDING, self::CLEARED, self::APPLIED], true);
    }

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::CLEARED => 'Cleared',
            self::APPLIED => 'Applied',
            self::RECONCILED => 'Reconciled',
            self::BOUNCED => 'Bounced',
            self::VOIDED => 'Voided',
        };
    }
}
