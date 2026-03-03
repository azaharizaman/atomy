<?php

declare(strict_types=1);

namespace Nexus\Budget\Contracts;

use Nexus\Common\ValueObjects\Money;

interface PurchaseOrderApprovedEventInterface
{
    public function getPurchaseOrderId(): string;

    public function getTotalAmount(): Money;
}
