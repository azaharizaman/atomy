<?php

declare(strict_types=1);

namespace Nexus\PolicyEngine\Exceptions;

use Nexus\PolicyEngine\Enums\PolicyKind;

final class UnsupportedPolicyKind extends PolicyEngineException
{
    public static function for(PolicyKind $kind): self
    {
        return new self(sprintf('Policy kind is not supported by runtime evaluator: %s', $kind->value));
    }
}
