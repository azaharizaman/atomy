<?php

declare(strict_types=1);

namespace Nexus\ProjectManagementOperations\Contracts;

use Nexus\Common\ValueObjects\Money;

interface BudgetPersistInterface
{
    /**
     * Update earned revenue for a project
     */
    public function updateEarnedRevenue(string $projectId, Money $amount): void;
}
