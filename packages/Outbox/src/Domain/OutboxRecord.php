<?php

declare(strict_types=1);

namespace Nexus\Outbox\Domain;

use DateTimeImmutable;
use Nexus\Outbox\Enums\OutboxRecordStatus;
use Nexus\Outbox\Exceptions\OutboxInvalidTransitionException;
use Nexus\Outbox\ValueObjects\ClaimToken;
use Nexus\Outbox\ValueObjects\DedupKey;
use Nexus\Outbox\ValueObjects\EventTypeRef;
use Nexus\Outbox\ValueObjects\OutboxMessageId;
use Nexus\Outbox\ValueObjects\TenantId;

final readonly class OutboxRecord
{
    /**
     * @param array<string, mixed> $payload
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        public OutboxMessageId $id,
        public TenantId $tenantId,
        public DedupKey $dedupKey,
        public OutboxRecordStatus $status,
        public EventTypeRef $eventType,
        public array $payload,
        public array $metadata,
        public ?string $correlationId,
        public ?string $causationId,
        public ?ClaimToken $claimToken,
        public ?DateTimeImmutable $claimExpiresAt,
        public int $attemptCount,
        public ?string $failureReason,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<string, mixed> $metadata
     */
    public static function newPending(
        OutboxMessageId $id,
        TenantId $tenantId,
        DedupKey $dedupKey,
        EventTypeRef $eventType,
        array $payload,
        array $metadata,
        ?string $correlationId,
        ?string $causationId,
        DateTimeImmutable $createdAt,
    ): self {
        return new self(
            $id,
            $tenantId,
            $dedupKey,
            OutboxRecordStatus::Pending,
            $eventType,
            $payload,
            $metadata,
            $correlationId,
            $causationId,
            null,
            null,
            0,
            null,
            $createdAt,
            $createdAt,
        );
    }

    public function withSending(
        ClaimToken $claimToken,
        DateTimeImmutable $claimExpiresAt,
        DateTimeImmutable $at,
    ): self {
        if ($this->status !== OutboxRecordStatus::Pending) {
            throw OutboxInvalidTransitionException::forStatus($this->status, 'withSending');
        }

        return new self(
            $this->id,
            $this->tenantId,
            $this->dedupKey,
            OutboxRecordStatus::Sending,
            $this->eventType,
            $this->payload,
            $this->metadata,
            $this->correlationId,
            $this->causationId,
            $claimToken,
            $claimExpiresAt,
            $this->attemptCount,
            null,
            $this->createdAt,
            $at,
        );
    }

    public function withSent(DateTimeImmutable $at): self
    {
        if ($this->status !== OutboxRecordStatus::Sending) {
            throw OutboxInvalidTransitionException::forStatus($this->status, 'withSent');
        }

        return new self(
            $this->id,
            $this->tenantId,
            $this->dedupKey,
            OutboxRecordStatus::Sent,
            $this->eventType,
            $this->payload,
            $this->metadata,
            $this->correlationId,
            $this->causationId,
            null,
            null,
            $this->attemptCount,
            null,
            $this->createdAt,
            $at,
        );
    }

    public function withFailed(string $failureReason, DateTimeImmutable $at): self
    {
        if ($this->status !== OutboxRecordStatus::Sending) {
            throw OutboxInvalidTransitionException::forStatus($this->status, 'withFailed');
        }

        return new self(
            $this->id,
            $this->tenantId,
            $this->dedupKey,
            OutboxRecordStatus::Failed,
            $this->eventType,
            $this->payload,
            $this->metadata,
            $this->correlationId,
            $this->causationId,
            null,
            null,
            $this->attemptCount,
            $failureReason,
            $this->createdAt,
            $at,
        );
    }

    public function withRetryPending(DateTimeImmutable $at): self
    {
        if ($this->status !== OutboxRecordStatus::Failed) {
            throw OutboxInvalidTransitionException::forStatus($this->status, 'withRetryPending');
        }

        return new self(
            $this->id,
            $this->tenantId,
            $this->dedupKey,
            OutboxRecordStatus::Pending,
            $this->eventType,
            $this->payload,
            $this->metadata,
            $this->correlationId,
            $this->causationId,
            null,
            null,
            $this->attemptCount + 1,
            null,
            $this->createdAt,
            $at,
        );
    }
}
