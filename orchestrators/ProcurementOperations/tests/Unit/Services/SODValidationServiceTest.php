<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Tests\Unit\Services;

use Nexus\Hrm\Contracts\EmployeeQueryInterface;
use Nexus\Identity\Contracts\RoleQueryInterface;
use Nexus\Identity\Contracts\UserQueryInterface;
use Nexus\Procurement\Contracts\PurchaseOrderQueryInterface;
use Nexus\Procurement\Contracts\RequisitionQueryInterface;
use Nexus\ProcurementOperations\DataProviders\SODComplianceDataProvider;
use Nexus\ProcurementOperations\DTOs\SODValidationRequest;
use Nexus\ProcurementOperations\Enums\SODConflictType;
use Nexus\ProcurementOperations\Services\SODValidationService;
use Nexus\ProcurementOperations\Services\SODViolationException;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\NullLogger;

final class SODValidationServiceTest extends TestCase
{
    private SODValidationService $service;
    private SODComplianceDataProvider $dataProvider;
    private EventDispatcherInterface $eventDispatcher;

    protected function setUp(): void
    {
        $userQuery = $this->createMock(UserQueryInterface::class);
        $roleQuery = $this->createMock(RoleQueryInterface::class);
        $roleQuery->method('getRoleIdentifiersForUser')->willReturn([]);

        $requisitionQuery = $this->createMock(RequisitionQueryInterface::class);
        $poQuery = $this->createMock(PurchaseOrderQueryInterface::class);

        $this->dataProvider = new SODComplianceDataProvider(
            $userQuery,
            $roleQuery,
            $requisitionQuery,
            $poQuery,
        );

        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->service = new SODValidationService(
            dataProvider: $this->dataProvider,
            eventDispatcher: $this->eventDispatcher,
            logger: new NullLogger(),
            blockOnHighRisk: true,
        );
    }

    public function test_validate_passes_when_no_violations(): void
    {
        $request = new SODValidationRequest(
            userId: 'user-approver',
            action: 'approve_requisition',
            entityType: 'requisition',
            entityId: 'req-123',
            metadata: ['requestor_id' => 'user-requestor'], // Different user
        );

        $result = $this->service->validate($request);

        $this->assertTrue($result->passed);
        $this->assertEmpty($result->violations);
    }

    public function test_validate_throws_on_high_risk_violation(): void
    {
        $request = SODValidationRequest::forRequisitionApproval(
            approverId: 'user-same',
            requisitionId: 'req-123',
            requestorId: 'user-same',
            approverRoles: ['procurement.approver'],
        );

        $this->expectException(SODViolationException::class);
        $this->expectExceptionMessage('Segregation of Duties violation');

        $this->service->validate($request);
    }

    public function test_validate_returns_result_when_blocking_disabled(): void
    {
        $serviceNoBlock = new SODValidationService(
            dataProvider: $this->dataProvider,
            eventDispatcher: $this->eventDispatcher,
            logger: new NullLogger(),
            blockOnHighRisk: false,
        );

        $request = SODValidationRequest::forRequisitionApproval(
            approverId: 'user-same',
            requisitionId: 'req-123',
            requestorId: 'user-same',
            approverRoles: ['procurement.approver'],
        );

        $result = $serviceNoBlock->validate($request);

        $this->assertFalse($result->passed);
        $this->assertNotEmpty($result->violations);
    }

    public function test_is_action_allowed_returns_true_when_passed(): void
    {
        $allowed = $this->service->isActionAllowed(
            userId: 'user-approver',
            action: 'approve_requisition',
            entityType: 'requisition',
            entityId: 'req-123',
            metadata: ['requestor_id' => 'user-requestor'],
        );

        $this->assertTrue($allowed);
    }

    public function test_is_action_allowed_returns_false_on_violation(): void
    {
        $allowed = $this->service->isActionAllowed(
            userId: 'user-same',
            action: 'approve_requisition',
            entityType: 'requisition',
            entityId: 'req-123',
            metadata: ['requestor_id' => 'user-same'],
        );

        $this->assertFalse($allowed);
    }

    public function test_validate_payment_with_receiver_conflict(): void
    {
        $request = SODValidationRequest::forPaymentProcessing(
            payerId: 'user-same',
            invoiceId: 'inv-123',
            vendorId: 'vendor-1',
            receiverId: 'user-same',
            payerRoles: ['finance.payment_processor'],
        );

        $this->expectException(SODViolationException::class);

        $this->service->validate($request);
    }

    public function test_validate_payment_with_vendor_creator_conflict(): void
    {
        $request = new SODValidationRequest(
            userId: 'user-same',
            action: 'process_payment',
            entityType: 'invoice',
            entityId: 'inv-123',
            conflictsToCheck: [SODConflictType::VENDOR_CREATOR_PAYER],
            metadata: ['vendor_creator_id' => 'user-same'],
        );

        $this->expectException(SODViolationException::class);

        $this->service->validate($request);
    }

    public function test_validate_invoice_matching_conflict(): void
    {
        $serviceNoBlock = new SODValidationService(
            dataProvider: $this->dataProvider,
            eventDispatcher: $this->eventDispatcher,
            logger: new NullLogger(),
            blockOnHighRisk: false, // Invoice matching is LOW risk
        );

        $request = SODValidationRequest::forInvoiceMatching(
            matcherId: 'user-same',
            invoiceId: 'inv-123',
            poCreatorId: 'user-same',
            matcherRoles: ['finance.invoice_matcher'],
        );

        $result = $serviceNoBlock->validate($request);

        $this->assertFalse($result->passed);
        $this->assertSame(
            SODConflictType::PO_CREATOR_INVOICE_MATCHER,
            $result->violations[0]->conflictType
        );
    }

    public function test_dispatches_events_on_violation(): void
    {
        $this->eventDispatcher
            ->expects($this->atLeastOnce())
            ->method('dispatch');

        $serviceNoBlock = new SODValidationService(
            dataProvider: $this->dataProvider,
            eventDispatcher: $this->eventDispatcher,
            logger: new NullLogger(),
            blockOnHighRisk: false,
        );

        $request = SODValidationRequest::forRequisitionApproval(
            approverId: 'user-same',
            requisitionId: 'req-123',
            requestorId: 'user-same',
            approverRoles: [],
        );

        $serviceNoBlock->validate($request);
    }

    public function test_validation_result_has_high_risk_violations(): void
    {
        $serviceNoBlock = new SODValidationService(
            dataProvider: $this->dataProvider,
            eventDispatcher: $this->eventDispatcher,
            logger: new NullLogger(),
            blockOnHighRisk: false,
        );

        $request = SODValidationRequest::forRequisitionApproval(
            approverId: 'user-same',
            requisitionId: 'req-123',
            requestorId: 'user-same',
            approverRoles: [],
        );

        $result = $serviceNoBlock->validate($request);

        $this->assertTrue($result->hasHighRiskViolations());
    }

    public function test_validation_result_summary(): void
    {
        $serviceNoBlock = new SODValidationService(
            dataProvider: $this->dataProvider,
            eventDispatcher: $this->eventDispatcher,
            logger: new NullLogger(),
            blockOnHighRisk: false,
        );

        $request = SODValidationRequest::forRequisitionApproval(
            approverId: 'user-same',
            requisitionId: 'req-123',
            requestorId: 'user-same',
            approverRoles: [],
        );

        $result = $serviceNoBlock->validate($request);
        $summary = $result->toSummary();

        $this->assertFalse($summary['passed']);
        $this->assertSame('user-same', $summary['user_id']);
        $this->assertSame('approve_requisition', $summary['action']);
        $this->assertSame(1, $summary['violation_count']);
        $this->assertSame(1, $summary['high_risk_count']);
    }
}
