<?php

declare(strict_types=1);

namespace Nexus\PolicyEngine\Contracts;

use Nexus\PolicyEngine\Domain\PolicyDefinition;

interface PolicyValidatorInterface
{
    public function validate(PolicyDefinition $definition): void;
}
