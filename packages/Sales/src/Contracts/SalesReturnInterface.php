<?php

declare(strict_types=1);

namespace Nexus\Sales\Contracts;

/**
 * Sales return (RMA) management service contract.
 * V1: Stub implementation throws NotImplementedException.
 * Phase 2: Full implementation with return order schema.
 */
interface SalesReturnInterface
{
    /**
     * Create return order from sales order.
     *
     * @param string $salesOrderId
     * @param array $returnLineItems Array of ['line_id' => quantity]
     * @param string $returnReason
     * @return string Return order ID
     * @throws \BadMethodCallException If Returns feature not implemented
     */
    public function createReturnOrder(
        string $salesOrderId,
        array $returnLineItems,
        string $returnReason
    ): string;
}
