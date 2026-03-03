<?php

declare(strict_types=1);

namespace Nexus\Budget\Contracts;

interface PurchaseOrderClosedEventInterface
{
    public function getPurchaseOrderId(): string;
}
