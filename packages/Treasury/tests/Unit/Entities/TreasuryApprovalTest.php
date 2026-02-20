<?php

declare(strict_types=1);

namespace Nexus\Treasury\Tests\Unit\Entities;

use DateTimeImmutable;
use Nexus\Treasury\Entities\TreasuryApproval;
use Nexus\Treasury\Enums\ApprovalStatus;
use PHPUnit\Framework\TestCase;

final class TreasuryApprovalTest extends TestCase
{
    public function test_creates_approval_with_required_fields(): void
    {
        $now = new DateTimeImmutable();
        
        $approval = new TreasuryApproval(
            id: 'TRE-APP-001',
            tenantId: 'tenant-001',
            transactionType: 'payment',
            transactionId: 'TXN-001',
            transactionReference: 'REF-001',
            amount: \Nexus\Common\ValueObjects\Money::of(10000, 'USD'),
            description: 'Test approval',
            status: ApprovalStatus::PENDING,
            requestedBy: 'user-001',
            requestedAt: $now,
            approvers: [],
            approvedBy: null,
            approvedAt: null,
            rejectedBy: null,
            rejectedAt: null,
            rejectionReason: null,
            approvalNotes: null,
            expiresAt: $now->modify('+7 days'),
            createdAt: $now,
            updatedAt: $now
        );
        
        $this->assertEquals('TRE-APP-001', $approval->getId());
        $this->assertEquals('tenant-001', $approval->getTenantId());
        $this->assertEquals('payment', $approval->getTransactionType());
        $this->assertEquals('TXN-001', $approval->getTransactionId());
        $this->assertEquals('user-001', $approval->getRequestedBy());
        $this->assertTrue($approval->isPending());
    }

    public function test_is_pending_returns_true_when_status_is_pending(): void
    {
        $approval = $this->createApproval(ApprovalStatus::PENDING);
        
        $this->assertTrue($approval->isPending());
    }

    public function test_is_approved_returns_true_when_status_is_approved(): void
    {
        $approval = $this->createApproval(ApprovalStatus::APPROVED);
        
        $this->assertTrue($approval->isApproved());
    }

    public function test_is_rejected_returns_true_when_status_is_rejected(): void
    {
        $approval = $this->createApproval(ApprovalStatus::REJECTED);
        
        $this->assertTrue($approval->isRejected());
    }

    public function test_is_expired_returns_true_when_expires_at_is_in_past(): void
    {
        $pastDate = new DateTimeImmutable('-1 day');
        
        $approval = $this->createApproval(ApprovalStatus::PENDING, $pastDate);
        
        $this->assertTrue($approval->isExpired());
    }

    public function test_is_expired_returns_false_when_expires_at_is_null(): void
    {
        $approval = $this->createApproval(ApprovalStatus::PENDING, null);
        
        $this->assertFalse($approval->isExpired());
    }

    public function test_is_expired_returns_false_when_expires_at_is_in_future(): void
    {
        $futureDate = new DateTimeImmutable('+7 days');
        
        $approval = $this->createApproval(ApprovalStatus::PENDING, $futureDate);
        
        $this->assertFalse($approval->isExpired());
    }

    private function createApproval(
        ApprovalStatus $status,
        ?DateTimeImmutable $expiresAt = null
    ): TreasuryApproval {
        $now = new DateTimeImmutable();
        
        return new TreasuryApproval(
            id: 'TRE-APP-001',
            tenantId: 'tenant-001',
            transactionType: 'payment',
            transactionId: 'TXN-001',
            transactionReference: null,
            amount: \Nexus\Common\ValueObjects\Money::of(10000, 'USD'),
            description: null,
            status: $status,
            requestedBy: 'user-001',
            requestedAt: $now,
            approvers: [],
            approvedBy: null,
            approvedAt: null,
            rejectedBy: null,
            rejectedAt: null,
            rejectionReason: null,
            approvalNotes: null,
            expiresAt: $expiresAt,
            createdAt: $now,
            updatedAt: $now
        );
    }
}
