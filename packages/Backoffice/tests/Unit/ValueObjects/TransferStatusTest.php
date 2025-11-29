<?php

declare(strict_types=1);

namespace Nexus\Backoffice\Tests\Unit\ValueObjects;

use PHPUnit\Framework\TestCase;
use Nexus\Backoffice\ValueObjects\TransferStatus;

/**
 * Unit tests for TransferStatus enum.
 */
class TransferStatusTest extends TestCase
{
    public function test_pending_status_value(): void
    {
        $this->assertSame('pending', TransferStatus::PENDING->value);
    }

    public function test_approved_status_value(): void
    {
        $this->assertSame('approved', TransferStatus::APPROVED->value);
    }

    public function test_rejected_status_value(): void
    {
        $this->assertSame('rejected', TransferStatus::REJECTED->value);
    }

    public function test_cancelled_status_value(): void
    {
        $this->assertSame('cancelled', TransferStatus::CANCELLED->value);
    }

    public function test_completed_status_value(): void
    {
        $this->assertSame('completed', TransferStatus::COMPLETED->value);
    }

    public function test_is_pending_returns_true_for_pending(): void
    {
        $this->assertTrue(TransferStatus::PENDING->isPending());
    }

    public function test_is_pending_returns_false_for_other_statuses(): void
    {
        $this->assertFalse(TransferStatus::APPROVED->isPending());
        $this->assertFalse(TransferStatus::REJECTED->isPending());
        $this->assertFalse(TransferStatus::CANCELLED->isPending());
        $this->assertFalse(TransferStatus::COMPLETED->isPending());
    }

    public function test_is_approved_returns_true_for_approved(): void
    {
        $this->assertTrue(TransferStatus::APPROVED->isApproved());
    }

    public function test_is_approved_returns_false_for_other_statuses(): void
    {
        $this->assertFalse(TransferStatus::PENDING->isApproved());
        $this->assertFalse(TransferStatus::REJECTED->isApproved());
        $this->assertFalse(TransferStatus::CANCELLED->isApproved());
        $this->assertFalse(TransferStatus::COMPLETED->isApproved());
    }

    public function test_is_rejected_returns_true_for_rejected(): void
    {
        $this->assertTrue(TransferStatus::REJECTED->isRejected());
    }

    public function test_is_rejected_returns_false_for_other_statuses(): void
    {
        $this->assertFalse(TransferStatus::PENDING->isRejected());
        $this->assertFalse(TransferStatus::APPROVED->isRejected());
        $this->assertFalse(TransferStatus::CANCELLED->isRejected());
        $this->assertFalse(TransferStatus::COMPLETED->isRejected());
    }

    public function test_is_completed_returns_true_for_completed(): void
    {
        $this->assertTrue(TransferStatus::COMPLETED->isCompleted());
    }

    public function test_is_completed_returns_false_for_other_statuses(): void
    {
        $this->assertFalse(TransferStatus::PENDING->isCompleted());
        $this->assertFalse(TransferStatus::APPROVED->isCompleted());
        $this->assertFalse(TransferStatus::REJECTED->isCompleted());
        $this->assertFalse(TransferStatus::CANCELLED->isCompleted());
    }

    public function test_is_final_returns_true_for_rejected(): void
    {
        $this->assertTrue(TransferStatus::REJECTED->isFinal());
    }

    public function test_is_final_returns_true_for_cancelled(): void
    {
        $this->assertTrue(TransferStatus::CANCELLED->isFinal());
    }

    public function test_is_final_returns_true_for_completed(): void
    {
        $this->assertTrue(TransferStatus::COMPLETED->isFinal());
    }

    public function test_is_final_returns_false_for_pending(): void
    {
        $this->assertFalse(TransferStatus::PENDING->isFinal());
    }

    public function test_is_final_returns_false_for_approved(): void
    {
        $this->assertFalse(TransferStatus::APPROVED->isFinal());
    }

    public function test_from_valid_value(): void
    {
        $this->assertSame(TransferStatus::PENDING, TransferStatus::from('pending'));
        $this->assertSame(TransferStatus::APPROVED, TransferStatus::from('approved'));
        $this->assertSame(TransferStatus::REJECTED, TransferStatus::from('rejected'));
        $this->assertSame(TransferStatus::CANCELLED, TransferStatus::from('cancelled'));
        $this->assertSame(TransferStatus::COMPLETED, TransferStatus::from('completed'));
    }

    public function test_from_invalid_value_throws_exception(): void
    {
        $this->expectException(\ValueError::class);
        TransferStatus::from('invalid');
    }

    public function test_all_cases_are_defined(): void
    {
        $cases = TransferStatus::cases();
        $this->assertCount(5, $cases);
        $this->assertContains(TransferStatus::PENDING, $cases);
        $this->assertContains(TransferStatus::APPROVED, $cases);
        $this->assertContains(TransferStatus::REJECTED, $cases);
        $this->assertContains(TransferStatus::CANCELLED, $cases);
        $this->assertContains(TransferStatus::COMPLETED, $cases);
    }
}
