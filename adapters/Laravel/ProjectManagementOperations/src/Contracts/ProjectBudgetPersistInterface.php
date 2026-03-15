<?php

declare(strict_types=1);

namespace Nexus\Laravel\ProjectManagementOperations\Contracts;

use Nexus\Common\ValueObjects\Money;

/**
 * Update earned revenue for a project. Implemented by the app or Budget adapter.
 */
interface ProjectBudgetPersistInterface
{
    public function updateEarnedRevenue(string $projectId, Money $amount): void;
}
