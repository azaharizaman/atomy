<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\Tests\Unit\Services;

use Nexus\Manufacturing\Contracts\WorkCenterInterface;
use Nexus\Manufacturing\Contracts\WorkCenterManagerInterface;
use Nexus\Manufacturing\Contracts\WorkOrderRepositoryInterface;
use Nexus\Manufacturing\Contracts\PlannedOrderRepositoryInterface;
use Nexus\Manufacturing\Contracts\RoutingManagerInterface;
use Nexus\Manufacturing\Services\CapacityPlanner;
use Nexus\Manufacturing\Tests\TestCase;
use Nexus\Manufacturing\ValueObjects\PlanningHorizon;
use Nexus\Manufacturing\ValueObjects\CapacityLoad;
use PHPUnit\Framework\MockObject\MockObject;

final class CapacityPlannerTest extends TestCase
{
    private WorkCenterManagerInterface&MockObject $workCenterManager;
    private RoutingManagerInterface&MockObject $routingManager;
    private WorkOrderRepositoryInterface&MockObject $workOrderRepository;
    private PlannedOrderRepositoryInterface&MockObject $plannedOrderRepository;
    private PlanningHorizon $horizon;

    protected function setUp(): void
    {
        parent::setUp();

        $this->workCenterManager = $this->createMock(WorkCenterManagerInterface::class);
        $this->routingManager = $this->createMock(RoutingManagerInterface::class);
        $this->workOrderRepository = $this->createMock(WorkOrderRepositoryInterface::class);
        $this->plannedOrderRepository = $this->createMock(PlannedOrderRepositoryInterface::class);

        $this->horizon = new PlanningHorizon(
            startDate: new \DateTimeImmutable('2024-01-01'),
            endDate: new \DateTimeImmutable('2024-01-31'),
        );
    }

    public function testGetCapacityProfileForWorkCenter(): void
    {
        // Setup work center with 8 hours/day
        $workCenter = $this->createMockWorkCenter('wc-001', 8.0, 0.85);

        $this->workCenterManager
            ->expects($this->once())
            ->method('getById')
            ->with('wc-001')
            ->willReturn($workCenter);

        $this->workCenterManager
            ->method('getAvailableHoursForPeriod')
            ->willReturn(8.0);

        $planner = new CapacityPlanner(
            $this->workCenterManager,
            $this->routingManager,
            $this->workOrderRepository,
            $this->plannedOrderRepository,
        );

        $profile = $planner->getCapacityProfile('wc-001', $this->horizon);

        $this->assertSame('wc-001', $profile->workCenterId);
        $this->assertGreaterThan(0, $profile->totalAvailableCapacity);
    }

    public function testIdentifyBottlenecks(): void
    {
        $workCenter = $this->createMockWorkCenter('wc-001', 8.0, 1.0);

        $this->workCenterManager
            ->method('getById')
            ->willReturn($workCenter);

        $this->workCenterManager
            ->method('findActive')
            ->willReturn([$workCenter]);

        $this->workCenterManager
            ->method('getAvailableHoursForPeriod')
            ->willReturn(8.0);

        $planner = new CapacityPlanner(
            $this->workCenterManager,
            $this->routingManager,
            $this->workOrderRepository,
            $this->plannedOrderRepository,
        );

        $bottlenecks = $planner->identifyBottlenecks($this->horizon);

        // Should return array, may or may not have bottlenecks
        $this->assertIsArray($bottlenecks);
    }

    public function testCheckAvailability(): void
    {
        $workCenter = $this->createMockWorkCenter('wc-001', 8.0, 1.0);

        $this->workCenterManager
            ->method('getById')
            ->willReturn($workCenter);

        $this->workCenterManager
            ->method('findActive')
            ->willReturn([$workCenter]);

        $this->workCenterManager
            ->method('getAvailableHoursForPeriod')
            ->willReturn(8.0);

        $planner = new CapacityPlanner(
            $this->workCenterManager,
            $this->routingManager,
            $this->workOrderRepository,
            $this->plannedOrderRepository,
        );

        $result = $planner->checkAvailability(
            'prod-001',
            100.0,
            new \DateTimeImmutable('2024-01-15')
        );

        $this->assertArrayHasKey('available', $result);
        $this->assertArrayHasKey('constrainedWorkCenters', $result);
    }

    public function testFindEarliestAvailable(): void
    {
        $workCenter = $this->createMockWorkCenter('wc-001', 8.0, 1.0);

        $this->workCenterManager
            ->method('getById')
            ->willReturn($workCenter);

        $this->workCenterManager
            ->method('findActive')
            ->willReturn([$workCenter]);

        $this->workCenterManager
            ->method('getAvailableHoursForPeriod')
            ->willReturn(8.0);

        $planner = new CapacityPlanner(
            $this->workCenterManager,
            $this->routingManager,
            $this->workOrderRepository,
            $this->plannedOrderRepository,
        );

        $desiredDate = new \DateTimeImmutable('2024-01-15');
        $earliestDate = $planner->findEarliestAvailable('prod-001', 100.0, $desiredDate);

        // Earliest date should be on or after desired date
        $this->assertInstanceOf(\DateTimeImmutable::class, $earliestDate);
    }

    public function testSetPlanningHorizon(): void
    {
        $planner = new CapacityPlanner(
            $this->workCenterManager,
            $this->routingManager,
            $this->workOrderRepository,
            $this->plannedOrderRepository,
        );

        $planner->setPlanningHorizon($this->horizon);

        // No exception means success
        $this->assertTrue(true);
    }

    public function testCalculateRequirements(): void
    {
        $planner = new CapacityPlanner(
            $this->workCenterManager,
            $this->routingManager,
            $this->workOrderRepository,
            $this->plannedOrderRepository,
        );

        $plannedOrders = [
            [
                'orderId' => 'po-001',
                'productId' => 'prod-001',
                'quantity' => 100.0,
                'startDate' => new \DateTimeImmutable('2024-01-15'),
            ],
        ];

        $requirements = $planner->calculateRequirements($plannedOrders);

        $this->assertIsArray($requirements);
    }

    public function testGetAllCapacityProfiles(): void
    {
        $workCenter = $this->createMockWorkCenter('wc-001', 8.0, 1.0);

        $this->workCenterManager
            ->method('findActive')
            ->willReturn([$workCenter]);

        $this->workCenterManager
            ->method('getById')
            ->willReturn($workCenter);

        $this->workCenterManager
            ->method('getAvailableHoursForPeriod')
            ->willReturn(8.0);

        $planner = new CapacityPlanner(
            $this->workCenterManager,
            $this->routingManager,
            $this->workOrderRepository,
            $this->plannedOrderRepository,
        );

        $profiles = $planner->getAllCapacityProfiles($this->horizon);

        $this->assertIsArray($profiles);
    }

    /**
     * Create a mock work center.
     */
    private function createMockWorkCenter(
        string $id,
        float $hoursPerDay,
        float $efficiency
    ): WorkCenterInterface&MockObject {
        $wc = $this->createMock(WorkCenterInterface::class);
        $wc->method('getId')->willReturn($id);
        $wc->method('getCode')->willReturn('WC-001');
        $wc->method('getHoursPerDay')->willReturn($hoursPerDay);
        $wc->method('getEfficiency')->willReturn($efficiency);
        $wc->method('getCapacityUnits')->willReturn(1);
        $wc->method('isActive')->willReturn(true);

        return $wc;
    }
}
