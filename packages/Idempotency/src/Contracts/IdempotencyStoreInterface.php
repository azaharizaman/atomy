<?php

declare(strict_types=1);

namespace Nexus\Idempotency\Contracts;

use Nexus\Idempotency\Domain\IdempotencyRecord;
use Nexus\Idempotency\ValueObjects\ClientKey;
use Nexus\Idempotency\ValueObjects\OperationRef;
use Nexus\Idempotency\ValueObjects\TenantId;

interface IdempotencyStoreInterface
{
    public function find(
        TenantId $tenantId,
        OperationRef $operationRef,
        ClientKey $clientKey,
    ): ?IdempotencyRecord;

    public function save(IdempotencyRecord $record): void;

    public function delete(
        TenantId $tenantId,
        OperationRef $operationRef,
        ClientKey $clientKey,
    ): void;
}
