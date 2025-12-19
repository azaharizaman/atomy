<?php

declare(strict_types=1);

namespace Nexus\Payment\Enums;

/**
 * Payment direction indicating money flow.
 *
 * INBOUND: Money coming into the organization (receipts, collections)
 * OUTBOUND: Money going out of the organization (disbursements, payouts)
 */
enum PaymentDirection: string
{
    /**
     * Inbound payment - money received (e.g., customer payment, collection).
     */
    case INBOUND = 'inbound';

    /**
     * Outbound payment - money sent (e.g., vendor payment, employee payroll).
     */
    case OUTBOUND = 'outbound';

    /**
     * Check if this is an inbound payment.
     */
    public function isInbound(): bool
    {
        return $this === self::INBOUND;
    }

    /**
     * Check if this is an outbound payment.
     */
    public function isOutbound(): bool
    {
        return $this === self::OUTBOUND;
    }

    /**
     * Get human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::INBOUND => 'Inbound',
            self::OUTBOUND => 'Outbound',
        };
    }

    /**
     * Get accounting sign (positive for inbound, negative for outbound).
     */
    public function accountingSign(): int
    {
        return match ($this) {
            self::INBOUND => 1,
            self::OUTBOUND => -1,
        };
    }

    /**
     * Get the opposite direction.
     */
    public function opposite(): self
    {
        return match ($this) {
            self::INBOUND => self::OUTBOUND,
            self::OUTBOUND => self::INBOUND,
        };
    }
}
