<?php

declare(strict_types=1);

namespace Nexus\Outbox\Contracts;

use DateTimeImmutable;
use Nexus\Outbox\Domain\EnqueueResult;
use Nexus\Outbox\Domain\OutboxEnqueueCommand;
use Nexus\Outbox\Domain\OutboxRecord;
use Nexus\Outbox\ValueObjects\ClaimToken;
use Nexus\Outbox\ValueObjects\FailureReason;
use Nexus\Outbox\ValueObjects\OutboxMessageId;
use Nexus\Outbox\ValueObjects\TenantId;

interface OutboxServiceInterface
{
    public function enqueue(OutboxEnqueueCommand $command): EnqueueResult;

    public function claimNextPending(TenantId $tenantId, DateTimeImmutable $leaseExpiresAt): ?OutboxRecord;

    public function markSent(TenantId $tenantId, OutboxMessageId $id, ClaimToken $claimToken): void;

    public function markFailed(TenantId $tenantId, OutboxMessageId $id, ClaimToken $claimToken, FailureReason $failureReason): void;

    public function scheduleRetry(TenantId $tenantId, OutboxMessageId $id): void;
}
