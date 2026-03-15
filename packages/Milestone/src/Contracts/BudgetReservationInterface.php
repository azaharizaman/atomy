<?php

declare(strict_types=1);

namespace Nexus\Milestone\Contracts;

/**
 * Check remaining budget for milestone billing (BUS-PRO-0077).
 * Implemented by adapter/orchestrator using Nexus\Budget or project budget context.
 */
interface BudgetReservationInterface
{
    /**
     * Whether the given amount can be reserved against the context (e.g. project).
     *
     * @param string $contextId e.g. project id
     * @param string $amount    monetary amount
     * @param string $currency  optional currency code
     */
    public function canReserve(string $contextId, string $amount, string $currency = ''): bool;
}
