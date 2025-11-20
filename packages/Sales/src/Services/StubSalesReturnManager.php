<?php

declare(strict_types=1);

namespace Nexus\Sales\Services;

use Nexus\Sales\Contracts\SalesReturnInterface;

/**
 * Stub sales return manager (V1 implementation).
 * 
 * Throws exception indicating Returns feature is not implemented.
 * Phase 2: Full implementation with return order schema.
 */
final readonly class StubSalesReturnManager implements SalesReturnInterface
{
    /**
     * {@inheritDoc}
     */
    public function createReturnOrder(
        string $salesOrderId,
        array $returnLineItems,
        string $returnReason
    ): string {
        throw new \BadMethodCallException(
            'Sales returns are not available in V1. ' .
            'This feature will be implemented in Phase 2 with dedicated return order schema. ' .
            'For now, please handle returns manually via credit notes in Nexus\Receivable.'
        );
    }
}
