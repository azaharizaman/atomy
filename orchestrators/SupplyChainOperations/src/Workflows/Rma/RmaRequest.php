<?php

declare(strict_types=1);

namespace Nexus\SupplyChainOperations\Workflows\Rma;

final readonly class RmaRequest
{
    public function __construct(
        public string $tenantId,
        public string $salesOrderId,
        public string $customerId,
        public array $items,
        public string $reason,
        public ?string $requestedBy = null
    ) {
    }
}
