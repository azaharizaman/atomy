<?php

declare(strict_types=1);

namespace Nexus\Idempotency\Domain;

use DateTimeImmutable;
use Nexus\Idempotency\Enums\IdempotencyRecordStatus;
use Nexus\Idempotency\ValueObjects\AttemptToken;
use Nexus\Idempotency\ValueObjects\ClientKey;
use Nexus\Idempotency\ValueObjects\OperationRef;
use Nexus\Idempotency\ValueObjects\RequestFingerprint;
use Nexus\Idempotency\ValueObjects\ResultEnvelope;
use Nexus\Idempotency\ValueObjects\TenantId;

final readonly class IdempotencyRecord
{
    public function __construct(
        public TenantId $tenantId,
        public OperationRef $operationRef,
        public ClientKey $clientKey,
        public IdempotencyRecordStatus $status,
        public RequestFingerprint $fingerprint,
        public AttemptToken $attemptToken,
        public ?ResultEnvelope $resultEnvelope,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $lastTransitionAt,
    ) {
    }

    public function withStatus(
        IdempotencyRecordStatus $status,
        DateTimeImmutable $at,
    ): self {
        return new self(
            $this->tenantId,
            $this->operationRef,
            $this->clientKey,
            $status,
            $this->fingerprint,
            $this->attemptToken,
            $this->resultEnvelope,
            $this->createdAt,
            $at,
        );
    }

    public function withCompleted(
        ResultEnvelope $result,
        DateTimeImmutable $at,
    ): self {
        return new self(
            $this->tenantId,
            $this->operationRef,
            $this->clientKey,
            IdempotencyRecordStatus::Completed,
            $this->fingerprint,
            $this->attemptToken,
            $result,
            $this->createdAt,
            $at,
        );
    }

    public function withFailed(DateTimeImmutable $at): self
    {
        return new self(
            $this->tenantId,
            $this->operationRef,
            $this->clientKey,
            IdempotencyRecordStatus::Failed,
            $this->fingerprint,
            $this->attemptToken,
            $this->resultEnvelope,
            $this->createdAt,
            $at,
        );
    }
}
