<?php

declare(strict_types=1);

namespace Nexus\SourcingOperations\Contracts;

use Nexus\SourcingOperations\DTOs\RfqLifecycleRecord;

interface RfqLifecycleQueryPortInterface
{
    public function findByTenantAndId(string $tenantId, string $rfqId): ?RfqLifecycleRecord;
}
