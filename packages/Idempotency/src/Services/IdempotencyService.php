<?php

declare(strict_types=1);

namespace Nexus\Idempotency\Services;

use DateTimeImmutable;
use Nexus\Idempotency\Contracts\IdempotencyClockInterface;
use Nexus\Idempotency\Contracts\IdempotencyServiceInterface;
use Nexus\Idempotency\Contracts\IdempotencyStoreInterface;
use Nexus\Idempotency\Domain\BeginDecision;
use Nexus\Idempotency\Domain\IdempotencyPolicy;
use Nexus\Idempotency\Domain\IdempotencyRecord;
use Nexus\Idempotency\Enums\BeginOutcome;
use Nexus\Idempotency\Enums\IdempotencyRecordStatus;
use Nexus\Idempotency\Exceptions\IdempotencyCompletionException;
use Nexus\Idempotency\Exceptions\IdempotencyFingerprintConflictException;
use Nexus\Idempotency\Exceptions\IdempotencyRecordExpiredException;
use Nexus\Idempotency\Exceptions\IdempotencyTenantMismatchException;
use Nexus\Idempotency\ValueObjects\ClientKey;
use Nexus\Idempotency\ValueObjects\OperationRef;
use Nexus\Idempotency\ValueObjects\RequestFingerprint;
use Nexus\Idempotency\ValueObjects\ResultEnvelope;
use Nexus\Idempotency\ValueObjects\TenantId;

final readonly class IdempotencyService implements IdempotencyServiceInterface
{
    public function __construct(
        private IdempotencyStoreInterface $store,
        private IdempotencyClockInterface $clock,
        private IdempotencyPolicy $policy,
    ) {
    }

    public function begin(
        TenantId $tenantId,
        OperationRef $operationRef,
        ClientKey $clientKey,
        RequestFingerprint $fingerprint,
    ): BeginDecision {
        $now = $this->clock->now();
        $record = $this->store->find($tenantId, $operationRef, $clientKey);

        if ($record !== null && $record->status === IdempotencyRecordStatus::Failed) {
            if (! $this->policy->allowRetryAfterFail) {
                throw IdempotencyCompletionException::wrongState(
                    'Idempotency key is in a failed state and retry is not allowed by policy.'
                );
            }
            $this->store->delete($tenantId, $operationRef, $clientKey);
            $record = null;
        }

        if ($record === null) {
            return $this->createFirstExecution($tenantId, $operationRef, $clientKey, $fingerprint, $now);
        }

        $this->assertTenantMatch($record, $tenantId);

        if ($record->status === IdempotencyRecordStatus::Completed) {
            if ($this->isCompletedReplayExpired($record, $now)) {
                $this->store->delete($tenantId, $operationRef, $clientKey);

                return $this->createFirstExecution($tenantId, $operationRef, $clientKey, $fingerprint, $now);
            }
            if (! $fingerprint->equals($record->fingerprint)) {
                throw new IdempotencyFingerprintConflictException($operationRef->value, $clientKey->value);
            }
            if ($record->resultEnvelope === null) {
                throw IdempotencyCompletionException::wrongState(
                    'Completed idempotency record is missing a result envelope.'
                );
            }

            return new BeginDecision(BeginOutcome::Replay, $record->resultEnvelope, $record);
        }

        if ($record->status === IdempotencyRecordStatus::Pending
            || $record->status === IdempotencyRecordStatus::InProgress) {
            if ($this->isPendingExpired($record, $now)) {
                $this->store->delete($tenantId, $operationRef, $clientKey);

                return $this->createFirstExecution($tenantId, $operationRef, $clientKey, $fingerprint, $now);
            }
            if (! $fingerprint->equals($record->fingerprint)) {
                throw new IdempotencyFingerprintConflictException($operationRef->value, $clientKey->value);
            }

            return new BeginDecision(BeginOutcome::InProgress, null, $record);
        }

        if ($record->status === IdempotencyRecordStatus::Expired) {
            $this->store->delete($tenantId, $operationRef, $clientKey);

            return $this->createFirstExecution($tenantId, $operationRef, $clientKey, $fingerprint, $now);
        }

        throw IdempotencyCompletionException::wrongState(
            'Unexpected idempotency record state: ' . $record->status->value
        );
    }

    public function complete(
        TenantId $tenantId,
        OperationRef $operationRef,
        ClientKey $clientKey,
        RequestFingerprint $fingerprint,
        ResultEnvelope $result,
    ): void {
        $now = $this->clock->now();
        $record = $this->requireOpenRecord($tenantId, $operationRef, $clientKey, $fingerprint);
        $completed = $record->withCompleted($result, $now);
        $this->store->save($completed);
    }

    public function fail(
        TenantId $tenantId,
        OperationRef $operationRef,
        ClientKey $clientKey,
        RequestFingerprint $fingerprint,
    ): void {
        $now = $this->clock->now();
        $record = $this->requireOpenRecord($tenantId, $operationRef, $clientKey, $fingerprint);
        $failed = $record->withFailed($now);
        $this->store->save($failed);
    }

    private function createFirstExecution(
        TenantId $tenantId,
        OperationRef $operationRef,
        ClientKey $clientKey,
        RequestFingerprint $fingerprint,
        DateTimeImmutable $now,
    ): BeginDecision {
        $record = new IdempotencyRecord(
            $tenantId,
            $operationRef,
            $clientKey,
            IdempotencyRecordStatus::Pending,
            $fingerprint,
            null,
            $now,
            $now,
        );
        $this->store->save($record);

        return new BeginDecision(BeginOutcome::FirstExecution, null, $record);
    }

    private function assertTenantMatch(IdempotencyRecord $record, TenantId $tenantId): void
    {
        if ($record->tenantId->value !== $tenantId->value) {
            throw new IdempotencyTenantMismatchException(
                expectedTenantId: $tenantId->value,
                actualTenantId: $record->tenantId->value,
            );
        }
    }

    private function isPendingExpired(IdempotencyRecord $record, DateTimeImmutable $now): bool
    {
        if ($this->policy->pendingTtlSeconds === null) {
            return false;
        }
        $expiresAt = $record->createdAt->modify('+' . (string) $this->policy->pendingTtlSeconds . ' seconds');

        return $now > $expiresAt;
    }

    private function isCompletedReplayExpired(IdempotencyRecord $record, DateTimeImmutable $now): bool
    {
        if ($this->policy->expireCompletedAfterSeconds === null) {
            return false;
        }
        $expiresAt = $record->lastTransitionAt->modify(
            '+' . (string) $this->policy->expireCompletedAfterSeconds . ' seconds'
        );

        return $now > $expiresAt;
    }

    private function requireOpenRecord(
        TenantId $tenantId,
        OperationRef $operationRef,
        ClientKey $clientKey,
        RequestFingerprint $fingerprint,
    ): IdempotencyRecord {
        $record = $this->store->find($tenantId, $operationRef, $clientKey);
        if ($record === null) {
            throw IdempotencyCompletionException::wrongState('No idempotency record for this key.');
        }
        $this->assertTenantMatch($record, $tenantId);
        if (! $fingerprint->equals($record->fingerprint)) {
            throw new IdempotencyFingerprintConflictException($operationRef->value, $clientKey->value);
        }
        if ($record->status !== IdempotencyRecordStatus::Pending
            && $record->status !== IdempotencyRecordStatus::InProgress) {
            throw IdempotencyCompletionException::wrongState(
                'Idempotency record is not open for completion (status: ' . $record->status->value . ').'
            );
        }
        if ($this->isPendingExpired($record, $this->clock->now())) {
            throw new IdempotencyRecordExpiredException($operationRef->value, $clientKey->value);
        }

        return $record;
    }
}
