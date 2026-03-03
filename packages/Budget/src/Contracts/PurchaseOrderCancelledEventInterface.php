<?php

declare(strict_types=1);

namespace Nexus\Budget\Contracts;

interface PurchaseOrderCancelledEventInterface
{
    public function getPurchaseOrderId(): string;
}
