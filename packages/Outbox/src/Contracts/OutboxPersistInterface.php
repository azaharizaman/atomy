<?php

declare(strict_types=1);

namespace Nexus\Outbox\Contracts;

use DateTimeImmutable;
use Nexus\Outbox\Domain\EnqueueResult;
use Nexus\Outbox\Domain\OutboxRecord;
use Nexus\Outbox\ValueObjects\TenantId;

interface OutboxPersistInterface
{
    /**
     * Inserts the record when no row exists for (tenantId, dedupKey); otherwise returns the existing row unchanged.
     */
    public function enqueue(OutboxRecord $newRecordIfAbsent): EnqueueResult;

    public function save(OutboxRecord $record): void;

    /**
     * Atomically leases the oldest pending message for the tenant: Pending → Sending with a new claim token and expiry.
     *
     * @param DateTimeImmutable $transitionedAt Timestamp for {@see OutboxRecord::$updatedAt} (typically clock "now").
     * @param DateTimeImmutable $claimExpiresAt Exclusive lease upper bound for {@see OutboxRecord::$claimExpiresAt}.
     */
    public function claimNextPending(
        TenantId $tenantId,
        DateTimeImmutable $transitionedAt,
        DateTimeImmutable $claimExpiresAt,
    ): ?OutboxRecord;
}
