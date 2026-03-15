<?php

declare(strict_types=1);

namespace Nexus\Laravel\ProjectManagementOperations\Adapters;

use Nexus\ProjectManagementOperations\Contracts\BudgetPersistInterface;
use Nexus\Common\ValueObjects\Money;
use Nexus\Laravel\ProjectManagementOperations\Contracts\ProjectBudgetPersistInterface;

/**
 * Implements orchestrator BudgetPersistInterface using project-scoped budget persist.
 */
final readonly class BudgetPersistAdapter implements BudgetPersistInterface
{
    public function __construct(
        private ProjectBudgetPersistInterface $projectBudgetPersist,
    ) {
    }

    public function updateEarnedRevenue(string $tenantId, string $projectId, Money $amount): void
    {
        $this->projectBudgetPersist->updateEarnedRevenue($tenantId, $projectId, $amount);
    }
}
