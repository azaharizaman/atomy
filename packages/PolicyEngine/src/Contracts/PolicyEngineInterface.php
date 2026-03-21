<?php

declare(strict_types=1);

namespace Nexus\PolicyEngine\Contracts;

use Nexus\PolicyEngine\Domain\PolicyDecision;
use Nexus\PolicyEngine\Domain\PolicyRequest;

interface PolicyEngineInterface
{
    public function evaluate(PolicyRequest $request): PolicyDecision;
}
