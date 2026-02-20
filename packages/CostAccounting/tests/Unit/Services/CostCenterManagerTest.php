<?php

declare(strict_types=1);

namespace Nexus\CostAccounting\Tests\Unit\Services;

use Nexus\CostAccounting\Contracts\CostCenterPersistInterface;
use Nexus\CostAccounting\Contracts\CostCenterQueryInterface;
use Nexus\CostAccounting\Entities\CostCenter;
use Nexus\CostAccounting\Enums\CostCenterStatus;
use Nexus\CostAccounting\Exceptions\CostCenterNotFoundException;
use Nexus\CostAccounting\Events\CostCenterCreatedEvent;
use Nexus\CostAccounting\Services\CostCenterManager;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for CostCenterManager Service
 * 
 * @covers \Nexus\CostAccounting\Services\CostCenterManager
 */
final class CostCenterManagerTest extends TestCase
{
    private CostCenterManager $manager;
    private $mockPersist;
    private $mockQuery;
    private $mockEventDispatcher;
    private $mockLogger;

    protected function setUp(): void
    {
        $this->mockPersist = $this->createMock(CostCenterPersistInterface::class);
        $this->mockQuery = $this->createMock(CostCenterQueryInterface::class);
        $this->mockEventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->mockLogger = $this->createMock(LoggerInterface::class);

        $this->manager = new CostCenterManager(
            $this->mockPersist,
            $this->mockQuery,
            $this->mockEventDispatcher,
            $this->mockLogger
        );
    }

    public function testCreateCostCenter(): void
    {
        $data = [
            'code' => 'CC001',
            'name' => 'Test Cost Center',
            'tenant_id' => 'tenant_1',
        ];

        $this->mockQuery
            ->expects(self::once())
            ->method('findByCode')
            ->with('CC001')
            ->willReturn(null);

        $this->mockPersist
            ->expects(self::once())
            ->method('save')
            ->willReturnCallback(function (CostCenter $cc) {
                // Simulate save
            });

        $this->mockEventDispatcher
            ->expects(self::once())
            ->method('dispatch')
            ->with(self::isInstanceOf(CostCenterCreatedEvent::class));

        $result = $this->manager->create($data);

        self::assertInstanceOf(CostCenter::class, $result);
        self::assertSame('CC001', $result->getCode());
        self::assertSame('Test Cost Center', $result->getName());
    }

    public function testCreateCostCenterWithDuplicateCode(): void
    {
        $data = [
            'code' => 'CC001',
            'name' => 'Test Cost Center',
            'tenant_id' => 'tenant_1',
        ];

        $existing = new CostCenter(
            id: 'cc_123',
            code: 'CC001',
            name: 'Existing',
            tenantId: 'tenant_1',
            status: CostCenterStatus::Active
        );

        $this->mockQuery
            ->expects(self::once())
            ->method('findByCode')
            ->with('CC001')
            ->willReturn($existing);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('already exists');

        $this->manager->create($data);
    }

    public function testCreateCostCenterWithMissingFields(): void
    {
        $data = [
            'code' => 'CC001',
            // missing name and tenant_id
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required field');

        $this->manager->create($data);
    }

    public function testCreateCostCenterWithParent(): void
    {
        $data = [
            'code' => 'CC002',
            'name' => 'Child Cost Center',
            'tenant_id' => 'tenant_1',
            'parent_cost_center_id' => 'cc_parent',
        ];

        $parent = new CostCenter(
            id: 'cc_parent',
            code: 'CC001',
            name: 'Parent',
            tenantId: 'tenant_1',
            status: CostCenterStatus::Active
        );

        $this->mockQuery
            ->expects(self::exactly(2))
            ->method('findByCode')
            ->with('CC002')
            ->willReturn(null);

        $this->mockQuery
            ->expects(self::once())
            ->method('findById')
            ->with('cc_parent')
            ->willReturn($parent);

        $this->mockPersist
            ->expects(self::once())
            ->method('save');

        $this->mockEventDispatcher
            ->expects(self::once())
            ->method('dispatch');

        $result = $this->manager->create($data);

        self::assertInstanceOf(CostCenter::class, $result);
    }

    public function testUpdateCostCenter(): void
    {
        $costCenterId = 'cc_123';
        $data = ['name' => 'Updated Name'];

        $costCenter = new CostCenter(
            id: $costCenterId,
            code: 'CC001',
            name: 'Original Name',
            tenantId: 'tenant_1',
            status: CostCenterStatus::Active
        );

        $this->mockQuery
            ->expects(self::once())
            ->method('findById')
            ->with($costCenterId)
            ->willReturn($costCenter);

        $this->mockPersist
            ->expects(self::once())
            ->method('save');

        $result = $this->manager->update($costCenterId, $data);

        self::assertSame('Updated Name', $result->getName());
    }

    public function testUpdateCostCenterNotFound(): void
    {
        $costCenterId = 'nonexistent';
        $data = ['name' => 'Updated Name'];

        $this->mockQuery
            ->expects(self::once())
            ->method('findById')
            ->with($costCenterId)
            ->willReturn(null);

        $this->expectException(CostCenterNotFoundException::class);

        $this->manager->update($costCenterId, $data);
    }

    public function testDeleteCostCenter(): void
    {
        $costCenterId = 'cc_123';

        $costCenter = new CostCenter(
            id: $costCenterId,
            code: 'CC001',
            name: 'To Delete',
            tenantId: 'tenant_1',
            status: CostCenterStatus::Active
        );

        $this->mockQuery
            ->expects(self::once())
            ->method('findById')
            ->with($costCenterId)
            ->willReturn($costCenter);

        $this->mockQuery
            ->expects(self::once())
            ->method('findChildren')
            ->with($costCenterId)
            ->willReturn([]);

        $this->mockPersist
            ->expects(self::once())
            ->method('delete')
            ->with($costCenterId);

        $this->manager->delete($costCenterId);
    }

    public function testDeleteCostCenterWithChildren(): void
    {
        $costCenterId = 'cc_123';

        $costCenter = new CostCenter(
            id: $costCenterId,
            code: 'CC001',
            name: 'Parent',
            tenantId: 'tenant_1',
            status: CostCenterStatus::Active
        );

        $child = new CostCenter(
            id: 'cc_child',
            code: 'CC002',
            name: 'Child',
            tenantId: 'tenant_1',
            status: CostCenterStatus::Active,
            parentCostCenterId: $costCenterId
        );

        $this->mockQuery
            ->expects(self::once())
            ->method('findById')
            ->with($costCenterId)
            ->willReturn($costCenter);

        $this->mockQuery
            ->expects(self::once())
            ->method('findChildren')
            ->with($costCenterId)
            ->willReturn([$child]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot delete');

        $this->manager->delete($costCenterId);
    }

    public function testUpdateStatus(): void
    {
        $costCenterId = 'cc_123';

        $costCenter = new CostCenter(
            id: $costCenterId,
            code: 'CC001',
            name: 'Test',
            tenantId: 'tenant_1',
            status: CostCenterStatus::Active
        );

        $this->mockQuery
            ->expects(self::once())
            ->method('findById')
            ->with($costCenterId)
            ->willReturn($costCenter);

        $this->mockPersist
            ->expects(self::once())
            ->method('save');

        $this->manager->updateStatus($costCenterId, CostCenterStatus::Inactive);

        self::assertSame(CostCenterStatus::Inactive, $costCenter->getStatus());
    }

    public function testAssignParent(): void
    {
        $costCenterId = 'cc_child';
        $parentId = 'cc_parent';

        $costCenter = new CostCenter(
            id: $costCenterId,
            code: 'CC002',
            name: 'Child',
            tenantId: 'tenant_1',
            status: CostCenterStatus::Active
        );

        $parent = new CostCenter(
            id: $parentId,
            code: 'CC001',
            name: 'Parent',
            tenantId: 'tenant_1',
            status: CostCenterStatus::Active
        );

        $this->mockQuery
            ->expects(self::exactly(2))
            ->method('findById')
            ->willReturnMap([
                [$costCenterId, $costCenter],
                [$parentId, $parent],
            ]);

        $this->mockPersist
            ->expects(self::once())
            ->method('save');

        $this->manager->assignParent($costCenterId, $parentId);

        self::assertSame($parentId, $costCenter->getParentCostCenterId());
    }

    public function testAssignParentSelfReference(): void
    {
        $costCenterId = 'cc_123';

        $costCenter = new CostCenter(
            id: $costCenterId,
            code: 'CC001',
            name: 'Test',
            tenantId: 'tenant_1',
            status: CostCenterStatus::Active
        );

        $this->mockQuery
            ->expects(self::once())
            ->method('findById')
            ->with($costCenterId)
            ->willReturn($costCenter);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('cannot be its own parent');

        $this->manager->assignParent($costCenterId, $costCenterId);
    }

    public function testLinkBudget(): void
    {
        $costCenterId = 'cc_123';
        $budgetId = 'budget_1';

        $costCenter = new CostCenter(
            id: $costCenterId,
            code: 'CC001',
            name: 'Test',
            tenantId: 'tenant_1',
            status: CostCenterStatus::Active
        );

        $this->mockQuery
            ->expects(self::once())
            ->method('findById')
            ->with($costCenterId)
            ->willReturn($costCenter);

        $this->mockPersist
            ->expects(self::once())
            ->method('save');

        $this->manager->linkBudget($costCenterId, $budgetId);

        self::assertSame($budgetId, $costCenter->getBudgetId());
    }
}
