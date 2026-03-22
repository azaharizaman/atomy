<?php

declare(strict_types=1);

namespace Nexus\Outbox\Services;

use DateTimeImmutable;
use Nexus\Outbox\Contracts\OutboxClockInterface;
use Nexus\Outbox\Contracts\OutboxPersistInterface;
use Nexus\Outbox\Contracts\OutboxQueryInterface;
use Nexus\Outbox\Contracts\OutboxServiceInterface;
use Nexus\Outbox\Domain\EnqueueResult;
use Nexus\Outbox\Domain\OutboxEnqueueCommand;
use Nexus\Outbox\Domain\OutboxRecord;
use Nexus\Outbox\Enums\OutboxRecordStatus;
use Nexus\Outbox\Exceptions\OutboxClaimExpiredException;
use Nexus\Outbox\Exceptions\OutboxClaimTokenMismatchException;
use Nexus\Outbox\Exceptions\OutboxInvalidTransitionException;
use Nexus\Outbox\Exceptions\OutboxNotFoundException;
use Nexus\Outbox\ValueObjects\ClaimToken;
use Nexus\Outbox\ValueObjects\FailureReason;
use Nexus\Outbox\ValueObjects\OutboxMessageId;
use Nexus\Outbox\ValueObjects\TenantId;

final readonly class OutboxService implements OutboxServiceInterface
{
    public function __construct(
        private OutboxQueryInterface $query,
        private OutboxPersistInterface $persist,
        private OutboxClockInterface $clock,
    ) {
    }

    public function enqueue(OutboxEnqueueCommand $command): EnqueueResult
    {
        $record = OutboxRecord::newPending(
            OutboxMessageId::generate(),
            $command->tenantId,
            $command->dedupKey,
            $command->eventType,
            $command->payload,
            $command->metadata,
            $command->correlationId,
            $command->causationId,
            $command->createdAt,
        );

        return $this->persist->enqueue($record);
    }

    public function claimNextPending(TenantId $tenantId, DateTimeImmutable $claimExpiresAt): ?OutboxRecord
    {
        $transitionedAt = $this->clock->now();

        return $this->persist->claimNextPending($tenantId, $transitionedAt, $claimExpiresAt);
    }

    public function markSent(TenantId $tenantId, OutboxMessageId $id, ClaimToken $claimToken): void
    {
        $now = $this->clock->now();
        $record = $this->requireSendingForCompletion($tenantId, $id, $claimToken, $now);
        $this->persist->save($record->withSent($now));
    }

    public function markFailed(TenantId $tenantId, OutboxMessageId $id, ClaimToken $claimToken, FailureReason $failureReason): void
    {
        $now = $this->clock->now();
        $record = $this->requireSendingForCompletion($tenantId, $id, $claimToken, $now);
        $this->persist->save($record->withFailed($failureReason->value, $now));
    }

    public function scheduleRetry(TenantId $tenantId, OutboxMessageId $id): void
    {
        $record = $this->query->findById($tenantId, $id);
        if ($record === null) {
            throw OutboxNotFoundException::forMessageId($id);
        }
        if ($record->status !== OutboxRecordStatus::Failed) {
            throw OutboxInvalidTransitionException::forStatus($record->status, 'scheduleRetry');
        }

        $this->persist->save($record->withRetryPending($this->clock->now()));
    }

    private function requireSendingForCompletion(
        TenantId $tenantId,
        OutboxMessageId $id,
        ClaimToken $claimToken,
        DateTimeImmutable $now,
    ): OutboxRecord {
        $record = $this->query->findById($tenantId, $id);
        if ($record === null) {
            throw OutboxNotFoundException::forMessageId($id);
        }
        if ($record->status !== OutboxRecordStatus::Sending) {
            throw OutboxInvalidTransitionException::forStatus($record->status, 'markSentOrFailed');
        }
        $storedToken = $record->claimToken;
        if ($storedToken === null || ! $storedToken->equals($claimToken)) {
            throw OutboxClaimTokenMismatchException::create();
        }
        $expiresAt = $record->claimExpiresAt;
        if ($expiresAt === null) {
            throw OutboxClaimTokenMismatchException::create();
        }
        if (! ($now < $expiresAt)) {
            throw OutboxClaimExpiredException::create();
        }

        return $record;
    }
}
