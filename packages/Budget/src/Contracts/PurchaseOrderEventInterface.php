<?php

declare(strict_types=1);

namespace Nexus\Budget\Contracts;

interface PurchaseOrderEventInterface
{
    public function getPurchaseOrderId(): string;
}
