<?php

declare(strict_types=1);

namespace Nexus\Treasury\Tests\Unit\Exceptions;

use Nexus\Treasury\Exceptions\SegregationOfDutiesViolationException;
use PHPUnit\Framework\TestCase;

final class SegregationOfDutiesViolationExceptionTest extends TestCase
{
    public function test_same_user_cannot_approve_creates_exception(): void
    {
        $exception = SegregationOfDutiesViolationException::sameUserCannotApprove('user-001', 'TXN-001');

        $this->assertStringContainsString('****', $exception->getMessage());
        $this->assertStringContainsString('cannot approve', $exception->getMessage());
        $this->assertStringContainsString('they created', $exception->getMessage());
    }

    public function test_requires_different_approver_creates_exception(): void
    {
        $exception = SegregationOfDutiesViolationException::requiresDifferentApprover('TXN-001', 'user-001');

        $this->assertStringContainsString('****', $exception->getMessage());
        $this->assertStringContainsString('different user', $exception->getMessage());
    }

    public function test_insufficient_approvers_creates_exception(): void
    {
        $exception = SegregationOfDutiesViolationException::insufficientApprovers('TXN-001', 3, 1);

        $this->assertStringContainsString('****', $exception->getMessage());
        $this->assertStringContainsString('3', $exception->getMessage());
        $this->assertStringContainsString('1', $exception->getMessage());
    }

    public function test_same_user_multiple_approvals_creates_exception(): void
    {
        $exception = SegregationOfDutiesViolationException::sameUserMultipleApprovals('user-001', 'TXN-001');

        $this->assertStringContainsString('****', $exception->getMessage());
        $this->assertStringContainsString('already approved', $exception->getMessage());
    }
}
