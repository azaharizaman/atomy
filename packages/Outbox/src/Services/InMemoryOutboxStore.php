<?php

declare(strict_types=1);

namespace Nexus\Outbox\Services;

use DateTimeImmutable;
use JsonException;
use Nexus\Outbox\Contracts\OutboxStoreInterface;
use Nexus\Outbox\Domain\EnqueueResult;
use Nexus\Outbox\Domain\OutboxRecord;
use Nexus\Outbox\Enums\OutboxRecordStatus;
use Nexus\Outbox\ValueObjects\ClaimToken;
use Nexus\Outbox\ValueObjects\DedupKey;
use Nexus\Outbox\ValueObjects\OutboxMessageId;
use Nexus\Outbox\ValueObjects\TenantId;
use RuntimeException;

final class InMemoryOutboxStore implements OutboxStoreInterface
{
    /** @var array<string, OutboxRecord> */
    private array $byId = [];

    public function findById(TenantId $tenantId, OutboxMessageId $id): ?OutboxRecord
    {
        $record = $this->byId[$id->value] ?? null;
        if ($record === null) {
            return null;
        }
        if ($record->tenantId->value !== $tenantId->value) {
            return null;
        }

        return $record;
    }

    public function findByDedupKey(TenantId $tenantId, DedupKey $dedupKey): ?OutboxRecord
    {
        $key = self::dedupCompositeKey($tenantId, $dedupKey);
        $id = $this->dedupIndex[$key] ?? null;
        if ($id === null) {
            return null;
        }

        return $this->byId[$id] ?? null;
    }

    /** @var array<string, string> dedup composite key → outbox message id */
    private array $dedupIndex = [];

    public function enqueue(OutboxRecord $newRecordIfAbsent): EnqueueResult
    {
        $dedupKey = self::dedupCompositeKey($newRecordIfAbsent->tenantId, $newRecordIfAbsent->dedupKey);
        $existingId = $this->dedupIndex[$dedupKey] ?? null;
        if ($existingId !== null) {
            $existing = $this->byId[$existingId] ?? null;
            if ($existing !== null) {
                return new EnqueueResult(false, $existing);
            }
        }

        $this->byId[$newRecordIfAbsent->id->value] = $newRecordIfAbsent;
        $this->dedupIndex[$dedupKey] = $newRecordIfAbsent->id->value;

        return new EnqueueResult(true, $newRecordIfAbsent);
    }

    public function save(OutboxRecord $record): void
    {
        $this->byId[$record->id->value] = $record;
        $dedupKey = self::dedupCompositeKey($record->tenantId, $record->dedupKey);
        $this->dedupIndex[$dedupKey] = $record->id->value;
    }

    public function claimNextPending(
        TenantId $tenantId,
        DateTimeImmutable $transitionedAt,
        DateTimeImmutable $claimExpiresAt,
    ): ?OutboxRecord {
        $pending = [];
        foreach ($this->byId as $record) {
            if ($record->tenantId->value !== $tenantId->value) {
                continue;
            }
            if ($record->status !== OutboxRecordStatus::Pending) {
                continue;
            }
            $pending[] = $record;
        }

        if ($pending === []) {
            return null;
        }

        usort(
            $pending,
            static fn (OutboxRecord $a, OutboxRecord $b): int => $a->createdAt <=> $b->createdAt,
        );

        $first = $pending[0];
        $claimToken = new ClaimToken(bin2hex(random_bytes(16)));
        $claimed = $first->withSending($claimToken, $claimExpiresAt, $transitionedAt);

        $this->save($claimed);

        return $claimed;
    }

    private static function dedupCompositeKey(TenantId $tenantId, DedupKey $dedupKey): string
    {
        $payload = [$tenantId->value, $dedupKey->value];

        try {
            return json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        } catch (JsonException $e) {
            throw new RuntimeException('Outbox dedup key encoding failed.', 0, $e);
        }
    }
}
