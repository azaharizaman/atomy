<?php

declare(strict_types=1);

namespace Nexus\Procurement\Tests\Unit\Exceptions;

use Nexus\Procurement\Exceptions\UnauthorizedApprovalException;
use PHPUnit\Framework\TestCase;

final class UnauthorizedApprovalExceptionTest extends TestCase
{
    public function test_cannot_approve_own_requisition_returns_correct_message(): void
    {
        $id = 'req-123';
        $userId = 'user-1';
        $e = UnauthorizedApprovalException::cannotApproveOwnRequisition($id, $userId);
        
        self::assertSame("User '{$userId}' cannot approve requisition '{$id}' - requester cannot approve own requisition.", $e->getMessage());
    }

    public function test_cannot_create_grn_for_own_po_returns_correct_message(): void
    {
        $poId = 'po-1';
        $userId = 'user-1';
        $e = UnauthorizedApprovalException::cannotCreateGrnForOwnPo($poId, $userId);
        
        self::assertSame("User '{$userId}' cannot create GRN for PO '{$poId}' - PO creator cannot create GRN for same PO.", $e->getMessage());
    }

    public function test_cannot_authorize_payment_for_own_grn_returns_correct_message(): void
    {
        $grnId = 'grn-1';
        $userId = 'user-1';
        $e = UnauthorizedApprovalException::cannotAuthorizePaymentForOwnGrn($grnId, $userId);
        
        self::assertSame("User '{$userId}' cannot authorize payment for GRN '{$grnId}' - GRN creator cannot authorize payment.", $e->getMessage());
    }
}
