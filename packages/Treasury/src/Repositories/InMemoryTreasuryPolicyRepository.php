<?php

declare(strict_types=1);

namespace Nexus\Treasury\Repositories;

use Nexus\Treasury\Contracts\TreasuryPolicyInterface;
use Nexus\Treasury\Contracts\TreasuryPolicyRepositoryInterface;
use Nexus\Treasury\Enums\TreasuryStatus;

final class InMemoryTreasuryPolicyRepository implements TreasuryPolicyRepositoryInterface
{
    /** @var array<string, TreasuryPolicyInterface> */
    private array $policies = [];

    public function findById(string $id): ?TreasuryPolicyInterface
    {
        return $this->policies[$id] ?? null;
    }

    public function findByTenantId(string $tenantId): array
    {
        return array_values(
            array_filter(
                $this->policies,
                fn(TreasuryPolicyInterface $policy) => $policy->getTenantId() === $tenantId
            )
        );
    }

    public function findByStatus(string $tenantId, TreasuryStatus $status): array
    {
        return array_values(
            array_filter(
                $this->policies,
                fn(TreasuryPolicyInterface $policy) => 
                    $policy->getTenantId() === $tenantId && $policy->getStatus() === $status
            )
        );
    }

    public function save(TreasuryPolicyInterface $policy): void
    {
        $this->policies[$policy->getId()] = $policy;
    }

    public function delete(string $id): void
    {
        unset($this->policies[$id]);
    }
}
