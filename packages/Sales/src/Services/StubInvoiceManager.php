<?php

declare(strict_types=1);

namespace Nexus\Sales\Services;

use Nexus\Sales\Contracts\InvoiceManagerInterface;

/**
 * Stub invoice manager (V1 implementation).
 * 
 * Throws exception indicating Receivable package is not installed.
 * Phase 2: Replace with real implementation from Nexus\Receivable.
 */
final readonly class StubInvoiceManager implements InvoiceManagerInterface
{
    /**
     * {@inheritDoc}
     */
    public function generateInvoiceFromOrder(string $salesOrderId): string
    {
        throw new \BadMethodCallException(
            'Invoice generation is not available in V1. ' .
            'This feature requires the Nexus\Receivable package. ' .
            'Please install and configure Nexus\Receivable to enable automatic invoice generation from sales orders.'
        );
    }
}
