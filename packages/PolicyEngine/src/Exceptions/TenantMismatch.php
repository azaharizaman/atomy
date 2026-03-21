<?php

declare(strict_types=1);

namespace Nexus\PolicyEngine\Exceptions;

final class TenantMismatch extends PolicyEngineException
{
    public static function between(): self
    {
        return new self('Tenant mismatch.');
    }
}
