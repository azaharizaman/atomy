<?php

declare(strict_types=1);

namespace Nexus\Idempotency\Services;

use JsonException;
use Nexus\Idempotency\Contracts\IdempotencyStoreInterface;
use RuntimeException;
use Nexus\Idempotency\Domain\IdempotencyRecord;
use Nexus\Idempotency\ValueObjects\ClientKey;
use Nexus\Idempotency\ValueObjects\OperationRef;
use Nexus\Idempotency\ValueObjects\TenantId;

final class InMemoryIdempotencyStore implements IdempotencyStoreInterface
{
    /** @var array<string, IdempotencyRecord> */
    private array $records = [];

    public function find(
        TenantId $tenantId,
        OperationRef $operationRef,
        ClientKey $clientKey,
    ): ?IdempotencyRecord {
        $key = self::compositeKey($tenantId, $operationRef, $clientKey);

        return $this->records[$key] ?? null;
    }

    public function save(IdempotencyRecord $record): void
    {
        $key = self::compositeKey($record->tenantId, $record->operationRef, $record->clientKey);
        $this->records[$key] = $record;
    }

    public function delete(
        TenantId $tenantId,
        OperationRef $operationRef,
        ClientKey $clientKey,
    ): void {
        $key = self::compositeKey($tenantId, $operationRef, $clientKey);
        unset($this->records[$key]);
    }

    /**
     * Injective key: JSON array of the three string dimensions (avoids delimiter collisions).
     */
    private static function compositeKey(
        TenantId $tenantId,
        OperationRef $operationRef,
        ClientKey $clientKey,
    ): string {
        $payload = [$tenantId->value, $operationRef->value, $clientKey->value];

        try {
            return json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        } catch (JsonException $e) {
            throw new RuntimeException('Idempotency store key encoding failed.', 0, $e);
        }
    }
}
