<?php

declare(strict_types=1);

namespace Nexus\Laravel\Idempotency\Http;

use Nexus\Idempotency\ValueObjects\AttemptToken;
use Nexus\Idempotency\ValueObjects\ClientKey;
use Nexus\Idempotency\ValueObjects\OperationRef;
use Nexus\Idempotency\ValueObjects\RequestFingerprint;
use Nexus\Idempotency\ValueObjects\TenantId;

final readonly class IdempotencyRequest
{
    public function __construct(
        public TenantId $tenantId,
        public OperationRef $operationRef,
        public ClientKey $clientKey,
        public RequestFingerprint $fingerprint,
        public AttemptToken $attemptToken,
    ) {}
}
