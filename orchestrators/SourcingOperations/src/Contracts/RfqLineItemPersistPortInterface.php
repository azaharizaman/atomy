<?php

declare(strict_types=1);

namespace Nexus\SourcingOperations\Contracts;

interface RfqLineItemPersistPortInterface
{
    /**
     * @param array<int, mixed> $lineItems
     */
    public function copyToRfq(string $tenantId, string $sourceRfqId, string $targetRfqId, array $lineItems): int;
}
