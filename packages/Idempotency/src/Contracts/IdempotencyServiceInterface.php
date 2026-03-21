<?php

declare(strict_types=1);

namespace Nexus\Idempotency\Contracts;

use Nexus\Idempotency\Domain\BeginDecision;
use Nexus\Idempotency\ValueObjects\ClientKey;
use Nexus\Idempotency\ValueObjects\OperationRef;
use Nexus\Idempotency\ValueObjects\RequestFingerprint;
use Nexus\Idempotency\ValueObjects\ResultEnvelope;
use Nexus\Idempotency\ValueObjects\TenantId;

interface IdempotencyServiceInterface
{
    public function begin(
        TenantId $tenantId,
        OperationRef $operationRef,
        ClientKey $clientKey,
        RequestFingerprint $fingerprint,
    ): BeginDecision;

    public function complete(
        TenantId $tenantId,
        OperationRef $operationRef,
        ClientKey $clientKey,
        RequestFingerprint $fingerprint,
        ResultEnvelope $result,
    ): void;

    public function fail(
        TenantId $tenantId,
        OperationRef $operationRef,
        ClientKey $clientKey,
        RequestFingerprint $fingerprint,
    ): void;
}
