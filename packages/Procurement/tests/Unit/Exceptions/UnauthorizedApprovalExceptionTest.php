<?php

declare(strict_types=1);

namespace Nexus\Procurement\Tests\Unit\Exceptions;

use Nexus\Procurement\Exceptions\UnauthorizedApprovalException;
use PHPUnit\Framework\TestCase;

final class UnauthorizedApprovalExceptionTest extends TestCase
{
    public function test_cannot_approve_own_requisition_returns_correct_message(): void
    {
        $e = UnauthorizedApprovalException::cannotApproveOwnRequisition('req-1', 'user-1');

        self::assertStringContainsString('req-1', $e->getMessage());
        self::assertStringContainsString('user-1', $e->getMessage());
    }

    public function test_cannot_create_grn_for_own_po_returns_correct_message(): void
    {
        $e = UnauthorizedApprovalException::cannotCreateGrnForOwnPo('po-1', 'user-1');

        self::assertStringContainsString('po-1', $e->getMessage());
        self::assertStringContainsString('user-1', $e->getMessage());
    }

    public function test_cannot_authorize_payment_for_own_grn_returns_correct_message(): void
    {
        $e = UnauthorizedApprovalException::cannotAuthorizePaymentForOwnGrn('grn-1', 'user-1');

        self::assertStringContainsString('grn-1', $e->getMessage());
        self::assertStringContainsString('user-1', $e->getMessage());
    }
}
