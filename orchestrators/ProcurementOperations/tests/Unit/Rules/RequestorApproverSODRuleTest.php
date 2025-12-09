<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Tests\Unit\Rules;

use Nexus\ProcurementOperations\DTOs\SODValidationRequest;
use Nexus\ProcurementOperations\Enums\SODConflictType;
use Nexus\ProcurementOperations\Rules\RequestorApproverSODRule;
use PHPUnit\Framework\TestCase;

final class RequestorApproverSODRuleTest extends TestCase
{
    private RequestorApproverSODRule $rule;

    protected function setUp(): void
    {
        $this->rule = new RequestorApproverSODRule();
    }

    public function test_passes_when_approver_different_from_requestor(): void
    {
        $request = SODValidationRequest::forRequisitionApproval(
            approverId: 'user-approver',
            requisitionId: 'req-123',
            requestorId: 'user-requestor',
            approverRoles: ['procurement.approver'],
        );

        $result = $this->rule->check($request);

        $this->assertTrue($result->passed);
        $this->assertEmpty($result->violations);
    }

    public function test_fails_when_approver_same_as_requestor(): void
    {
        $request = SODValidationRequest::forRequisitionApproval(
            approverId: 'user-same',
            requisitionId: 'req-123',
            requestorId: 'user-same',
            approverRoles: ['procurement.approver', 'procurement.requestor'],
        );

        $result = $this->rule->check($request);

        $this->assertFalse($result->passed);
        $this->assertCount(1, $result->violations);
        $this->assertSame(
            SODConflictType::REQUESTOR_APPROVER,
            $result->violations[0]->conflictType
        );
    }

    public function test_passes_for_non_approval_actions(): void
    {
        $request = new SODValidationRequest(
            userId: 'user-1',
            action: 'create_requisition',
            entityType: 'requisition',
            entityId: 'req-123',
        );

        $result = $this->rule->check($request);

        $this->assertTrue($result->passed);
    }

    public function test_passes_when_requestor_id_not_in_metadata(): void
    {
        $request = new SODValidationRequest(
            userId: 'user-1',
            action: 'approve_requisition',
            entityType: 'requisition',
            entityId: 'req-123',
            metadata: [], // No requestor_id
        );

        $result = $this->rule->check($request);

        $this->assertTrue($result->passed);
    }

    public function test_applies_to_po_approval(): void
    {
        $request = new SODValidationRequest(
            userId: 'user-same',
            action: 'approve_po',
            entityType: 'purchase_order',
            entityId: 'po-123',
            metadata: ['requestor_id' => 'user-same'],
        );

        $result = $this->rule->check($request);

        $this->assertFalse($result->passed);
    }

    public function test_violation_has_correct_risk_level(): void
    {
        $request = SODValidationRequest::forRequisitionApproval(
            approverId: 'user-same',
            requisitionId: 'req-123',
            requestorId: 'user-same',
            approverRoles: ['procurement.approver'],
        );

        $result = $this->rule->check($request);

        $this->assertSame('HIGH', $result->violations[0]->conflictType->getRiskLevel());
    }
}
