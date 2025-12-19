<?php

declare(strict_types=1);

namespace Nexus\Payment\Enums;

/**
 * Payment allocation method for distributing payment across documents.
 *
 * When a payment is received or made, it may need to be allocated across
 * multiple invoices or bills. This enum defines the available allocation strategies.
 */
enum AllocationMethod: string
{
    /**
     * First-In-First-Out: Allocate to oldest documents first.
     */
    case FIFO = 'fifo';

    /**
     * Last-In-First-Out: Allocate to newest documents first.
     */
    case LIFO = 'lifo';

    /**
     * Proportional: Allocate proportionally across all documents.
     */
    case PROPORTIONAL = 'proportional';

    /**
     * Manual: User specifies exact allocation amounts.
     */
    case MANUAL = 'manual';

    /**
     * Oldest-First: Alias for FIFO, allocate by due date.
     */
    case OLDEST_FIRST = 'oldest_first';

    /**
     * Smallest-First: Allocate to smallest balances first.
     */
    case SMALLEST_FIRST = 'smallest_first';

    /**
     * Largest-First: Allocate to largest balances first.
     */
    case LARGEST_FIRST = 'largest_first';

    /**
     * Check if this method requires user input.
     */
    public function requiresUserInput(): bool
    {
        return $this === self::MANUAL;
    }

    /**
     * Check if this method is automatic.
     */
    public function isAutomatic(): bool
    {
        return $this !== self::MANUAL;
    }

    /**
     * Get human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::FIFO => 'First-In-First-Out',
            self::LIFO => 'Last-In-First-Out',
            self::PROPORTIONAL => 'Proportional',
            self::MANUAL => 'Manual Allocation',
            self::OLDEST_FIRST => 'Oldest First',
            self::SMALLEST_FIRST => 'Smallest First',
            self::LARGEST_FIRST => 'Largest First',
        };
    }

    /**
     * Get short description.
     */
    public function description(): string
    {
        return match ($this) {
            self::FIFO => 'Allocate payment to oldest invoices/bills first based on document date',
            self::LIFO => 'Allocate payment to newest invoices/bills first based on document date',
            self::PROPORTIONAL => 'Distribute payment proportionally across all outstanding documents',
            self::MANUAL => 'Allow user to specify exact allocation amounts per document',
            self::OLDEST_FIRST => 'Allocate payment to documents with oldest due dates first',
            self::SMALLEST_FIRST => 'Allocate payment to documents with smallest balances first',
            self::LARGEST_FIRST => 'Allocate payment to documents with largest balances first',
        };
    }

    /**
     * Get the default allocation method.
     */
    public static function default(): self
    {
        return self::FIFO;
    }

    /**
     * Get all automatic allocation methods (excluding MANUAL).
     *
     * @return array<self>
     */
    public static function automaticMethods(): array
    {
        return array_filter(
            self::cases(),
            fn(self $method) => $method->isAutomatic()
        );
    }
}
