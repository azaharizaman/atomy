<?php

declare(strict_types=1);

namespace Nexus\Idempotency\Services;

use Nexus\Idempotency\Contracts\IdempotencyStoreInterface;
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
        $key = $this->compositeKey($tenantId, $operationRef, $clientKey);

        return $this->records[$key] ?? null;
    }

    public function save(IdempotencyRecord $record): void
    {
        $key = $this->compositeKey($record->tenantId, $record->operationRef, $record->clientKey);
        $this->records[$key] = $record;
    }

    public function delete(
        TenantId $tenantId,
        OperationRef $operationRef,
        ClientKey $clientKey,
    ): void {
        $key = $this->compositeKey($tenantId, $operationRef, $clientKey);
        unset($this->records[$key]);
    }

    private function compositeKey(
        TenantId $tenantId,
        OperationRef $operationRef,
        ClientKey $clientKey,
    ): string {
        return $tenantId->value . '|' . $operationRef->value . '|' . $clientKey->value;
    }
}
