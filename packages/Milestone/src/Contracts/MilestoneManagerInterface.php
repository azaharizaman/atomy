<?php

declare(strict_types=1);

namespace Nexus\Milestone\Contracts;

/**
 * Milestone lifecycle. BUS-PRO-0077: billing amount cannot exceed remaining budget.
 * Orchestrator wires BudgetReservationInterface for remaining budget check.
 */
interface MilestoneManagerInterface
{
}
