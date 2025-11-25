<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\Services;

use Nexus\Manufacturing\Contracts\CapacityPlannerInterface;
use Nexus\Manufacturing\Contracts\WorkCenterManagerInterface;
use Nexus\Manufacturing\Contracts\RoutingManagerInterface;
use Nexus\Manufacturing\Contracts\WorkOrderRepositoryInterface;
use Nexus\Manufacturing\Contracts\PlannedOrderRepositoryInterface;
use Nexus\Manufacturing\Enums\WorkOrderStatus;
use Nexus\Manufacturing\Enums\ResolutionAction;
use Nexus\Manufacturing\Exceptions\CapacityExceededException;
use Nexus\Manufacturing\ValueObjects\PlanningHorizon;
use Nexus\Manufacturing\ValueObjects\CapacityProfile;
use Nexus\Manufacturing\ValueObjects\CapacityPeriod;
use Nexus\Manufacturing\ValueObjects\CapacityLoad;
use Nexus\Manufacturing\ValueObjects\CapacityResolutionSuggestion;

/**
 * Capacity Planner implementation.
 *
 * Calculates work center capacity loads and provides resolution suggestions.
 */
final class CapacityPlanner implements CapacityPlannerInterface
{
    /** @var PlanningHorizon|null */
    private ?PlanningHorizon $planningHorizon = null;

    /** @var array{frozen: int, slushy: int, liquid: int} */
    private array $zones = ['frozen' => 7, 'slushy' => 14, 'liquid' => 30];

    public function __construct(
        private readonly WorkCenterManagerInterface $workCenterManager,
        private readonly RoutingManagerInterface $routingManager,
        private readonly WorkOrderRepositoryInterface $workOrderRepository,
        private readonly PlannedOrderRepositoryInterface $plannedOrderRepository,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function getCapacityProfile(string $workCenterId, PlanningHorizon $horizon): CapacityProfile
    {
        $workCenter = $this->workCenterManager->getById($workCenterId);
        $periods = [];
        $totalAvailable = 0.0;
        $totalLoaded = 0.0;

        // Create periods based on horizon buckets
        foreach ($horizon->getBuckets() as $bucket) {
            $periodStart = $bucket['start'];
            $periodEnd = $bucket['end'];

            // Get available hours for this period
            $availableHours = $this->workCenterManager->getAvailableHoursForPeriod(
                $workCenterId,
                $periodStart,
                $periodEnd
            );

            // Get loads for this period
            $loads = $this->getLoadsForPeriod($workCenterId, $periodStart, $periodEnd);

            $loadedHours = array_sum(array_map(fn (CapacityLoad $l) => $l->hours, $loads));

            $periods[] = new CapacityPeriod(
                periodStart: $periodStart,
                periodEnd: $periodEnd,
                availableHours: $availableHours,
                loadedHours: $loadedHours,
                loads: $loads,
            );

            $totalAvailable += $availableHours;
            $totalLoaded += $loadedHours;
        }

        return new CapacityProfile(
            workCenterId: $workCenterId,
            horizon: $horizon,
            periods: $periods,
            totalAvailableCapacity: $totalAvailable,
            totalLoadedCapacity: $totalLoaded,
            calculatedAt: new \DateTimeImmutable(),
        );
    }

    /**
     * Calculate capacity load for a product.
     */
    public function calculateLoadForProduct(string $productId, float $quantity, PlanningHorizon $horizon): array
    {
        $profiles = [];

        try {
            $routing = $this->routingManager->getEffective($productId);
            $capacityRequirements = $this->routingManager->calculateCost($routing->getId(), $quantity);

            // This is simplified - real implementation would track per work center
            foreach ($this->workCenterManager->findActive() as $workCenter) {
                $workCenterId = $workCenter->getId();
                $profile = $this->getCapacityProfile($workCenterId, $horizon);

                // Add the new product load to assess impact
                $additionalHours = 0.0; // Simplified

                $profiles[$workCenterId] = [
                    'profile' => $profile,
                    'additionalHours' => $additionalHours,
                    'newTotalLoad' => $profile->totalLoadedCapacity + $additionalHours,
                    'newUtilization' => $profile->totalAvailableCapacity > 0
                        ? (($profile->totalLoadedCapacity + $additionalHours) / $profile->totalAvailableCapacity) * 100
                        : 0,
                    'isOverloaded' => ($profile->totalLoadedCapacity + $additionalHours) > $profile->totalAvailableCapacity,
                ];
            }
        } catch (\Exception) {
            // No routing for product
        }

        return $profiles;
    }

    /**
     * Get overloaded work centers.
     */
    public function getOverloadedWorkCenters(PlanningHorizon $horizon): array
    {
        $overloaded = [];
        $workCenters = $this->workCenterManager->findActive();

        foreach ($workCenters as $workCenter) {
            $profile = $this->getCapacityProfile($workCenter->getId(), $horizon);

            if ($profile->isOverloaded()) {
                $overloaded[] = [
                    'workCenterId' => $workCenter->getId(),
                    'workCenterCode' => $workCenter->getCode(),
                    'profile' => $profile,
                    'utilization' => $profile->getUtilization(),
                    'excessHours' => $profile->getExcessLoad(),
                    'overloadedPeriods' => count($profile->getOverloadedPeriods()),
                ];
            }
        }

        // Sort by excess load (highest first)
        usort($overloaded, fn ($a, $b) => $b['excessHours'] <=> $a['excessHours']);

        return $overloaded;
    }

    /**
     * Suggest resolutions for capacity constraints.
     */
    public function suggestResolutions(string $workCenterId, PlanningHorizon $horizon): array
    {
        $profile = $this->getCapacityProfile($workCenterId, $horizon);

        if (!$profile->isOverloaded()) {
            return [];
        }

        $suggestions = [];
        $excessHours = $profile->getExcessLoad();
        $workCenter = $this->workCenterManager->getById($workCenterId);

        // Strategy 1: Alternative work centers
        $alternatives = $this->workCenterManager->findAlternatives($workCenterId);
        foreach ($alternatives as $alternative) {
            $altProfile = $this->getCapacityProfile($alternative->getId(), $horizon);

            if ($altProfile->getAvailableCapacity() > 0) {
                $transferableHours = min($excessHours, $altProfile->getAvailableCapacity());

                $suggestions[] = CapacityResolutionSuggestion::alternativeWorkCenter(
                    alternativeWorkCenterId: $alternative->getId(),
                    resolvesHours: $transferableHours,
                    additionalCost: 0.0 // Could calculate based on rate differential
                );
            }
        }

        // Strategy 2: Overtime
        $maxOvertimeHoursPerDay = 4.0;
        $daysInHorizon = $horizon->getTotalDays();
        $workingDays = (int) ($daysInHorizon * ($workCenter->getDaysPerWeek() / 7));
        $maxOvertime = $maxOvertimeHoursPerDay * $workingDays;
        $overtimeHours = min($excessHours, $maxOvertime);

        if ($overtimeHours > 0) {
            $overtimeCostPerHour = 75.0; // Could be configurable
            $suggestions[] = CapacityResolutionSuggestion::overtime(
                overtimeHours: $overtimeHours,
                overtimeCostPerHour: $overtimeCostPerHour
            );
        }

        // Strategy 3: Reschedule to periods with capacity
        foreach ($profile->getOverloadedPeriods() as $overloadedPeriod) {
            // Find first period with available capacity
            foreach ($profile->periods as $period) {
                if (!$period->isOverloaded() && $period->periodStart > $overloadedPeriod->periodStart) {
                    $reschedulableHours = min(
                        $overloadedPeriod->getExcessHours(),
                        $period->getRemainingHours()
                    );

                    if ($reschedulableHours > 0) {
                        $daysDelayed = (int) $overloadedPeriod->periodStart->diff($period->periodStart)->days;

                        $suggestions[] = CapacityResolutionSuggestion::reschedule(
                            newDate: $period->periodStart,
                            resolvesHours: $reschedulableHours,
                            daysDelayed: $daysDelayed
                        );
                    }
                    break;
                }
            }
        }

        // Strategy 4: Split operations
        $suggestions[] = new CapacityResolutionSuggestion(
            action: ResolutionAction::SPLIT,
            description: 'Split large operations across multiple periods',
            resolvesHours: $excessHours * 0.5, // Estimate 50% resolution
            priority: ResolutionAction::SPLIT->getDefaultPriority(),
            requiresApproval: true,
            canAutoApply: false,
            reason: 'Splitting operations allows parallel processing',
        );

        // Strategy 5: Add shift
        $newShiftHours = $workCenter->getCapacityHoursPerDay() * $workingDays;
        if ($newShiftHours > 0 && $excessHours > $maxOvertime) {
            $suggestions[] = new CapacityResolutionSuggestion(
                action: ResolutionAction::ADD_SHIFT,
                description: "Add additional shift (adds {$newShiftHours} hours)",
                resolvesHours: $newShiftHours,
                priority: ResolutionAction::ADD_SHIFT->getDefaultPriority(),
                estimatedCost: $newShiftHours * 50.0, // Estimate cost per hour
                requiresApproval: true,
                canAutoApply: false,
                reason: 'Additional shift can fully resolve capacity shortage',
            );
        }

        // Sort by priority (lowest number = highest priority)
        usort($suggestions, fn ($a, $b) => $a->priority <=> $b->priority);

        return $suggestions;
    }

    /**
     * {@inheritdoc}
     */
    public function checkAvailability(
        string $productId,
        float $quantity,
        \DateTimeImmutable $startDate
    ): array {
        $constrainedWorkCenters = [];
        $available = true;

        try {
            $routing = $this->routingManager->getEffective($productId);
            // Check each work center has capacity
            foreach ($this->workCenterManager->findActive() as $workCenter) {
                $availableHours = $this->workCenterManager->getAvailableHours($workCenter->getId(), $startDate);
                $horizon = new PlanningHorizon(
                    startDate: $startDate,
                    endDate: $startDate->modify('+1 day'),
                );
                $loads = $this->getLoadsForPeriod($workCenter->getId(), $startDate, $startDate->modify('+1 day'));
                $loadedHours = array_sum(array_map(fn (CapacityLoad $l) => $l->hours, $loads));
                $remainingHours = $availableHours - $loadedHours;

                if ($remainingHours <= 0) {
                    $constrainedWorkCenters[] = $workCenter->getId();
                    $available = false;
                }
            }
        } catch (\Exception) {
            // No routing found
        }

        return [
            'available' => $available,
            'constrainedWorkCenters' => $constrainedWorkCenters,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function findEarliestAvailable(
        string $productId,
        float $quantity,
        \DateTimeImmutable $desiredDate
    ): \DateTimeImmutable {
        $current = $desiredDate;
        $maxDays = 365; // Search up to a year ahead

        for ($i = 0; $i < $maxDays; $i++) {
            $availability = $this->checkAvailability($productId, $quantity, $current);
            if ($availability['available']) {
                return $current;
            }
            $current = $current->modify('+1 day');
        }

        // If no slot found, return desired date + max days
        return $desiredDate->modify("+{$maxDays} days");
    }

    /**
     * Rough cut capacity planning - not in interface.
     */
    public function roughCutCapacityPlan(array $masterSchedule, PlanningHorizon $horizon): array
    {
        $rccp = [];

        foreach ($masterSchedule as $item) {
            $productId = $item['productId'];
            $quantity = $item['quantity'];
            $dueDate = new \DateTimeImmutable($item['dueDate']);

            $loads = $this->calculateLoadForProduct($productId, $quantity, $horizon);

            foreach ($loads as $workCenterId => $loadInfo) {
                if (!isset($rccp[$workCenterId])) {
                    $rccp[$workCenterId] = [
                        'workCenterId' => $workCenterId,
                        'totalAvailable' => $loadInfo['profile']->totalAvailableCapacity,
                        'items' => [],
                        'totalLoad' => 0.0,
                    ];
                }

                $rccp[$workCenterId]['items'][] = [
                    'productId' => $productId,
                    'quantity' => $quantity,
                    'dueDate' => $dueDate->format('Y-m-d'),
                    'hours' => $loadInfo['additionalHours'],
                ];

                $rccp[$workCenterId]['totalLoad'] += $loadInfo['additionalHours'];
            }
        }

        // Calculate utilization for each work center
        foreach ($rccp as $workCenterId => &$data) {
            $data['utilization'] = $data['totalAvailable'] > 0
                ? ($data['totalLoad'] / $data['totalAvailable']) * 100
                : 0;
            $data['isOverloaded'] = $data['totalLoad'] > $data['totalAvailable'];
        }

        return array_values($rccp);
    }

    /**
     * {@inheritdoc}
     */
    public function calculateRequirements(array $plannedOrders): array
    {
        $loads = [];

        foreach ($plannedOrders as $order) {
            $productId = $order['productId'];
            $quantity = $order['quantity'];
            $startDate = $order['startDate'];

            try {
                $routing = $this->routingManager->getEffective($productId);
                // Create capacity loads for each operation
                foreach ($routing->getOperations() as $operation) {
                    $loads[] = new CapacityLoad(
                        sourceId: $order['orderId'],
                        sourceType: 'planned_order',
                        workCenterId: $operation->workCenterId,
                        hours: $operation->getCapacityTimeHours($quantity),
                        setupHours: $operation->setupTimeMinutes / 60,
                        runHours: ($operation->runTimeMinutes * $quantity) / 60,
                        loadDate: $startDate,
                        operationNumber: $operation->operationNumber,
                        productId: $productId,
                        quantity: $quantity,
                    );
                }
            } catch (\Exception) {
                // No routing
            }
        }

        return $loads;
    }

    /**
     * {@inheritdoc}
     */
    public function getAllCapacityProfiles(PlanningHorizon $horizon): array
    {
        $profiles = [];
        $workCenters = $this->workCenterManager->findActive();

        foreach ($workCenters as $workCenter) {
            $profiles[$workCenter->getId()] = $this->getCapacityProfile($workCenter->getId(), $horizon);
        }

        return $profiles;
    }

    /**
     * {@inheritdoc}
     */
    public function identifyBottlenecks(PlanningHorizon $horizon, float $threshold = 0.9): array
    {
        $bottlenecks = [];
        $profiles = $this->getAllCapacityProfiles($horizon);

        foreach ($profiles as $workCenterId => $profile) {
            foreach ($profile->periods as $period) {
                $utilization = $period->availableHours > 0
                    ? $period->loadedHours / $period->availableHours
                    : 0;

                if ($utilization >= $threshold) {
                    $bottlenecks[] = [
                        'workCenterId' => $workCenterId,
                        'period' => $period->periodStart->format('Y-m-d'),
                        'utilization' => $utilization,
                        'overload' => max(0, $period->loadedHours - $period->availableHours),
                    ];
                }
            }
        }

        return $bottlenecks;
    }

    /**
     * {@inheritdoc}
     */
    public function levelLoad(array $orders): array
    {
        $suggestions = [];

        // Simple load leveling - suggest moving orders from high to low utilization periods
        foreach ($orders as $order) {
            $availability = $this->checkAvailability($order['productId'], $order['quantity'], $order['startDate']);
            if (!$availability['available']) {
                $newDate = $this->findEarliestAvailable($order['productId'], $order['quantity'], $order['startDate']);
                if ($newDate > $order['startDate']) {
                    $suggestions[] = [
                        'orderId' => $order['orderId'],
                        'originalDate' => $order['startDate'],
                        'suggestedDate' => $newDate,
                    ];
                }
            }
        }

        return $suggestions;
    }

    /**
     * {@inheritdoc}
     */
    public function setPlanningHorizon(PlanningHorizon $horizon): void
    {
        $this->planningHorizon = $horizon;
    }

    /**
     * {@inheritdoc}
     */
    public function getPlanningHorizon(): PlanningHorizon
    {
        return $this->planningHorizon ?? new PlanningHorizon(
            startDate: new \DateTimeImmutable(),
            endDate: (new \DateTimeImmutable())->modify('+90 days'),
        );
    }

    /**
     * {@inheritdoc}
     */
    public function setZones(array $zones): void
    {
        $this->zones = $zones;
    }

    /**
     * {@inheritdoc}
     */
    public function getZones(): array
    {
        return $this->zones;
    }

    /**
     * Get capacity loads for a work center in a period.
     *
     * @return array<CapacityLoad>
     */
    private function getLoadsForPeriod(
        string $workCenterId,
        \DateTimeImmutable $periodStart,
        \DateTimeImmutable $periodEnd
    ): array {
        $loads = [];

        // Get loads from firm work orders
        $workOrders = $this->workOrderRepository->findByWorkCenterAndDateRange(
            $workCenterId,
            $periodStart,
            $periodEnd,
            [WorkOrderStatus::PLANNED, WorkOrderStatus::RELEASED, WorkOrderStatus::IN_PROGRESS]
        );

        foreach ($workOrders as $workOrder) {
            foreach ($workOrder->getLines() as $line) {
                if ($line->workCenterId === $workCenterId && $line->isOperation()) {
                    $loads[] = new CapacityLoad(
                        sourceId: $workOrder->getId(),
                        sourceType: 'work_order',
                        workCenterId: $workCenterId,
                        hours: $line->plannedSetupHours + $line->plannedRunHours,
                        setupHours: $line->plannedSetupHours,
                        runHours: $line->plannedRunHours,
                        loadDate: $workOrder->getPlannedStartDate(),
                        operationNumber: $line->operationNumber,
                        productId: $workOrder->getProductId(),
                        quantity: $workOrder->getQuantity(),
                    );
                }
            }
        }

        // Get loads from planned orders
        $plannedOrders = $this->plannedOrderRepository->findByWorkCenterAndDateRange(
            $workCenterId,
            $periodStart,
            $periodEnd
        );

        foreach ($plannedOrders as $plannedOrder) {
            $loads[] = new CapacityLoad(
                sourceId: $plannedOrder->id ?? uniqid(),
                sourceType: 'planned_order',
                workCenterId: $workCenterId,
                hours: $plannedOrder->hours ?? 0.0,
                setupHours: $plannedOrder->setupHours ?? 0.0,
                runHours: $plannedOrder->runHours ?? 0.0,
                loadDate: $plannedOrder->startDate,
                productId: $plannedOrder->productId,
                quantity: $plannedOrder->quantity,
            );
        }

        return $loads;
    }
}
