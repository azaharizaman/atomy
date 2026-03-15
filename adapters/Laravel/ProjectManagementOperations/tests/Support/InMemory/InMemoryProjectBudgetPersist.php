<?php

declare(strict_types=1);

namespace Nexus\Laravel\ProjectManagementOperations\Tests\Support\InMemory;

use Nexus\Common\ValueObjects\Money;
use Nexus\Laravel\ProjectManagementOperations\Contracts\ProjectBudgetPersistInterface;

/**
 * No-op in-memory ProjectBudgetPersistInterface for integration tests.
 */
final class InMemoryProjectBudgetPersist implements ProjectBudgetPersistInterface
{
    public function updateEarnedRevenue(string $projectId, Money $amount): void
    {
        // No-op for tests
    }
}
