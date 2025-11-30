<?php

declare(strict_types=1);

namespace Nexus\Receivable\Enums;

/**
 * Payment Allocation Type
 *
 * Defines how a payment should be allocated across multiple open invoices.
 */
enum PaymentAllocationType: string
{
    case FIFO = 'fifo';                        // First In, First Out (oldest invoice first)
    case PROPORTIONAL = 'proportional';        // Proportional across all open invoices
    case MANUAL = 'manual';                    // User-specified allocation
    case SPECIFIC = 'specific';                // Apply to specific invoice(s)

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match ($this) {
            self::FIFO => 'FIFO (Oldest First)',
            self::PROPORTIONAL => 'Proportional',
            self::MANUAL => 'Manual Allocation',
            self::SPECIFIC => 'Specific Invoice',
        };
    }

    /**
     * Get description
     */
    public function description(): string
    {
        return match ($this) {
            self::FIFO => 'Apply payment to oldest outstanding invoice first',
            self::PROPORTIONAL => 'Distribute payment proportionally across all outstanding invoices',
            self::MANUAL => 'User specifies allocation amounts for each invoice',
            self::SPECIFIC => 'Apply entire payment to specific invoice(s)',
        };
    }
}
