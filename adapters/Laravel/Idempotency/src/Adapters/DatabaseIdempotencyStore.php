<?php

declare(strict_types=1);

namespace Nexus\Laravel\Idempotency\Adapters;

use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Nexus\Idempotency\Contracts\IdempotencyStoreInterface;
use Nexus\Idempotency\Domain\ClaimPendingResult;
use Nexus\Idempotency\Domain\IdempotencyRecord;
use Nexus\Idempotency\Enums\IdempotencyRecordStatus;
use Nexus\Idempotency\Exceptions\IdempotencyCompletionException;
use Nexus\Idempotency\ValueObjects\AttemptToken;
use Nexus\Idempotency\ValueObjects\ClientKey;
use Nexus\Idempotency\ValueObjects\OperationRef;
use Nexus\Idempotency\ValueObjects\RequestFingerprint;
use Nexus\Idempotency\ValueObjects\ResultEnvelope;
use Nexus\Idempotency\ValueObjects\TenantId;

final readonly class DatabaseIdempotencyStore implements IdempotencyStoreInterface
{
    private const TABLE = 'nexus_idempotency_records';

    public function find(
        TenantId $tenantId,
        OperationRef $operationRef,
        ClientKey $clientKey,
    ): ?IdempotencyRecord {
        $row = DB::table(self::TABLE)
            ->where('tenant_id', $tenantId->value)
            ->where('operation_ref', $operationRef->value)
            ->where('client_key', $clientKey->value)
            ->first();

        if ($row === null) {
            return null;
        }

        return $this->rowToDomain($row);
    }

    public function claimPending(IdempotencyRecord $newRecordIfAbsent): ClaimPendingResult
    {
        $now = $newRecordIfAbsent->createdAt;
        $values = [
            'tenant_id' => $newRecordIfAbsent->tenantId->value,
            'operation_ref' => $newRecordIfAbsent->operationRef->value,
            'client_key' => $newRecordIfAbsent->clientKey->value,
            'request_fingerprint' => $newRecordIfAbsent->fingerprint->value,
            'attempt_token' => $newRecordIfAbsent->attemptToken->value,
            'status' => $newRecordIfAbsent->status->value,
            'result_envelope' => null,
            'created_at' => $this->formatUtc($now),
            'last_transition_at' => $this->formatUtc($newRecordIfAbsent->lastTransitionAt),
        ];

        DB::table(self::TABLE)->insertOrIgnore($values);

        $existing = $this->find(
            $newRecordIfAbsent->tenantId,
            $newRecordIfAbsent->operationRef,
            $newRecordIfAbsent->clientKey,
        );
        if ($existing === null) {
            throw IdempotencyCompletionException::wrongState(
                'Idempotency claim failed: row missing after insert attempt.'
            );
        }

        if ($existing->attemptToken->value === $newRecordIfAbsent->attemptToken->value) {
            return new ClaimPendingResult(true, $existing);
        }

        return new ClaimPendingResult(false, $existing);
    }

    public function save(IdempotencyRecord $record): void
    {
        $payload = [
            'request_fingerprint' => $record->fingerprint->value,
            'attempt_token' => $record->attemptToken->value,
            'status' => $record->status->value,
            'result_envelope' => $record->resultEnvelope?->payload,
            'last_transition_at' => $this->formatUtc($record->lastTransitionAt),
        ];

        $updated = DB::table(self::TABLE)
            ->where('tenant_id', $record->tenantId->value)
            ->where('operation_ref', $record->operationRef->value)
            ->where('client_key', $record->clientKey->value)
            ->update($payload);

        if ($updated === 0) {
            throw IdempotencyCompletionException::wrongState('Idempotency save affected zero rows.');
        }
    }

    public function delete(
        TenantId $tenantId,
        OperationRef $operationRef,
        ClientKey $clientKey,
    ): void {
        DB::table(self::TABLE)
            ->where('tenant_id', $tenantId->value)
            ->where('operation_ref', $operationRef->value)
            ->where('client_key', $clientKey->value)
            ->delete();
    }

    private function rowToDomain(object $row): IdempotencyRecord
    {
        $status = IdempotencyRecordStatus::tryFrom((string) $row->status);
        if ($status === null) {
            throw IdempotencyCompletionException::wrongState('Unknown idempotency status in storage: ' . (string) $row->status);
        }

        $resultEnvelope = null;
        if (isset($row->result_envelope) && $row->result_envelope !== null && $row->result_envelope !== '') {
            $resultEnvelope = new ResultEnvelope((string) $row->result_envelope);
        }

        return new IdempotencyRecord(
            new TenantId((string) $row->tenant_id),
            new OperationRef((string) $row->operation_ref),
            new ClientKey((string) $row->client_key),
            $status,
            new RequestFingerprint((string) $row->request_fingerprint),
            new AttemptToken((string) $row->attempt_token),
            $resultEnvelope,
            $this->parseUtc((string) $row->created_at),
            $this->parseUtc((string) $row->last_transition_at),
        );
    }

    private function formatUtc(DateTimeImmutable $at): string
    {
        return $at->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
    }

    private function parseUtc(string $value): DateTimeImmutable
    {
        return Carbon::parse($value)->utc()->toDateTimeImmutable();
    }
}
