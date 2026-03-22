<?php

declare(strict_types=1);

namespace Nexus\Laravel\Idempotency\Adapters;

use DateTimeImmutable;
use Nexus\Idempotency\Contracts\IdempotencyPersistInterface;
use Nexus\Idempotency\Contracts\IdempotencyQueryInterface;
use Nexus\Idempotency\Contracts\IdempotencyStoreInterface;
use Nexus\Idempotency\Domain\ClaimPendingResult;
use Nexus\Idempotency\Domain\IdempotencyRecord;
use Nexus\Idempotency\Enums\IdempotencyRecordStatus;
use Nexus\Idempotency\ValueObjects\AttemptToken;
use Nexus\Idempotency\ValueObjects\ClientKey;
use Nexus\Idempotency\ValueObjects\OperationRef;
use Nexus\Idempotency\ValueObjects\RequestFingerprint;
use Nexus\Idempotency\ValueObjects\ResultEnvelope;
use Nexus\Idempotency\ValueObjects\TenantId;
use Nexus\Laravel\Idempotency\Models\IdempotencyRecord as EloquentModel;

final class DatabaseIdempotencyStore implements IdempotencyStoreInterface
{
    public function __construct(
        private readonly EloquentModel $model
    ) {}

    public function find(
        TenantId $tenantId,
        OperationRef $operationRef,
        ClientKey $clientKey,
    ): ?IdempotencyRecord {
        $record = $this->model
            ->where('tenant_id', $tenantId->value)
            ->where('operation_ref', $operationRef->value)
            ->where('client_key', $clientKey->value)
            ->first();

        if ($record === null) {
            return null;
        }

        return $this->toDomainRecord($record);
    }

    public function claimPending(IdempotencyRecord $newRecordIfAbsent): ClaimPendingResult
    {
        $now = $newRecordIfAbsent->createdAt;
        $expiresAt = $this->calculateExpiresAt($now, $newRecordIfAbsent);

        $this->model->unguarded(function ($query) use ($newRecordIfAbsent, $expiresAt) {
            return $query->upsert(
                [
                    'tenant_id' => $newRecordIfAbsent->tenantId->value,
                    'operation_ref' => $newRecordIfAbsent->operationRef->value,
                    'client_key' => $newRecordIfAbsent->clientKey->value,
                    'request_fingerprint' => $newRecordIfAbsent->fingerprint->value,
                    'attempt_token' => $newRecordIfAbsent->attemptToken->value,
                    'status' => IdempotencyRecordStatus::Pending->value,
                    'expires_at' => $expiresAt->format('Y-m-d H:i:s'),
                    'created_at' => $newRecordIfAbsent->createdAt->format('Y-m-d H:i:s'),
                    'updated_at' => $newRecordIfAbsent->lastTransitionAt->format('Y-m-d H:i:s'),
                ],
                ['tenant_id', 'operation_ref', 'client_key'],
                [
                    'request_fingerprint' => $newRecordIfAbsent->fingerprint->value,
                    'attempt_token' => $newRecordIfAbsent->attemptToken->value,
                    'status' => IdempotencyRecordStatus::Pending->value,
                    'expires_at' => $expiresAt->format('Y-m-d H:i:s'),
                    'updated_at' => $newRecordIfAbsent->lastTransitionAt->format('Y-m-d H:i:s'),
                ]
            );
        });

        $existing = $this->find(
            $newRecordIfAbsent->tenantId,
            $newRecordIfAbsent->operationRef,
            $newRecordIfAbsent->clientKey
        );

        if ($existing === null) {
            throw new \RuntimeException('Failed to find or create idempotency record');
        }

        $claimedNew = $existing->fingerprint->value === $newRecordIfAbsent->fingerprint->value
            && $existing->attemptToken->value === $newRecordIfAbsent->attemptToken->value;

        return new ClaimPendingResult($claimedNew, $existing);
    }

    public function save(IdempotencyRecord $record): void
    {
        $eloquent = $this->model
            ->where('tenant_id', $record->tenantId->value)
            ->where('operation_ref', $record->operationRef->value)
            ->where('client_key', $record->clientKey->value)
            ->firstOrFail();

        $eloquent->status = $record->status->value;
        $eloquent->attempt_token = $record->attemptToken->value;
        $eloquent->request_fingerprint = $record->fingerprint->value;
        
        if ($record->resultEnvelope !== null) {
            $eloquent->result_envelope = $record->resultEnvelope->value;
        }

        $eloquent->expires_at = $record->lastTransitionAt->modify('+' . $this->getPendingTtlSeconds() . ' seconds');
        $eloquent->save();
    }

    public function delete(
        TenantId $tenantId,
        OperationRef $operationRef,
        ClientKey $clientKey,
    ): void {
        $this->model
            ->where('tenant_id', $tenantId->value)
            ->where('operation_ref', $operationRef->value)
            ->where('client_key', $clientKey->value)
            ->delete();
    }

    private function toDomainRecord(EloquentModel $model): IdempotencyRecord
    {
        return new IdempotencyRecord(
            new TenantId($model->tenant_id),
            new OperationRef($model->operation_ref),
            new ClientKey($model->client_key),
            IdempotencyRecordStatus::from($model->status),
            new RequestFingerprint($model->request_fingerprint),
            new AttemptToken($model->attempt_token),
            $model->result_envelope !== null ? new ResultEnvelope($model->result_envelope) : null,
            new DateTimeImmutable($model->created_at),
            new DateTimeImmutable($model->updated_at),
        );
    }

    private function calculateExpiresAt(DateTimeImmutable $now, IdempotencyRecord $record): DateTimeImmutable
    {
        return $now->modify('+' . $this->getPendingTtlSeconds() . ' seconds');
    }

    private function getPendingTtlSeconds(): int
    {
        return config('nexus-idempotency.policy.pending_ttl_seconds', 604800);
    }
}
