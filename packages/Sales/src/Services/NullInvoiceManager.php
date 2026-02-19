<?php

declare(strict_types=1);

namespace Nexus\Sales\Services;

use Nexus\Sales\Contracts\InvoiceManagerInterface;
use Nexus\Sales\Exceptions\InvoiceGenerationUnavailableException;

/**
 * Null invoice manager implementation.
 *
 * This implementation throws InvoiceGenerationUnavailableException to indicate
 * that invoice generation requires the Receivable package adapter.
 *
 * Use this as the default binding when the Receivable package is not installed.
 * When Receivable is available, bind InvoiceManagerInterface to
 * ReceivableInvoiceManagerAdapter in the adapter layer.
 */
final readonly class NullInvoiceManager implements InvoiceManagerInterface
{
    /**
     * {@inheritDoc}
     *
     * @throws InvoiceGenerationUnavailableException Always throws to indicate unavailable feature
     */
    public function generateInvoiceFromOrder(string $salesOrderId): string
    {
        throw InvoiceGenerationUnavailableException::forOrder($salesOrderId);
    }
}
