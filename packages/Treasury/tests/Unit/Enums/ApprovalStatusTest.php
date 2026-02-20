<?php

declare(strict_types=1);

namespace Nexus\Treasury\Tests\Unit\Enums;

use Nexus\Treasury\Enums\ApprovalStatus;
use PHPUnit\Framework\TestCase;

final class ApprovalStatusTest extends TestCase
{
    public function test_label_returns_correct_string(): void
    {
        $this->assertEquals('Pending', ApprovalStatus::PENDING->label());
        $this->assertEquals('Approved', ApprovalStatus::APPROVED->label());
        $this->assertEquals('Rejected', ApprovalStatus::REJECTED->label());
        $this->assertEquals('Expired', ApprovalStatus::EXPIRED->label());
        $this->assertEquals('Cancelled', ApprovalStatus::CANCELLED->label());
    }

    public function test_is_pending_returns_true_for_pending(): void
    {
        $this->assertTrue(ApprovalStatus::PENDING->isPending());
        $this->assertFalse(ApprovalStatus::APPROVED->isPending());
    }

    public function test_is_approved_returns_true_for_approved(): void
    {
        $this->assertTrue(ApprovalStatus::APPROVED->isApproved());
        $this->assertFalse(ApprovalStatus::PENDING->isApproved());
    }

    public function test_is_rejected_returns_true_for_rejected(): void
    {
        $this->assertTrue(ApprovalStatus::REJECTED->isRejected());
        $this->assertFalse(ApprovalStatus::PENDING->isRejected());
    }

    public function test_is_expired_returns_true_for_expired(): void
    {
        $this->assertTrue(ApprovalStatus::EXPIRED->isExpired());
        $this->assertFalse(ApprovalStatus::PENDING->isExpired());
    }

    public function test_is_cancelled_returns_true_for_cancelled(): void
    {
        $this->assertTrue(ApprovalStatus::CANCELLED->isCancelled());
        $this->assertFalse(ApprovalStatus::PENDING->isCancelled());
    }

    public function test_is_final_returns_true_for_final_states(): void
    {
        $this->assertTrue(ApprovalStatus::APPROVED->isFinal());
        $this->assertTrue(ApprovalStatus::REJECTED->isFinal());
        $this->assertTrue(ApprovalStatus::EXPIRED->isFinal());
        $this->assertTrue(ApprovalStatus::CANCELLED->isFinal());
        $this->assertFalse(ApprovalStatus::PENDING->isFinal());
    }
}
