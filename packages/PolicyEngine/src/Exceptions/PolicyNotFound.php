<?php

declare(strict_types=1);

namespace Nexus\PolicyEngine\Exceptions;

use Nexus\PolicyEngine\ValueObjects\PolicyId;
use Nexus\PolicyEngine\ValueObjects\PolicyVersion;

final class PolicyNotFound extends PolicyEngineException
{
    public static function for(PolicyId $policyId, PolicyVersion $version): self
    {
        return new self(sprintf(
            'Policy not found: policyId=%s version=%s',
            $policyId->value,
            $version->value
        ));
    }
}
