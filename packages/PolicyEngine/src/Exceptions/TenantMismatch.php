<?php

declare(strict_types=1);

namespace Nexus\PolicyEngine\Exceptions;

use Nexus\PolicyEngine\ValueObjects\TenantId;

final class TenantMismatch extends PolicyEngineException
{
    public static function between(TenantId $requestTenant, TenantId $policyTenant): self
    {
        return new self(sprintf(
            'Tenant mismatch: request tenant %s does not match policy tenant %s.',
            $requestTenant->value,
            $policyTenant->value
        ));
    }
}
