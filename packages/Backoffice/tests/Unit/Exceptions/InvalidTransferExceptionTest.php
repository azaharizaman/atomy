<?php

declare(strict_types=1);

namespace Nexus\Backoffice\Tests\Unit\Exceptions;

use Nexus\Backoffice\Exceptions\InvalidTransferException;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for InvalidTransferException.
 */
class InvalidTransferExceptionTest extends TestCase
{
    public function test_pending_transfer_exists_includes_staff_id(): void
    {
        $exception = InvalidTransferException::pendingTransferExists('staff-123');
        $this->assertStringContainsString('staff-123', $exception->getMessage());
    }

    public function test_pending_transfer_exists_indicates_pending(): void
    {
        $exception = InvalidTransferException::pendingTransferExists('staff-123');
        $this->assertStringContainsString('pending', $exception->getMessage());
    }

    public function test_invalid_status_includes_transfer_id(): void
    {
        $exception = InvalidTransferException::invalidStatus('transfer-123', 'approved', 'pending');
        $this->assertStringContainsString('transfer-123', $exception->getMessage());
    }

    public function test_invalid_status_includes_current_status(): void
    {
        $exception = InvalidTransferException::invalidStatus('transfer-123', 'approved', 'pending');
        $this->assertStringContainsString('approved', $exception->getMessage());
    }

    public function test_invalid_status_includes_required_status(): void
    {
        $exception = InvalidTransferException::invalidStatus('transfer-123', 'approved', 'pending');
        $this->assertStringContainsString('pending', $exception->getMessage());
    }

    public function test_retroactive_date_includes_date(): void
    {
        $date = new \DateTimeImmutable('2024-01-15');
        $exception = InvalidTransferException::retroactiveDate($date);
        $this->assertStringContainsString('2024-01-15', $exception->getMessage());
    }

    public function test_retroactive_date_indicates_retroactive_limit(): void
    {
        $date = new \DateTimeImmutable('2024-01-15');
        $exception = InvalidTransferException::retroactiveDate($date);
        $this->assertStringContainsString('retroactive', $exception->getMessage());
        $this->assertStringContainsString('30-day', $exception->getMessage());
    }

    public function test_exception_extends_exception(): void
    {
        $exception = InvalidTransferException::pendingTransferExists('staff-123');
        $this->assertInstanceOf(\Exception::class, $exception);
    }

    public function test_different_invalid_status_transitions(): void
    {
        $exception1 = InvalidTransferException::invalidStatus('t-1', 'completed', 'pending');
        $this->assertStringContainsString('completed', $exception1->getMessage());
        
        $exception2 = InvalidTransferException::invalidStatus('t-2', 'rejected', 'approved');
        $this->assertStringContainsString('rejected', $exception2->getMessage());
    }
}
