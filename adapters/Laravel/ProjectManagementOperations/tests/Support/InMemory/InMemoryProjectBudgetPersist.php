<?php

declare(strict_types=1);

namespace Nexus\Laravel\ProjectManagementOperations\Tests\Support\InMemory;

use Nexus\Common\ValueObjects\Money;
use Nexus\Laravel\ProjectManagementOperations\Contracts\ProjectBudgetPersistInterface;

/**
 * In-memory ProjectBudgetPersistInterface for integration tests; records calls for assertion.
 */
final class InMemoryProjectBudgetPersist implements ProjectBudgetPersistInterface
{
    /** @var list<array{tenantId: string, projectId: string, amount: Money}> */
    private array $updateEarnedRevenueCalls = [];

    public function updateEarnedRevenue(string $tenantId, string $projectId, Money $amount): void
    {
        $this->updateEarnedRevenueCalls[] = [
            'tenantId' => $tenantId,
            'projectId' => $projectId,
            'amount' => $amount,
        ];
    }

    /**
     * @return list<array{tenantId: string, projectId: string, amount: Money}>
     */
    public function getUpdateEarnedRevenueCalls(): array
    {
        return $this->updateEarnedRevenueCalls;
    }

    public function resetUpdateEarnedRevenueCalls(): void
    {
        $this->updateEarnedRevenueCalls = [];
    }
}
