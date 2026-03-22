<?php

declare(strict_types=1);

namespace Nexus\Idempotency\Contracts;

use Nexus\Idempotency\Domain\ClaimPendingResult;
use Nexus\Idempotency\Domain\IdempotencyRecord;
use Nexus\Idempotency\ValueObjects\ClientKey;
use Nexus\Idempotency\ValueObjects\OperationRef;
use Nexus\Idempotency\ValueObjects\TenantId;

interface IdempotencyPersistInterface
{
    /**
     * Atomically inserts the record if no row exists for (tenantId, operationRef, clientKey);
     * otherwise returns the existing row without modification.
     */
    public function claimPending(IdempotencyRecord $newRecordIfAbsent): ClaimPendingResult;

    public function save(IdempotencyRecord $record): void;

    public function delete(
        TenantId $tenantId,
        OperationRef $operationRef,
        ClientKey $clientKey,
    ): void;
}
