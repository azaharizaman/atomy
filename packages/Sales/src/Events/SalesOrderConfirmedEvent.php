<?php

declare(strict_types=1);

namespace Nexus\Sales\Events;

use Nexus\Sales\Contracts\SalesOrderInterface;

/**
 * Event dispatched when a sales order is confirmed.
 */
final readonly class SalesOrderConfirmedEvent
{
    public function __construct(
        public SalesOrderInterface $salesOrder
    ) {
    }
}
