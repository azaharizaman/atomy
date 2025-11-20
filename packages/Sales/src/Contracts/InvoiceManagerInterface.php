<?php

declare(strict_types=1);

namespace Nexus\Sales\Contracts;

/**
 * Invoice generation service contract (stub for Nexus\Receivable integration).
 * V1: Stub implementation throws NotImplementedException.
 * Phase 2: Integrate with Nexus\Receivable for automatic invoice creation.
 */
interface InvoiceManagerInterface
{
    /**
     * Generate invoice(s) from sales order.
     *
     * @param string $salesOrderId
     * @return string Invoice ID
     * @throws \BadMethodCallException If Receivable package not installed
     */
    public function generateInvoiceFromOrder(string $salesOrderId): string;
}
