<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\Tests\Unit\ValueObjects;

use Nexus\Manufacturing\Enums\OperationType;
use Nexus\Manufacturing\ValueObjects\Operation;
use Nexus\Manufacturing\Tests\TestCase;

final class OperationTest extends TestCase
{
    public function testCreateOperation(): void
    {
        $operation = new Operation(
            operationNumber: 10,
            workCenterId: 'wc-001',
            description: 'Main assembly operation',
            type: OperationType::PRODUCTION,
            setupTimeMinutes: 30.0,
            runTimeMinutes: 5.0,
            queueTimeMinutes: 10.0,
            moveTimeMinutes: 5.0,
            resourceCount: 1,
            overlapPercentage: 0.0,
        );

        $this->assertSame(10, $operation->operationNumber);
        $this->assertSame('wc-001', $operation->workCenterId);
        $this->assertSame('Main assembly operation', $operation->description);
        $this->assertSame(OperationType::PRODUCTION, $operation->type);
        $this->assertSame(30.0, $operation->setupTimeMinutes);
        $this->assertSame(5.0, $operation->runTimeMinutes);
        $this->assertSame(10.0, $operation->queueTimeMinutes);
        $this->assertSame(5.0, $operation->moveTimeMinutes);
        $this->assertSame(1, $operation->resourceCount);
        $this->assertSame(0.0, $operation->overlapPercentage);
    }

    public function testCalculateTotalTime(): void
    {
        $operation = new Operation(
            operationNumber: 10,
            workCenterId: 'wc-001',
            description: 'Assembly',
            type: OperationType::PRODUCTION,
            setupTimeMinutes: 30.0, // 30 minutes setup
            runTimeMinutes: 5.0, // 5 minutes per unit
            queueTimeMinutes: 10.0,
            moveTimeMinutes: 5.0,
        );

        // For 10 units: 30 + (5 * 10) + 10 + 5 = 95 minutes
        $totalTime = $operation->calculateTotalTime(10.0);
        $this->assertSame(95.0, $totalTime);
    }

    public function testCalculateTotalTimeHours(): void
    {
        $operation = new Operation(
            operationNumber: 10,
            workCenterId: 'wc-001',
            description: 'Assembly',
            type: OperationType::PRODUCTION,
            setupTimeMinutes: 30.0,
            runTimeMinutes: 5.0,
            queueTimeMinutes: 10.0,
            moveTimeMinutes: 5.0,
        );

        // 95 minutes / 60 = 1.5833... hours
        $totalTimeHours = $operation->calculateTotalTimeHours(10.0);
        $this->assertEqualsWithDelta(1.583, $totalTimeHours, 0.01);
    }

    public function testGetCapacityTimeHours(): void
    {
        $prodOp = new Operation(
            operationNumber: 10,
            workCenterId: 'wc-001',
            description: 'Production',
            type: OperationType::PRODUCTION,
            setupTimeMinutes: 30.0,
            runTimeMinutes: 5.0,
        );

        // Capacity time = (30 + 5*10) / 60 = 80/60 = 1.333... hours
        $capacityTime = $prodOp->getCapacityTimeHours(10.0);
        $this->assertEqualsWithDelta(1.333, $capacityTime, 0.01);

        $moveOp = new Operation(
            operationNumber: 20,
            workCenterId: 'wc-001',
            description: 'Move',
            type: OperationType::MOVE,
            setupTimeMinutes: 0.0,
            runTimeMinutes: 10.0,
        );

        // Move operations don't consume capacity
        $this->assertSame(0.0, $moveOp->getCapacityTimeHours(10.0));
    }

    public function testIsSubcontracted(): void
    {
        $prodOp = new Operation(
            operationNumber: 10,
            workCenterId: 'wc-001',
            description: 'Production',
            type: OperationType::PRODUCTION,
        );

        $subOp = new Operation(
            operationNumber: 20,
            workCenterId: 'wc-002',
            description: 'External coating',
            type: OperationType::SUBCONTRACT,
            subcontractorId: 'vendor-001',
            subcontractCost: 25.0,
        );

        $this->assertFalse($prodOp->isSubcontracted());
        $this->assertTrue($subOp->isSubcontracted());
    }

    public function testIsEffectiveAt(): void
    {
        $effectiveFrom = new \DateTimeImmutable('2024-01-01');
        $effectiveTo = new \DateTimeImmutable('2024-12-31');

        $operation = new Operation(
            operationNumber: 10,
            workCenterId: 'wc-001',
            description: 'Assembly',
            effectiveFrom: $effectiveFrom,
            effectiveTo: $effectiveTo,
        );

        $this->assertTrue($operation->isEffectiveAt(new \DateTimeImmutable('2024-06-15')));
        $this->assertFalse($operation->isEffectiveAt(new \DateTimeImmutable('2023-12-31')));
        $this->assertFalse($operation->isEffectiveAt(new \DateTimeImmutable('2025-01-01')));
    }

    public function testWithTimes(): void
    {
        $operation = new Operation(
            operationNumber: 10,
            workCenterId: 'wc-001',
            description: 'Assembly',
            setupTimeMinutes: 30.0,
            runTimeMinutes: 5.0,
        );

        $newOp = $operation->withTimes(
            setupTimeMinutes: 45.0,
            runTimeMinutes: 3.0,
            queueTimeMinutes: 15.0,
        );

        $this->assertSame(45.0, $newOp->setupTimeMinutes);
        $this->assertSame(3.0, $newOp->runTimeMinutes);
        $this->assertSame(15.0, $newOp->queueTimeMinutes);
        $this->assertSame('wc-001', $newOp->workCenterId);
    }

    public function testWithWorkCenter(): void
    {
        $operation = new Operation(
            operationNumber: 10,
            workCenterId: 'wc-001',
            description: 'Assembly',
        );

        $newOp = $operation->withWorkCenter('wc-002');

        $this->assertSame('wc-002', $newOp->workCenterId);
        $this->assertSame(10, $newOp->operationNumber);
    }

    public function testToArray(): void
    {
        $operation = new Operation(
            operationNumber: 10,
            workCenterId: 'wc-001',
            description: 'Main assembly',
            type: OperationType::PRODUCTION,
            setupTimeMinutes: 30.0,
            runTimeMinutes: 5.0,
            queueTimeMinutes: 10.0,
            moveTimeMinutes: 5.0,
            resourceCount: 2,
            overlapPercentage: 25.0,
        );

        $array = $operation->toArray();

        $this->assertSame(10, $array['operationNumber']);
        $this->assertSame('wc-001', $array['workCenterId']);
        $this->assertSame('Main assembly', $array['description']);
        $this->assertSame('production', $array['type']);
        $this->assertSame(30.0, $array['setupTimeMinutes']);
        $this->assertSame(5.0, $array['runTimeMinutes']);
        $this->assertSame(10.0, $array['queueTimeMinutes']);
        $this->assertSame(5.0, $array['moveTimeMinutes']);
        $this->assertSame(2, $array['resourceCount']);
        $this->assertSame(25.0, $array['overlapPercentage']);
    }

    public function testFromArray(): void
    {
        $data = [
            'operationNumber' => 20,
            'workCenterId' => 'wc-003',
            'description' => 'Final packing',
            'type' => 'packaging',
            'setupTimeMinutes' => 10.0,
            'runTimeMinutes' => 2.0,
            'overlapPercentage' => 0.0,
        ];

        $operation = Operation::fromArray($data);

        $this->assertSame(20, $operation->operationNumber);
        $this->assertSame('wc-003', $operation->workCenterId);
        $this->assertSame('Final packing', $operation->description);
        $this->assertSame(OperationType::PACKAGING, $operation->type);
    }

    public function testThrowsExceptionForInvalidOperationNumber(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Operation number must be positive');

        new Operation(
            operationNumber: 0,
            workCenterId: 'wc-001',
            description: 'Invalid',
        );
    }

    public function testThrowsExceptionForNegativeTime(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Time values cannot be negative');

        new Operation(
            operationNumber: 10,
            workCenterId: 'wc-001',
            description: 'Invalid',
            setupTimeMinutes: -5.0,
        );
    }

    public function testThrowsExceptionForSubcontractWithoutVendor(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Subcontracted operations require a subcontractor ID');

        new Operation(
            operationNumber: 10,
            workCenterId: 'wc-001',
            description: 'Subcontract without vendor',
            type: OperationType::SUBCONTRACT,
        );
    }
}
