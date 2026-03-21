<?php

declare(strict_types=1);

namespace Nexus\PolicyEngine\Services;

use Nexus\PolicyEngine\Contracts\PolicyRegistryInterface;
use Nexus\PolicyEngine\Domain\PolicyDefinition;
use Nexus\PolicyEngine\Exceptions\PolicyNotFound;
use Nexus\PolicyEngine\ValueObjects\PolicyId;
use Nexus\PolicyEngine\ValueObjects\PolicyVersion;
use Nexus\PolicyEngine\ValueObjects\TenantId;

final class InMemoryPolicyRegistry implements PolicyRegistryInterface
{
    /** @var array<string, PolicyDefinition> */
    private array $items = [];

    public function put(PolicyDefinition $definition): void
    {
        $this->items[$this->key($definition->id, $definition->version, $definition->tenantId)] = $definition;
    }

    public function get(PolicyId $id, PolicyVersion $version, TenantId $tenantId): PolicyDefinition
    {
        $key = $this->key($id, $version, $tenantId);
        if (!isset($this->items[$key])) {
            throw PolicyNotFound::for($id, $version);
        }

        return $this->items[$key];
    }

    private function key(PolicyId $id, PolicyVersion $version, TenantId $tenantId): string
    {
        return $tenantId->value . '::' . $id->value . '::' . $version->value;
    }
}
