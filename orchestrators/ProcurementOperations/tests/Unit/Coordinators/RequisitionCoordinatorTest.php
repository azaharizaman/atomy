<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Tests\Unit\Coordinators;

use Nexus\ProcurementOperations\Coordinators\RequisitionCoordinator;
use Nexus\ProcurementOperations\DTOs\CreateRequisitionRequest;
use Nexus\ProcurementOperations\DTOs\RequisitionResult;
use Nexus\ProcurementOperations\DTOs\SagaResult;
use Nexus\ProcurementOperations\Workflows\RequisitionApprovalWorkflow;
use Nexus\Procurement\Contracts\RequisitionManagerInterface;
use Nexus\Procurement\Contracts\RequisitionQueryInterface;
use Nexus\Procurement\Contracts\RequisitionInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

#[CoversClass(RequisitionCoordinator::class)]
final class RequisitionCoordinatorTest extends TestCase
{
    private RequisitionManagerInterface&MockObject $managerMock;
    private RequisitionQueryInterface&MockObject $queryMock;
    private RequisitionApprovalWorkflow&MockObject $workflowMock;
    private RequisitionCoordinator $coordinator;

    protected function setUp(): void
    {
        $this->managerMock = $this->createMock(RequisitionManagerInterface::class);
        $this->queryMock = $this->createMock(RequisitionQueryInterface::class);
        $this->workflowMock = $this->createMock(RequisitionApprovalWorkflow::class);

        $this->coordinator = new RequisitionCoordinator(
            requisitionManager: $this->managerMock,
            requisitionQuery: $this->queryMock,
            approvalWorkflow: $this->workflowMock,
            logger: new NullLogger()
        );
    }

    #[Test]
    public function create_initiates_workflow(): void
    {
        $request = new CreateRequisitionRequest(
            tenantId: 'tenant-1',
            requestedBy: 'user-1',
            departmentId: 'dept-1',
            lineItems: [['productId' => 'p1', 'quantity' => 1.0, 'estimatedUnitPriceCents' => 100, 'uom' => 'EA']]
        );

        $requisitionMock = $this->createMock(RequisitionInterface::class);
        $requisitionMock->method('getId')->willReturn('req-123');
        $requisitionMock->method('getRequisitionNumber')->willReturn('REQ-001');
        $requisitionMock->method('getTotalAmountCents')->willReturn(100);
        
        $statusMock = new \stdClass();
        $statusMock->value = 'draft';
        $requisitionMock->method('getStatus')->willReturn($statusMock);

        $this->managerMock->expects($this->once())
            ->method('create')
            ->willReturn($requisitionMock);

        $this->workflowMock->expects($this->once())
            ->method('submitForApproval')
            ->willReturn(SagaResult::success('instance-1', 'saga-1'));

        $result = $this->coordinator->create($request);

        $this->assertTrue($result->success);
        $this->assertSame('req-123', $result->requisitionId);
        $this->assertSame('draft', $result->status);
    }
}
