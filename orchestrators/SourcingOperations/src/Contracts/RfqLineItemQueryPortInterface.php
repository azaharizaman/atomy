<?php

declare(strict_types=1);

namespace Nexus\SourcingOperations\Contracts;

interface RfqLineItemQueryPortInterface
{
    /**
     * @return array<int, mixed>
     */
    public function findByTenantAndRfqId(string $tenantId, string $rfqId): array;
}
