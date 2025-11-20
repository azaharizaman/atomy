<?php

declare(strict_types=1);

namespace Nexus\Sales\Enums;

/**
 * Sales order status enum.
 * 
 * Tracks the lifecycle of a sales order from draft to payment completion.
 */
enum SalesOrderStatus: string
{
    case DRAFT = 'draft';
    case CONFIRMED = 'confirmed';
    case PARTIALLY_SHIPPED = 'partially_shipped';
    case FULLY_SHIPPED = 'fully_shipped';
    case INVOICED = 'invoiced';
    case PAID = 'paid';
    case CANCELLED = 'cancelled';

    /**
     * Check if order can be confirmed.
     */
    public function canBeConfirmed(): bool
    {
        return $this === self::DRAFT;
    }

    /**
     * Check if order can be shipped.
     */
    public function canBeShipped(): bool
    {
        return in_array($this, [self::CONFIRMED, self::PARTIALLY_SHIPPED], true);
    }

    /**
     * Check if order can be invoiced.
     */
    public function canBeInvoiced(): bool
    {
        return in_array($this, [self::CONFIRMED, self::PARTIALLY_SHIPPED, self::FULLY_SHIPPED], true);
    }

    /**
     * Check if order is in a final state.
     */
    public function isFinal(): bool
    {
        return in_array($this, [self::PAID, self::CANCELLED], true);
    }

    /**
     * Check if status transition is valid.
     */
    public function canTransitionTo(SalesOrderStatus $newStatus): bool
    {
        return match ($this) {
            self::DRAFT => in_array($newStatus, [self::CONFIRMED, self::CANCELLED], true),
            self::CONFIRMED => in_array($newStatus, [self::PARTIALLY_SHIPPED, self::FULLY_SHIPPED, self::INVOICED, self::CANCELLED], true),
            self::PARTIALLY_SHIPPED => in_array($newStatus, [self::FULLY_SHIPPED, self::INVOICED], true),
            self::FULLY_SHIPPED => $newStatus === self::INVOICED,
            self::INVOICED => $newStatus === self::PAID,
            self::PAID, self::CANCELLED => false,
        };
    }
}
