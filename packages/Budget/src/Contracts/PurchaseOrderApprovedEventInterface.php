<?php

declare(strict_types=1);

namespace Nexus\Budget\Contracts;

use Nexus\Common\ValueObjects\Money;

interface PurchaseOrderApprovedEventInterface extends PurchaseOrderEventInterface
{
    public function getTotalAmount(): Money;

    public function getAccountId(): ?string;
}
