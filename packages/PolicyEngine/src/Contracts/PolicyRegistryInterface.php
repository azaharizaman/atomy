<?php

declare(strict_types=1);

namespace Nexus\PolicyEngine\Contracts;

use Nexus\PolicyEngine\Domain\PolicyDefinition;
use Nexus\PolicyEngine\ValueObjects\PolicyId;
use Nexus\PolicyEngine\ValueObjects\PolicyVersion;
use Nexus\PolicyEngine\ValueObjects\TenantId;

interface PolicyRegistryInterface
{
    public function get(PolicyId $id, PolicyVersion $version, TenantId $tenantId): PolicyDefinition;
}
