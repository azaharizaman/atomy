<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\Tests\Unit\Services;

use Nexus\Manufacturing\Contracts\WorkOrderInterface;
use Nexus\Manufacturing\Contracts\WorkOrderRepositoryInterface;
use Nexus\Manufacturing\Contracts\BomManagerInterface;
use Nexus\Manufacturing\Contracts\RoutingManagerInterface;
use Nexus\Manufacturing\Enums\WorkOrderStatus;
use Nexus\Manufacturing\Exceptions\InvalidWorkOrderStatusException;
use Nexus\Manufacturing\Exceptions\WorkOrderNotFoundException;
use Nexus\Manufacturing\Services\WorkOrderManager;
use Nexus\Manufacturing\Tests\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

final class WorkOrderManagerTest extends TestCase
{
    private WorkOrderRepositoryInterface&MockObject $repository;
    private BomManagerInterface&MockObject $bomManager;
    private RoutingManagerInterface&MockObject $routingManager;
    private WorkOrderManager $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->createMock(WorkOrderRepositoryInterface::class);
        $this->bomManager = $this->createMock(BomManagerInterface::class);
        $this->routingManager = $this->createMock(RoutingManagerInterface::class);

        $this->manager = new WorkOrderManager(
            $this->repository,
            $this->bomManager,
            $this->routingManager,
        );
    }

    public function testCreateWorkOrder(): void
    {
        $productId = 'prod-001';
        $quantity = 100.0;
        $plannedStartDate = new \DateTimeImmutable('2024-02-01');
        $plannedEndDate = new \DateTimeImmutable('2024-02-15');

        $workOrder = $this->createMockWorkOrder('wo-001', 'WO-2024-001', WorkOrderStatus::PLANNED);

        $this->repository
            ->expects($this->once())
            ->method('create')
            ->willReturn($workOrder);

        $result = $this->manager->create(
            $productId,
            $quantity,
            $plannedStartDate,
            $plannedEndDate
        );

        $this->assertSame($workOrder, $result);
    }

    public function testGetByIdReturnsWorkOrder(): void
    {
        $workOrder = $this->createMockWorkOrder('wo-001', 'WO-2024-001', WorkOrderStatus::PLANNED);

        $this->repository
            ->expects($this->once())
            ->method('findById')
            ->with('wo-001')
            ->willReturn($workOrder);

        $result = $this->manager->getById('wo-001');

        $this->assertSame($workOrder, $result);
    }

    public function testGetByIdThrowsExceptionWhenNotFound(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('findById')
            ->with('non-existent')
            ->willThrowException(WorkOrderNotFoundException::withId('non-existent'));

        $this->expectException(WorkOrderNotFoundException::class);

        $this->manager->getById('non-existent');
    }

    public function testReleaseWorkOrder(): void
    {
        $plannedOrder = $this->createMockWorkOrder('wo-001', 'WO-2024-001', WorkOrderStatus::PLANNED);

        $this->repository
            ->expects($this->once())
            ->method('findById')
            ->with('wo-001')
            ->willReturn($plannedOrder);

        $this->repository
            ->expects($this->once())
            ->method('update')
            ->with('wo-001', $this->callback(function ($data) {
                return $data['status'] === WorkOrderStatus::RELEASED->value;
            }));

        $this->manager->release('wo-001');
    }

    public function testStartWorkOrder(): void
    {
        $releasedOrder = $this->createMockWorkOrder('wo-001', 'WO-2024-001', WorkOrderStatus::RELEASED);

        $this->repository
            ->expects($this->once())
            ->method('findById')
            ->with('wo-001')
            ->willReturn($releasedOrder);

        $this->repository
            ->expects($this->once())
            ->method('update')
            ->with('wo-001', $this->callback(function ($data) {
                return $data['status'] === WorkOrderStatus::IN_PROGRESS->value;
            }));

        $this->manager->start('wo-001');
    }

    public function testCompleteWorkOrder(): void
    {
        $inProgressOrder = $this->createMockWorkOrder('wo-001', 'WO-2024-001', WorkOrderStatus::IN_PROGRESS);

        $this->repository
            ->expects($this->once())
            ->method('findById')
            ->with('wo-001')
            ->willReturn($inProgressOrder);

        $this->repository
            ->expects($this->once())
            ->method('update')
            ->with('wo-001', $this->callback(function ($data) {
                return $data['status'] === WorkOrderStatus::COMPLETED->value;
            }));

        $this->manager->complete('wo-001');
    }

    public function testCancelWorkOrder(): void
    {
        $plannedOrder = $this->createMockWorkOrder('wo-001', 'WO-2024-001', WorkOrderStatus::PLANNED);

        $this->repository
            ->expects($this->once())
            ->method('findById')
            ->with('wo-001')
            ->willReturn($plannedOrder);

        $this->repository
            ->expects($this->once())
            ->method('update')
            ->with('wo-001', $this->callback(function ($data) {
                return $data['status'] === WorkOrderStatus::CANCELLED->value;
            }));

        $this->manager->cancel('wo-001', 'Customer cancelled order');
    }

    public function testCannotReleaseFromInvalidStatus(): void
    {
        $completedOrder = $this->createMockWorkOrder('wo-001', 'WO-2024-001', WorkOrderStatus::COMPLETED);

        $this->repository
            ->expects($this->once())
            ->method('findById')
            ->with('wo-001')
            ->willReturn($completedOrder);

        $this->expectException(InvalidWorkOrderStatusException::class);

        $this->manager->release('wo-001');
    }

    public function testCannotStartFromPlanned(): void
    {
        // Start requires RELEASED status, not PLANNED
        $plannedOrder = $this->createMockWorkOrder('wo-001', 'WO-2024-001', WorkOrderStatus::PLANNED);

        $this->repository
            ->expects($this->once())
            ->method('findById')
            ->with('wo-001')
            ->willReturn($plannedOrder);

        $this->expectException(InvalidWorkOrderStatusException::class);

        $this->manager->start('wo-001');
    }

    public function testCloseWorkOrder(): void
    {
        $completedOrder = $this->createMockWorkOrder('wo-001', 'WO-2024-001', WorkOrderStatus::COMPLETED);

        $this->repository
            ->expects($this->once())
            ->method('findById')
            ->with('wo-001')
            ->willReturn($completedOrder);

        $this->repository
            ->expects($this->once())
            ->method('update')
            ->with('wo-001', $this->callback(function ($data) {
                return $data['status'] === WorkOrderStatus::CLOSED->value;
            }));

        $this->manager->close('wo-001');
    }

    public function testGetByNumber(): void
    {
        $workOrder = $this->createMockWorkOrder('wo-001', 'WO-2024-001', WorkOrderStatus::PLANNED);

        $this->repository
            ->expects($this->once())
            ->method('findByNumber')
            ->with('WO-2024-001')
            ->willReturn($workOrder);

        $result = $this->manager->getByNumber('WO-2024-001');

        $this->assertSame($workOrder, $result);
    }

    /**
     * Create a mock work order.
     */
    private function createMockWorkOrder(
        string $id,
        string $orderNumber,
        WorkOrderStatus $status
    ): WorkOrderInterface&MockObject {
        $workOrder = $this->createMock(WorkOrderInterface::class);
        $workOrder->method('getId')->willReturn($id);
        $workOrder->method('getOrderNumber')->willReturn($orderNumber);
        $workOrder->method('getStatus')->willReturn($status);
        $workOrder->method('getProductId')->willReturn('prod-001');
        $workOrder->method('getPlannedQuantity')->willReturn(100.0);

        return $workOrder;
    }
}
