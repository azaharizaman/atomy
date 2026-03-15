<?php

declare(strict_types=1);

namespace Nexus\Milestone\Services;

use Nexus\Milestone\Contracts\BudgetReservationInterface;
use Nexus\Milestone\Contracts\MilestoneManagerInterface;
use Nexus\Milestone\Contracts\MilestonePersistInterface;
use Nexus\Milestone\Contracts\MilestoneQueryInterface;
use Nexus\Milestone\Exceptions\BudgetExceededException;
use Nexus\Milestone\ValueObjects\MilestoneSummary;

/**
 * Milestone lifecycle. BUS-PRO-0077: validates via BudgetReservationInterface when provided.
 */
final readonly class MilestoneManager implements MilestoneManagerInterface
{
    public function __construct(
        private MilestonePersistInterface $persist,
        private ?BudgetReservationInterface $budgetReservation = null,
        private ?MilestoneQueryInterface $query = null,
    ) {
    }

    public function create(MilestoneSummary $milestone): void
    {
        if ($this->budgetReservation !== null && $milestone->billingAmount !== '0') {
            if (!$this->budgetReservation->canReserve($milestone->contextId, $milestone->billingAmount, $milestone->currency)) {
                throw BudgetExceededException::forMilestone($milestone->id, $milestone->contextId);
            }
        }
        $this->persist->persist($milestone);
    }

    public function update(MilestoneSummary $milestone): void
    {
        if ($this->budgetReservation !== null && $this->query !== null && $milestone->billingAmount !== '0') {
            $existing = $this->query->getById($milestone->id);
            $previousAmount = $existing?->billingAmount ?? '0';
            $current = (float) $milestone->billingAmount;
            $previous = (float) $previousAmount;
            if ($current > $previous) {
                $additional = (string) ($current - $previous);
                if ($additional !== '0' && !$this->budgetReservation->canReserve($milestone->contextId, $additional, $milestone->currency)) {
                    throw BudgetExceededException::forMilestone($milestone->id, $milestone->contextId);
                }
            }
        }
        $this->persist->persist($milestone);
    }
}
