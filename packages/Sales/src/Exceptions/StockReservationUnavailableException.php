<?php

declare(strict_types=1);

namespace Nexus\Sales\Exceptions;

/**
 * Exception thrown when stock reservation is unavailable.
 *
 * This exception is thrown by NullStockReservation when the
 * Inventory package is not installed or stock reservation is disabled.
 */
final class StockReservationUnavailableException extends SalesException
{
    /**
     * Create exception for unavailable stock reservation.
     */
    public static function unavailable(): self
    {
        return new self(
            'Stock reservation is not available. ' .
            'This feature requires the Nexus\\Inventory package. ' .
            'Please install and configure Nexus\\Inventory to enable stock reservation.'
        );
    }

    /**
     * Create exception for disabled stock reservation.
     */
    public static function disabled(): self
    {
        return new self(
            'Stock reservation is disabled. ' .
            'Enable stock reservation in configuration or install the Nexus\\Inventory package.'
        );
    }

    /**
     * Create exception for specific order.
     */
    public static function forOrder(string $salesOrderId): self
    {
        return new self(
            sprintf(
                'Stock reservation is not available for sales order %s. ' .
                'This feature requires the Nexus\\Inventory package.',
                $salesOrderId
            )
        );
    }
}