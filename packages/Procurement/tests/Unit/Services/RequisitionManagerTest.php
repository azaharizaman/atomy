<?php

declare(strict_types=1);

namespace Nexus\Procurement\Tests\Unit\Services;

use Nexus\Procurement\Contracts\RequisitionInterface;
use Nexus\Procurement\Contracts\RequisitionRepositoryInterface;
use Nexus\Procurement\Exceptions\InvalidRequisitionDataException;
use Nexus\Procurement\Exceptions\InvalidRequisitionStateException;
use Nexus\Procurement\Exceptions\RequisitionNotFoundException;
use Nexus\Procurement\Exceptions\UnauthorizedApprovalException;
use Nexus\Procurement\Services\RequisitionManager;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class RequisitionManagerTest extends TestCase
{
    private RequisitionRepositoryInterface $repository;
    private LoggerInterface $logger;
    private RequisitionManager $manager;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(RequisitionRepositoryInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->manager = new RequisitionManager($this->repository, $this->logger);
    }

    public function test_create_requisition_delegates_to_repository(): void
    {
        $requisition = $this->createMockRequisition('req-1', 'REQ-001', 'user-1', 'draft');

        $this->repository->expects(self::once())
            ->method('create')
            ->with('tenant-1', 'user-1', self::callback(static function (array $data): bool {
                return isset($data['number']) && isset($data['description']) && isset($data['department']) && isset($data['lines']);
            }))
            ->willReturn($requisition);

        $result = $this->manager->createRequisition('tenant-1', 'user-1', [
            'number' => 'REQ-001',
            'description' => 'Test',
            'department' => 'IT',
            'lines' => [
                ['item_code' => 'A', 'description' => 'Item A', 'quantity' => 1, 'unit' => 'EA', 'estimated_unit_price' => 10],
            ],
        ]);

        self::assertSame($requisition, $result);
    }

    public function test_create_requisition_auto_generates_number_when_missing(): void
    {
        $this->repository->method('generateNextNumber')->with('tenant-1')->willReturn('REQ-AUTO-001');
        $requisition = $this->createMockRequisition('req-1', 'REQ-AUTO-001', 'user-1', 'draft');

        $this->repository->expects(self::once())
            ->method('create')
            ->with('tenant-1', 'user-1', self::callback(static fn(array $d) => ($d['number'] ?? '') === 'REQ-AUTO-001'))
            ->willReturn($requisition);

        $this->manager->createRequisition('tenant-1', 'user-1', [
            'description' => 'Test',
            'department' => 'IT',
            'lines' => [['item_code' => 'A', 'description' => 'Item A', 'quantity' => 1, 'unit' => 'EA', 'estimated_unit_price' => 10]],
        ]);
    }

    public function test_create_requisition_throws_when_no_lines(): void
    {
        $this->expectException(InvalidRequisitionDataException::class);

        $this->manager->createRequisition('tenant-1', 'user-1', [
            'number' => 'REQ-001',
            'description' => 'Test',
            'department' => 'IT',
            'lines' => [],
        ]);
    }

    public function test_submit_for_approval_throws_when_not_found(): void
    {
        $this->repository->method('findById')->with('tenant-1', 'req-1')->willReturn(null);

        $this->expectException(RequisitionNotFoundException::class);

        $this->manager->submitForApproval('tenant-1', 'req-1');
    }

    public function test_submit_for_approval_throws_when_status_not_draft(): void
    {
        $req = $this->createMockRequisition('req-1', 'REQ-001', 'user-1', 'approved');

        $this->repository->method('findById')->with('tenant-1', 'req-1')->willReturn($req);

        $this->expectException(InvalidRequisitionStateException::class);

        $this->manager->submitForApproval('tenant-1', 'req-1');
    }

    public function test_approve_requisition_throws_when_requester_approves_own(): void
    {
        $req = $this->createMockRequisition('req-1', 'REQ-001', 'user-1', 'pending_approval');

        $this->repository->method('findById')->with('tenant-1', 'req-1')->willReturn($req);

        $this->expectException(UnauthorizedApprovalException::class);

        $this->manager->approveRequisition('tenant-1', 'req-1', 'user-1');
    }

    public function test_approve_requisition_delegates_when_authorized(): void
    {
        $req = $this->createMockRequisition('req-1', 'REQ-001', 'user-1', 'pending_approval');
        $approved = $this->createMockRequisition('req-1', 'REQ-001', 'user-1', 'approved');

        $this->repository->method('findById')->with('tenant-1', 'req-1')->willReturn($req);
        $this->repository->expects(self::once())
            ->method('approve')
            ->with('tenant-1', 'req-1', 'approver-2')
            ->willReturn($approved);

        $result = $this->manager->approveRequisition('tenant-1', 'req-1', 'approver-2');

        self::assertSame($approved, $result);
    }

    public function test_get_requisition_throws_when_not_found(): void
    {
        $this->repository->method('findById')->with('tenant-1', 'req-1')->willReturn(null);

        $this->expectException(RequisitionNotFoundException::class);

        $this->manager->getRequisition('tenant-1', 'req-1');
    }

    public function test_submit_for_approval_delegates_when_draft(): void
    {
        $req = $this->createMockRequisition('req-1', 'REQ-001', 'user-1', 'draft');
        $updated = $this->createMockRequisition('req-1', 'REQ-001', 'user-1', 'pending_approval');

        $this->repository->method('findById')->with('tenant-1', 'req-1')->willReturn($req);
        $this->repository->expects(self::once())
            ->method('updateStatus')
            ->with('tenant-1', 'req-1', 'pending_approval')
            ->willReturn($updated);

        $result = $this->manager->submitForApproval('tenant-1', 'req-1');

        self::assertSame($updated, $result);
    }

    public function test_reject_requisition_delegates_when_pending_approval(): void
    {
        $req = $this->createMockRequisition('req-1', 'REQ-001', 'user-1', 'pending_approval');
        $rejected = $this->createMockRequisition('req-1', 'REQ-001', 'user-1', 'rejected');

        $this->repository->method('findById')->with('tenant-1', 'req-1')->willReturn($req);
        $this->repository->expects(self::once())
            ->method('reject')
            ->with('tenant-1', 'req-1', 'approver-2', 'Not needed')
            ->willReturn($rejected);

        $result = $this->manager->rejectRequisition('tenant-1', 'req-1', 'approver-2', 'Not needed');

        self::assertSame($rejected, $result);
    }

    public function test_mark_as_converted_delegates_when_approved(): void
    {
        $req = $this->createMockRequisition('req-1', 'REQ-001', 'user-1', 'approved', false);
        $converted = $this->createMockRequisition('req-1', 'REQ-001', 'user-1', 'approved', true);
        $po = $this->createMock(\Nexus\Procurement\Contracts\PurchaseOrderInterface::class);
        $po->method('getId')->willReturn('po-1');
        $po->method('getPoNumber')->willReturn('PO-001');

        $this->repository->method('findById')->with('tenant-1', 'req-1')->willReturn($req);
        $this->repository->expects(self::once())
            ->method('markAsConverted')
            ->with('tenant-1', 'req-1', 'po-1')
            ->willReturn($converted);

        $result = $this->manager->markAsConverted('tenant-1', 'req-1', $po);

        self::assertSame($converted, $result);
    }

    public function test_get_requisitions_for_tenant_delegates(): void
    {
        $reqs = [$this->createMockRequisition('req-1', 'REQ-001', 'user-1', 'draft')];

        $this->repository->expects(self::once())
            ->method('findByTenantId')
            ->with('tenant-1', [])
            ->willReturn($reqs);

        $result = $this->manager->getRequisitionsForTenant('tenant-1');

        self::assertSame($reqs, $result);
    }

    public function test_get_requisitions_by_status_delegates(): void
    {
        $reqs = [$this->createMockRequisition('req-1', 'REQ-001', 'user-1', 'approved')];

        $this->repository->expects(self::once())
            ->method('findByStatus')
            ->with('tenant-1', 'approved')
            ->willReturn($reqs);

        $result = $this->manager->getRequisitionsByStatus('tenant-1', 'approved');

        self::assertSame($reqs, $result);
    }

    private function createMockRequisition(string $id, string $number, string $requesterId, string $status, bool $isConverted = false): RequisitionInterface
    {
        $req = $this->createMock(RequisitionInterface::class);
        $req->method('getId')->willReturn($id);
        $req->method('getRequisitionNumber')->willReturn($number);
        $req->method('getRequesterId')->willReturn($requesterId);
        $req->method('getStatus')->willReturn($status);
        $req->method('getTotalEstimate')->willReturn(100.0);
        $req->method('getLines')->willReturn([]);
        $req->method('isConverted')->willReturn($isConverted);

        return $req;
    }
}
