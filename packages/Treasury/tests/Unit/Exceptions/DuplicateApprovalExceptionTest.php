<?php

declare(strict_types=1);

namespace Nexus\Treasury\Tests\Unit\Exceptions;

use Nexus\Common\ValueObjects\Money;
use Nexus\Treasury\Exceptions\DuplicateApprovalException;
use PHPUnit\Framework\TestCase;

final class DuplicateApprovalExceptionTest extends TestCase
{
    public function test_already_approved_creates_exception(): void
    {
        $exception = DuplicateApprovalException::alreadyApproved('APP-001', 'user-001');

        $this->assertStringContainsString('APP-001', $exception->getMessage());
        $this->assertStringContainsString('user-001', $exception->getMessage());
    }

    public function test_already_exists_creates_exception(): void
    {
        $exception = DuplicateApprovalException::alreadyExists('TXN-001');

        $this->assertStringContainsString('TXN-001', $exception->getMessage());
    }

    public function test_user_already_approved_creates_exception(): void
    {
        $exception = DuplicateApprovalException::userAlreadyApproved('user-001', 'APP-001');

        $this->assertStringContainsString('user-001', $exception->getMessage());
        $this->assertStringContainsString('APP-001', $exception->getMessage());
    }

    public function test_cannot_approve_twice_creates_exception(): void
    {
        $exception = DuplicateApprovalException::cannotApproveTwice('APP-001');

        $this->assertStringContainsString('APP-001', $exception->getMessage());
    }
}
