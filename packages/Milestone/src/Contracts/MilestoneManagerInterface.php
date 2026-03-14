<?php

declare(strict_types=1);

namespace Nexus\Milestone\Contracts;

use Nexus\Milestone\ValueObjects\MilestoneSummary;

/**
 * Milestone lifecycle (FUN-PRO-0569). BUS-PRO-0077: billing amount cannot exceed remaining budget.
 */
interface MilestoneManagerInterface
{
    /**
     * Create milestone. Optionally validates budget via BudgetReservationInterface when provided.
     *
     * @throws \Nexus\Milestone\Exceptions\BudgetExceededException When canReserve returns false.
     */
    public function create(MilestoneSummary $milestone): void;

    /**
     * Update milestone. Validates budget when billing amount increases.
     */
    public function update(MilestoneSummary $milestone): void;
}
