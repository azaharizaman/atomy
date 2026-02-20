<?php

declare(strict_types=1);

namespace Nexus\Treasury\Repositories;

use Nexus\Treasury\Contracts\AuthorizationLimitInterface;
use Nexus\Treasury\Contracts\AuthorizationLimitRepositoryInterface;

final class InMemoryAuthorizationLimitRepository implements AuthorizationLimitRepositoryInterface
{
    /** @var array<string, AuthorizationLimitInterface> */
    private array $limits = [];

    public function findById(string $id): ?AuthorizationLimitInterface
    {
        return $this->limits[$id] ?? null;
    }

    public function findByUserId(string $userId): array
    {
        return array_values(
            array_filter(
                $this->limits,
                fn(AuthorizationLimitInterface $limit) => $limit->getUserId() === $userId
            )
        );
    }

    public function findByRoleId(string $roleId): array
    {
        return array_values(
            array_filter(
                $this->limits,
                fn(AuthorizationLimitInterface $limit) => $limit->getRoleId() === $roleId
            )
        );
    }

    public function findActiveByAmount(string $tenantId, float $amount, string $currency): ?AuthorizationLimitInterface
    {
        // First try to find a limit that covers the amount (limit >= amount)
        foreach ($this->limits as $limit) {
            if ($limit->getTenantId() === $tenantId 
                && $limit->getCurrency() === $currency
                && $limit->isActive()
                && $limit->getAmount() >= $amount) {
                return $limit;
            }
        }
        
        // If no covering limit found, return the highest limit for this currency
        $highestLimit = null;
        $highestAmount = 0;
        foreach ($this->limits as $limit) {
            if ($limit->getTenantId() === $tenantId 
                && $limit->getCurrency() === $currency
                && $limit->isActive()
                && $limit->getAmount() > $highestAmount) {
                $highestAmount = $limit->getAmount();
                $highestLimit = $limit;
            }
        }
        
        return $highestLimit;
    }

    public function save(AuthorizationLimitInterface $limit): void
    {
        $this->limits[$limit->getId()] = $limit;
    }

    public function delete(string $id): void
    {
        unset($this->limits[$id]);
    }
}
