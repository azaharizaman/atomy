<?php

declare(strict_types=1);

namespace Nexus\Idempotency\Services;

use DateTimeImmutable;
use Nexus\Idempotency\Contracts\IdempotencyClockInterface;
use Nexus\Idempotency\Contracts\IdempotencyPersistInterface;
use Nexus\Idempotency\Contracts\IdempotencyQueryInterface;
use Nexus\Idempotency\Contracts\IdempotencyServiceInterface;
use Nexus\Idempotency\Domain\BeginDecision;
use Nexus\Idempotency\Domain\IdempotencyPolicy;
use Nexus\Idempotency\Domain\IdempotencyRecord;
use Nexus\Idempotency\Enums\BeginOutcome;
use Nexus\Idempotency\Enums\IdempotencyRecordStatus;
use Nexus\Idempotency\Exceptions\IdempotencyCompletionException;
use Nexus\Idempotency\Exceptions\IdempotencyFailedRetryNotAllowedException;
use Nexus\Idempotency\Exceptions\IdempotencyFingerprintConflictException;
use Nexus\Idempotency\Exceptions\IdempotencyRecordExpiredException;
use Nexus\Idempotency\Exceptions\IdempotencyTenantMismatchException;
use Nexus\Idempotency\ValueObjects\AttemptToken;
use Nexus\Idempotency\ValueObjects\ClientKey;
use Nexus\Idempotency\ValueObjects\OperationRef;
use Nexus\Idempotency\ValueObjects\RequestFingerprint;
use Nexus\Idempotency\ValueObjects\ResultEnvelope;
use Nexus\Idempotency\ValueObjects\TenantId;

final readonly class IdempotencyService implements IdempotencyServiceInterface
{
    public function __construct(
        private IdempotencyQueryInterface $query,
        private IdempotencyPersistInterface $persist,
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
        $record = $this->query->find($tenantId, $operationRef, $clientKey);
        $record = $this->applyRecordCleanup($record, $tenantId, $operationRef, $clientKey, $fingerprint, $now);

        if ($record === null) {
            return $this->claimFirstExecution($tenantId, $operationRef, $clientKey, $fingerprint, $now);
        }

        return $this->decideForExistingRecord($record, $tenantId, $operationRef, $clientKey, $fingerprint, $now);
    }

    public function complete(
        TenantId $tenantId,
        OperationRef $operationRef,
        ClientKey $clientKey,
        RequestFingerprint $fingerprint,
        AttemptToken $attemptToken,
        ResultEnvelope $result,
    ): void {
        $now = $this->clock->now();
        $record = $this->requireOpenRecord($tenantId, $operationRef, $clientKey, $fingerprint, $attemptToken);
        $completed = $record->withCompleted($result, $now);
        $this->persist->save($completed);
    }

    public function fail(
        TenantId $tenantId,
        OperationRef $operationRef,
        ClientKey $clientKey,
        RequestFingerprint $fingerprint,
        AttemptToken $attemptToken,
    ): void {
        $now = $this->clock->now();
        $record = $this->requireOpenRecord($tenantId, $operationRef, $clientKey, $fingerprint, $attemptToken);
        $failed = $record->withFailed($now);
        $this->persist->save($failed);
    }

    /**
     * Deletes or nulls the record when policy/TTL says a fresh reservation is allowed.
     * For failed records with retry, requires matching fingerprint before delete.
     */
    private function applyRecordCleanup(
        ?IdempotencyRecord $record,
        TenantId $tenantId,
        OperationRef $operationRef,
        ClientKey $clientKey,
        RequestFingerprint $fingerprint,
        DateTimeImmutable $now,
    ): ?IdempotencyRecord {
        if ($record === null) {
            return null;
        }

        if ($record->status === IdempotencyRecordStatus::Failed) {
            if (! $this->policy->allowRetryAfterFail) {
                throw IdempotencyFailedRetryNotAllowedException::create();
            }
            if (! $fingerprint->equals($record->fingerprint)) {
                throw new IdempotencyFingerprintConflictException($operationRef->value, $clientKey->value);
            }
            $this->persist->delete($tenantId, $operationRef, $clientKey);

            return null;
        }

        if ($record->status === IdempotencyRecordStatus::Completed && $this->isCompletedReplayExpired($record, $now)) {
            $this->persist->delete($tenantId, $operationRef, $clientKey);

            return null;
        }

        if ($record->status === IdempotencyRecordStatus::Expired) {
            $this->persist->delete($tenantId, $operationRef, $clientKey);

            return null;
        }

        if ($record->status === IdempotencyRecordStatus::Pending
            || $record->status === IdempotencyRecordStatus::InProgress) {
            if ($this->isPendingExpired($record, $now)) {
                $this->persist->delete($tenantId, $operationRef, $clientKey);

                return null;
            }
        }

        return $record;
    }

    private function claimFirstExecution(
        TenantId $tenantId,
        OperationRef $operationRef,
        ClientKey $clientKey,
        RequestFingerprint $fingerprint,
        DateTimeImmutable $now,
    ): BeginDecision {
        $attemptToken = self::newAttemptToken();
        $candidate = new IdempotencyRecord(
            $tenantId,
            $operationRef,
            $clientKey,
            IdempotencyRecordStatus::Pending,
            $fingerprint,
            $attemptToken,
            null,
            $now,
            $now,
        );
        $claim = $this->persist->claimPending($candidate);
        if ($claim->claimedNew) {
            return new BeginDecision(BeginOutcome::FirstExecution, null, $claim->record);
        }

        $normalized = $this->applyRecordCleanup(
            $claim->record,
            $tenantId,
            $operationRef,
            $clientKey,
            $fingerprint,
            $now,
        );
        if ($normalized === null) {
            return $this->claimFirstExecution($tenantId, $operationRef, $clientKey, $fingerprint, $now);
        }

        return $this->decideForExistingRecord(
            $normalized,
            $tenantId,
            $operationRef,
            $clientKey,
            $fingerprint,
            $now,
        );
    }

    private function decideForExistingRecord(
        IdempotencyRecord $record,
        TenantId $tenantId,
        OperationRef $operationRef,
        ClientKey $clientKey,
        RequestFingerprint $fingerprint,
        DateTimeImmutable $now,
    ): BeginDecision {
        $this->assertTenantMatch($record, $tenantId);

        if ($record->status === IdempotencyRecordStatus::Completed) {
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
            if (! $fingerprint->equals($record->fingerprint)) {
                throw new IdempotencyFingerprintConflictException($operationRef->value, $clientKey->value);
            }

            return new BeginDecision(BeginOutcome::InProgress, null, $record);
        }

        throw IdempotencyCompletionException::wrongState(
            'Unexpected idempotency record state: ' . $record->status->value
        );
    }

    private static function newAttemptToken(): AttemptToken
    {
        return new AttemptToken(bin2hex(random_bytes(16)));
    }

    private function assertTenantMatch(IdempotencyRecord $record, TenantId $tenantId): void
    {
        if ($record->tenantId->value !== $tenantId->value) {
            throw IdempotencyTenantMismatchException::create();
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
        AttemptToken $attemptToken,
    ): IdempotencyRecord {
        $record = $this->query->find($tenantId, $operationRef, $clientKey);
        if ($record === null) {
            throw IdempotencyCompletionException::wrongState('No idempotency record for this key.');
        }
        $this->assertTenantMatch($record, $tenantId);
        if (! $fingerprint->equals($record->fingerprint)) {
            throw new IdempotencyFingerprintConflictException($operationRef->value, $clientKey->value);
        }
        if (! $attemptToken->equals($record->attemptToken)) {
            throw IdempotencyCompletionException::wrongState(
                'Attempt token does not match the open idempotency record.'
            );
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
