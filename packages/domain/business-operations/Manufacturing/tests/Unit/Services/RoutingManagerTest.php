<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\Tests\Unit\Services;

use Nexus\Manufacturing\Contracts\RoutingInterface;
use Nexus\Manufacturing\Contracts\RoutingRepositoryInterface;
use Nexus\Manufacturing\Enums\OperationType;
use Nexus\Manufacturing\Exceptions\RoutingNotFoundException;
use Nexus\Manufacturing\Services\RoutingManager;
use Nexus\Manufacturing\Tests\TestCase;
use Nexus\Manufacturing\ValueObjects\Operation;
use PHPUnit\Framework\MockObject\MockObject;

final class RoutingManagerTest extends TestCase
{
    private RoutingRepositoryInterface&MockObject $repository;
    private RoutingManager $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->createMock(RoutingRepositoryInterface::class);

        $this->manager = new RoutingManager(
            $this->repository,
        );
    }

    public function testCreateRouting(): void
    {
        $productId = 'prod-001';
        $version = '1.0';
        $effectiveFrom = new \DateTimeImmutable('2024-01-01');

        $routing = $this->createMockRouting('route-001', $productId);

        $this->repository
            ->expects($this->once())
            ->method('create')
            ->willReturn($routing);

        $result = $this->manager->create(
            $productId,
            $version,
            [],
            $effectiveFrom
        );

        $this->assertSame($routing, $result);
    }

    public function testGetByIdReturnsRouting(): void
    {
        $routing = $this->createMockRouting('route-001', 'prod-001');

        $this->repository
            ->expects($this->once())
            ->method('findById')
            ->with('route-001')
            ->willReturn($routing);

        $result = $this->manager->getById('route-001');

        $this->assertSame($routing, $result);
    }

    public function testGetByIdThrowsExceptionWhenNotFound(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('findById')
            ->with('non-existent')
            ->willThrowException(RoutingNotFoundException::withId('non-existent'));

        $this->expectException(RoutingNotFoundException::class);

        $this->manager->getById('non-existent');
    }

    public function testGetEffectiveForDate(): void
    {
        $date = new \DateTimeImmutable('2024-06-15');
        $routing = $this->createMockRouting('route-001', 'prod-001');

        $this->repository
            ->expects($this->once())
            ->method('findByProductId')
            ->with('prod-001', $date)
            ->willReturn($routing);

        $result = $this->manager->getEffective('prod-001', $date);

        $this->assertSame($routing, $result);
    }

    public function testAddOperation(): void
    {
        $routing = $this->createMockRouting('route-001', 'prod-001', [], 'draft');

        $this->repository
            ->expects($this->once())
            ->method('findById')
            ->with('route-001')
            ->willReturn($routing);

        $this->repository
            ->expects($this->once())
            ->method('update')
            ->with('route-001', $this->anything());

        // Operation constructor: (operationNumber, workCenterId, description, type, ...)
        $operation = new Operation(
            operationNumber: 10,
            workCenterId: 'wc-001',
            description: 'Assembly',
            type: OperationType::PRODUCTION,
            setupTimeMinutes: 30.0,
            runTimeMinutes: 5.0,
        );

        $this->manager->addOperation('route-001', $operation);
    }

    public function testRemoveOperation(): void
    {
        $op1 = new Operation(
            operationNumber: 10,
            workCenterId: 'wc-001',
            description: 'Op 1',
            type: OperationType::PRODUCTION,
            setupTimeMinutes: 30.0,
            runTimeMinutes: 5.0,
        );
        $op2 = new Operation(
            operationNumber: 20,
            workCenterId: 'wc-002',
            description: 'Op 2',
            type: OperationType::INSPECTION,
            setupTimeMinutes: 10.0,
            runTimeMinutes: 2.0,
        );

        $routing = $this->createMockRouting('route-001', 'prod-001', [$op1, $op2], 'draft');

        $this->repository
            ->expects($this->once())
            ->method('findById')
            ->with('route-001')
            ->willReturn($routing);

        $this->repository
            ->expects($this->once())
            ->method('update')
            ->with('route-001', $this->anything());

        $this->manager->removeOperation('route-001', 10);
    }

    public function testCreateNewVersion(): void
    {
        $op1 = new Operation(
            operationNumber: 10,
            workCenterId: 'wc-001',
            description: 'Op 1',
            type: OperationType::PRODUCTION,
            setupTimeMinutes: 30.0,
            runTimeMinutes: 5.0,
        );

        $originalRouting = $this->createMockRouting('route-001', 'prod-001', [$op1]);
        $newRouting = $this->createMockRouting('route-002', 'prod-001', [$op1]);

        $this->repository
            ->expects($this->once())
            ->method('findById')
            ->with('route-001')
            ->willReturn($originalRouting);

        $this->repository
            ->expects($this->once())
            ->method('findAllVersions')
            ->with('prod-001')
            ->willReturn([$originalRouting]);

        $this->repository
            ->expects($this->once())
            ->method('create')
            ->willReturn($newRouting);

        $result = $this->manager->createVersion(
            'route-001',
            '2.0',
            new \DateTimeImmutable('2024-07-01')
        );

        $this->assertSame($newRouting, $result);
    }

    public function testRelease(): void
    {
        $op1 = new Operation(
            operationNumber: 10,
            workCenterId: 'wc-001',
            description: 'Op 1',
            type: OperationType::PRODUCTION,
            setupTimeMinutes: 30.0,
            runTimeMinutes: 5.0,
        );
        $routing = $this->createMockRouting('route-001', 'prod-001', [$op1], 'draft');

        $this->repository
            ->expects($this->once())
            ->method('findById')
            ->with('route-001')
            ->willReturn($routing);

        $this->repository
            ->expects($this->once())
            ->method('update')
            ->with('route-001', ['status' => 'released']);

        $this->manager->release('route-001');
    }

    public function testObsolete(): void
    {
        $routing = $this->createMockRouting('route-001', 'prod-001');

        $this->repository
            ->expects($this->once())
            ->method('findById')
            ->with('route-001')
            ->willReturn($routing);

        $this->repository
            ->expects($this->once())
            ->method('update')
            ->with('route-001', ['status' => 'obsolete']);

        $this->manager->obsolete('route-001');
    }

    /**
     * Create a mock routing.
     *
     * @param array<Operation> $operations
     */
    private function createMockRouting(
        string $id,
        string $productId,
        array $operations = [],
        string $status = 'draft'
    ): RoutingInterface&MockObject {
        if (empty($operations)) {
            $operations = [
                new Operation(
                    operationNumber: 10,
                    workCenterId: 'wc-001',
                    description: 'Default Op',
                    type: OperationType::PRODUCTION,
                    setupTimeMinutes: 30.0,
                    runTimeMinutes: 5.0,
                ),
            ];
        }

        $routing = $this->createMock(RoutingInterface::class);
        $routing->method('getId')->willReturn($id);
        $routing->method('getProductId')->willReturn($productId);
        $routing->method('getVersion')->willReturn(1);
        $routing->method('getOperations')->willReturn($operations);
        $routing->method('getStatus')->willReturn($status);

        return $routing;
    }
}
