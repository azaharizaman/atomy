<?php

declare(strict_types=1);

namespace Nexus\PolicyEngine\Contracts;

use Nexus\PolicyEngine\Domain\PolicyDefinition;

/**
 * Reserved extension point for future policy compilation/caching.
 */
interface PolicyCompilerInterface
{
    public function compile(PolicyDefinition $definition): PolicyDefinition;
}
