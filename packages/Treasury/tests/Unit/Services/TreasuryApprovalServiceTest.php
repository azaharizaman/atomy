<?php

declare(strict_types=1);

namespace Nexus\Treasury\Tests\Unit\Services;

use DateTimeImmutable;
use Nexus\Common\ValueObjects\Money;
use Nexus\Treasury\Contracts\AuthorizationMatrixQueryInterface;
use Nexus\Treasury\Contracts\AuthorizationMatrixPersistInterface;
use Nexus\Treasury\Contracts\TreasuryApprovalQueryInterface;
use Nexus\Treasury\Contracts\TreasuryApprovalPersistInterface;
use Nexus\Treasury\Contracts\TreasuryPolicyQueryInterface;
use Nexus\Treasury\Entities\TreasuryApproval;
use Nexus\Treasury\Enums\ApprovalStatus;
use Nexus\Treasury\Exceptions\DuplicateApprovalException;
use Nexus\Treasury\Exceptions\SegregationOfDutiesViolationException;
use Nexus\Treasury\Services\AuthorizationMatrixService;
use Nexus\Treasury\Services\TreasuryApprovalService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class TreasuryApprovalServiceTest extends TestCase
{
    private TreasuryApprovalService $service;
    private MockObject $query;
    private MockObject $persist;
    private MockObject $policyQuery;
    private AuthorizationMatrixService $authMatrixService;

    protected function setUp(): void
    {
        $this->query = $this->createMock(TreasuryApprovalQueryInterface::class);
        $this->persist = $this->createMock(TreasuryApprovalPersistInterface::class);
        $this->policyQuery = $this->createMock(TreasuryPolicyQueryInterface::class);

        $authQuery = $this->createMock(AuthorizationMatrixQueryInterface::class);
        $authPersist = $this->createMock(AuthorizationMatrixPersistInterface::class);

        $mockLimit = new \Nexus\Treasury\Entities\AuthorizationLimit(
            id: 'TRE-AUTH-001',
            tenantId: 'tenant-001',
            userId: 'user-001',
            roleId: null,
            transactionType: 'payment',
            approvalLimit: Money::of(100000, 'USD'),
            dailyLimit: null,
            weeklyLimit: null,
            monthlyLimit: null,
            requiresDualApproval: false,
            effectiveFrom: new DateTimeImmutable(),
            effectiveTo: null,
            createdAt: new DateTimeImmutable(),
            updatedAt: new DateTimeImmutable()
        );

        $authQuery->method('findEffectiveForUser')->willReturn($mockLimit);
        $authQuery->method('canAuthorize')->willReturn(true);
        $authQuery->method('findHighestLimitForUser')->willReturn($mockLimit);

        $this->authMatrixService = new AuthorizationMatrixService(
            $authQuery,
            $authPersist,
            null,
            new NullLogger()
        );

        $this->service = new TreasuryApprovalService(
            $this->query,
            $this->persist,
            $this->policyQuery,
            $this->authMatrixService,
            null,
            new NullLogger()
        );
    }

    public function test_submit_creates_new_approval(): void
    {
        $this->query
            ->expects($this->once())
            ->method('findByTransactionId')
            ->with('TXN-001')
            ->willReturn(null);

        $this->persist
            ->expects($this->once())
            ->method('save');

        $result = $this->service->submit(
            'tenant-001',
            'payment',
            'TXN-001',
            Money::of(10000, 'USD'),
            'user-001',
            'Test approval'
        );

        $this->assertEquals('tenant-001', $result->getTenantId());
        $this->assertEquals('payment', $result->getTransactionType());
        $this->assertEquals('TXN-001', $result->getTransactionId());
        $this->assertTrue($result->isPending());
    }

    public function test_submit_throws_exception_when_pending_approval_exists(): void
    {
        $existingApproval = $this->createMockApproval(ApprovalStatus::PENDING);

        $this->query
            ->expects($this->once())
            ->method('findByTransactionId')
            ->with('TXN-001')
            ->willReturn($existingApproval);

        $this->expectException(DuplicateApprovalException::class);

        $this->service->submit(
            'tenant-001',
            'payment',
            'TXN-001',
            Money::of(10000, 'USD'),
            'user-001'
        );
    }

    public function test_approve_approves_pending_approval(): void
    {
        $approval = $this->createMockApproval(ApprovalStatus::PENDING);

        $this->query
            ->expects($this->once())
            ->method('findOrFail')
            ->with('TRE-APP-001')
            ->willReturn($approval);

        $this->persist
            ->expects($this->once())
            ->method('save');

        $result = $this->service->approve('TRE-APP-001', 'user-002', 'Approved');

        $this->assertTrue($result->isApproved());
        $this->assertEquals('user-002', $result->getApprovedBy());
    }

    public function test_approve_throws_exception_when_same_user_tries_to_approve(): void
    {
        $approval = $this->createMockApproval(ApprovalStatus::PENDING, 'user-001');

        $this->query
            ->expects($this->once())
            ->method('findOrFail')
            ->with('TRE-APP-001')
            ->willReturn($approval);

        $this->expectException(SegregationOfDutiesViolationException::class);

        $this->service->approve('TRE-APP-001', 'user-001', 'Approved');
    }

    public function test_reject_rejects_pending_approval(): void
    {
        $approval = $this->createMockApproval(ApprovalStatus::PENDING);

        $this->query
            ->expects($this->once())
            ->method('findOrFail')
            ->with('TRE-APP-001')
            ->willReturn($approval);

        $this->persist
            ->expects($this->once())
            ->method('save');

        $result = $this->service->reject('TRE-APP-001', 'user-002', 'Insufficient budget');

        $this->assertTrue($result->isRejected());
        $this->assertEquals('user-002', $result->getRejectedBy());
        $this->assertEquals('Insufficient budget', $result->getRejectionReason());
    }

    public function test_get_pending_approvals_returns_pending_list(): void
    {
        $pendingApproval = $this->createMockApproval(ApprovalStatus::PENDING);

        $this->query
            ->expects($this->once())
            ->method('findPendingByTenantId')
            ->with('tenant-001')
            ->willReturn([$pendingApproval]);

        $result = $this->service->getPendingApprovals('tenant-001');

        $this->assertCount(1, $result);
    }

    public function test_is_approved_returns_true_when_approved(): void
    {
        $approvedApproval = $this->createMockApproval(ApprovalStatus::APPROVED);

        $this->query
            ->expects($this->once())
            ->method('findByTransactionId')
            ->with('TXN-001')
            ->willReturn($approvedApproval);

        $result = $this->service->isApproved('TXN-001');

        $this->assertTrue($result);
    }

    public function test_is_approved_returns_false_when_no_approval(): void
    {
        $this->query
            ->expects($this->once())
            ->method('findByTransactionId')
            ->with('TXN-001')
            ->willReturn(null);

        $result = $this->service->isApproved('TXN-001');

        $this->assertFalse($result);
    }

    public function test_get_returns_approval_by_id(): void
    {
        $approval = $this->createMockApproval(ApprovalStatus::PENDING);

        $this->query
            ->expects($this->once())
            ->method('findOrFail')
            ->with('TRE-APP-001')
            ->willReturn($approval);

        $result = $this->service->get('TRE-APP-001');

        $this->assertEquals($approval, $result);
    }

    public function test_is_pending_returns_true_when_pending(): void
    {
        $pendingApproval = $this->createMockApproval(ApprovalStatus::PENDING);

        $this->query
            ->expects($this->once())
            ->method('findByTransactionId')
            ->with('TXN-001')
            ->willReturn($pendingApproval);

        $result = $this->service->isPending('TXN-001');

        $this->assertTrue($result);
    }

    public function test_is_pending_returns_false_when_not_pending(): void
    {
        $approvedApproval = $this->createMockApproval(ApprovalStatus::APPROVED);

        $this->query
            ->expects($this->once())
            ->method('findByTransactionId')
            ->with('TXN-001')
            ->willReturn($approvedApproval);

        $result = $this->service->isPending('TXN-001');

        $this->assertFalse($result);
    }

    public function test_reject_throws_exception_when_already_processed(): void
    {
        $approval = $this->createMockApproval(ApprovalStatus::APPROVED);

        $this->query
            ->expects($this->once())
            ->method('findOrFail')
            ->with('TRE-APP-001')
            ->willReturn($approval);

        $this->expectException(DuplicateApprovalException::class);

        $this->service->reject('TRE-APP-001', 'user-002', 'Reason');
    }

    public function test_get_pending_for_approver_returns_list(): void
    {
        $pendingApproval = $this->createMockApproval(ApprovalStatus::PENDING);

        $this->query
            ->expects($this->once())
            ->method('findPendingByApprover')
            ->with('user-001')
            ->willReturn([$pendingApproval]);

        $result = $this->service->getPendingForApprover('user-001');

        $this->assertCount(1, $result);
    }

    public function test_get_by_transaction_returns_approval(): void
    {
        $approval = $this->createMockApproval(ApprovalStatus::PENDING);

        $this->query
            ->expects($this->once())
            ->method('findByTransactionId')
            ->with('TXN-001')
            ->willReturn($approval);

        $result = $this->service->getByTransaction('TXN-001');

        $this->assertNotNull($result);
        $this->assertEquals('TXN-001', $result->getTransactionId());
    }

    public function test_requires_approval_returns_true_when_policy_requires(): void
    {
        $policy = new \Nexus\Treasury\Entities\TreasuryPolicy(
            id: 'TRE-POL-001',
            tenantId: 'tenant-001',
            name: 'Default Policy',
            minimumCashBalance: Money::of(10000, 'USD'),
            maximumSingleTransaction: Money::of(100000, 'USD'),
            approvalThreshold: Money::of(5000, 'USD'),
            approvalRequired: true,
            status: \Nexus\Treasury\Enums\TreasuryStatus::ACTIVE,
            effectiveFrom: new DateTimeImmutable(),
            effectiveTo: null,
            description: null,
            createdAt: new DateTimeImmutable(),
            updatedAt: new DateTimeImmutable()
        );

        $this->policyQuery
            ->expects($this->once())
            ->method('findEffectiveForDate')
            ->willReturn($policy);

        $result = $this->service->requiresApproval('tenant-001', Money::of(10000, 'USD'));

        $this->assertTrue($result);
    }

    public function test_requires_approval_returns_false_when_below_threshold(): void
    {
        $policy = new \Nexus\Treasury\Entities\TreasuryPolicy(
            id: 'TRE-POL-001',
            tenantId: 'tenant-001',
            name: 'Default Policy',
            minimumCashBalance: Money::of(10000, 'USD'),
            maximumSingleTransaction: Money::of(100000, 'USD'),
            approvalThreshold: Money::of(50000, 'USD'),
            approvalRequired: true,
            status: \Nexus\Treasury\Enums\TreasuryStatus::ACTIVE,
            effectiveFrom: new DateTimeImmutable(),
            effectiveTo: null,
            description: null,
            createdAt: new DateTimeImmutable(),
            updatedAt: new DateTimeImmutable()
        );

        $this->policyQuery
            ->expects($this->once())
            ->method('findEffectiveForDate')
            ->willReturn($policy);

        $result = $this->service->requiresApproval('tenant-001', Money::of(1000, 'USD'));

        $this->assertFalse($result);
    }

    public function test_requires_approval_returns_true_when_no_policy(): void
    {
        $this->policyQuery
            ->expects($this->once())
            ->method('findEffectiveForDate')
            ->willReturn(null);

        $result = $this->service->requiresApproval('tenant-001', Money::of(1000, 'USD'));

        $this->assertTrue($result);
    }

    public function test_expire_approvals_expires_pending_approvals(): void
    {
        $expiredApproval = $this->createMockApproval(ApprovalStatus::PENDING);
        $expiredApproval = new TreasuryApproval(
            id: 'TRE-APP-001',
            tenantId: 'tenant-001',
            transactionType: 'payment',
            transactionId: 'TXN-001',
            transactionReference: null,
            amount: Money::of(10000, 'USD'),
            description: null,
            status: ApprovalStatus::PENDING,
            requestedBy: 'user-001',
            requestedAt: new DateTimeImmutable('-10 days'),
            approvers: [],
            approvedBy: null,
            approvedAt: null,
            rejectedBy: null,
            rejectedAt: null,
            rejectionReason: null,
            approvalNotes: null,
            expiresAt: new DateTimeImmutable('-3 days'),
            createdAt: new DateTimeImmutable('-10 days'),
            updatedAt: new DateTimeImmutable('-10 days')
        );

        $this->query
            ->expects($this->once())
            ->method('findExpired')
            ->willReturn([$expiredApproval]);

        $this->persist
            ->expects($this->once())
            ->method('save');

        $result = $this->service->expireApprovals();

        $this->assertEquals(1, $result);
    }

    public function test_expire_approvals_skips_non_pending(): void
    {
        $expiredApproval = new TreasuryApproval(
            id: 'TRE-APP-001',
            tenantId: 'tenant-001',
            transactionType: 'payment',
            transactionId: 'TXN-001',
            transactionReference: null,
            amount: Money::of(10000, 'USD'),
            description: null,
            status: ApprovalStatus::APPROVED,
            requestedBy: 'user-001',
            requestedAt: new DateTimeImmutable('-10 days'),
            approvers: [],
            approvedBy: 'user-002',
            approvedAt: new DateTimeImmutable('-8 days'),
            rejectedBy: null,
            rejectedAt: null,
            rejectionReason: null,
            approvalNotes: null,
            expiresAt: new DateTimeImmutable('-3 days'),
            createdAt: new DateTimeImmutable('-10 days'),
            updatedAt: new DateTimeImmutable('-8 days')
        );

        $this->query
            ->expects($this->once())
            ->method('findExpired')
            ->willReturn([$expiredApproval]);

        $this->persist
            ->expects($this->never())
            ->method('save');

        $result = $this->service->expireApprovals();

        $this->assertEquals(0, $result);
    }

    public function test_submit_with_transaction_reference(): void
    {
        $this->query
            ->expects($this->once())
            ->method('findByTransactionId')
            ->with('TXN-001')
            ->willReturn(null);

        $this->persist
            ->expects($this->once())
            ->method('save');

        $result = $this->service->submit(
            'tenant-001',
            'payment',
            'TXN-001',
            Money::of(10000, 'USD'),
            'user-001',
            'Test approval',
            'REF-001'
        );

        $this->assertEquals('REF-001', $result->getTransactionReference());
        $this->assertEquals('Test approval', $result->getDescription());
    }

    private function createMockApproval(
        ApprovalStatus $status,
        string $requestedBy = 'user-001'
    ): TreasuryApproval {
        $now = new DateTimeImmutable();

        return new TreasuryApproval(
            id: 'TRE-APP-001',
            tenantId: 'tenant-001',
            transactionType: 'payment',
            transactionId: 'TXN-001',
            transactionReference: null,
            amount: Money::of(10000, 'USD'),
            description: null,
            status: $status,
            requestedBy: $requestedBy,
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
    }
}
