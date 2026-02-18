<?php

declare(strict_types=1);

namespace Nexus\SupplyChainOperations\Contracts;

interface SupplyChainReceivableManagerInterface
{
    public function createCreditNote(
        string $tenantId,
        string $customerId,
        string $salesOrderId,
        float $amount,
        string $reason,
        ?string $reference = null
    ): string;
}
