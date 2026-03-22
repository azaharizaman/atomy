<?php

declare(strict_types=1);

namespace Nexus\Outbox\Contracts;

use Nexus\Outbox\Domain\OutboxRecord;
use Nexus\Outbox\ValueObjects\DedupKey;
use Nexus\Outbox\ValueObjects\OutboxMessageId;
use Nexus\Outbox\ValueObjects\TenantId;

interface OutboxQueryInterface
{
    public function findById(TenantId $tenantId, OutboxMessageId $id): ?OutboxRecord;

    public function findByDedupKey(TenantId $tenantId, DedupKey $dedupKey): ?OutboxRecord;
}
