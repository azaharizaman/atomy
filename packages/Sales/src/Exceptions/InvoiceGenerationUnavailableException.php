<?php

declare(strict_types=1);

namespace Nexus\Sales\Exceptions;

/**
 * Exception thrown when invoice generation is unavailable.
 *
 * This exception is thrown by NullInvoiceManager when the
 * Receivable package is not installed or invoice generation is disabled.
 */
final class InvoiceGenerationUnavailableException extends SalesException
{
    /**
     * Create exception for unavailable invoice generation.
     */
    public static function unavailable(): self
    {
        return new self(
            'Invoice generation is not available. ' .
            'This feature requires the Nexus\\Receivable package. ' .
            'Please install and configure Nexus\\Receivable to enable automatic invoice generation from sales orders.'
        );
    }

    /**
     * Create exception for disabled invoice generation.
     */
    public static function disabled(): self
    {
        return new self(
            'Invoice generation is disabled. ' .
            'Enable invoice generation in configuration or install the Nexus\\Receivable package.'
        );
    }

    /**
     * Create exception for specific order.
     */
    public static function forOrder(string $salesOrderId): self
    {
        return new self(
            sprintf(
                'Invoice generation is not available for sales order %s. ' .
                'This feature requires the Nexus\\Receivable package.',
                $salesOrderId
            )
        );
    }
}