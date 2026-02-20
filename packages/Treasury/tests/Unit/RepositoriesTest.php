<?php

declare(strict_types=1);

namespace Nexus\Treasury\Tests\Unit;

use Nexus\Treasury\Enums\ApprovalStatus;
use Nexus\Treasury\Enums\TreasuryStatus;
use Nexus\Treasury\Models\AuthorizationLimit;
use Nexus\Treasury\Models\TreasuryApproval;
use Nexus\Treasury\Models\TreasuryPolicy;
use Nexus\Treasury\Repositories\InMemoryAuthorizationLimitRepository;
use Nexus\Treasury\Repositories\InMemoryTreasuryApprovalRepository;
use Nexus\Treasury\Repositories\InMemoryTreasuryPolicyRepository;
use PHPUnit\Framework\TestCase;

class RepositoriesTest extends TestCase
{
    public function testTreasuryPolicyRepositoryFindById(): void
    {
        $repository = new InMemoryTreasuryPolicyRepository();
        
        $policy = new TreasuryPolicy(
            id: 'policy-1',
            tenantId: 'tenant-1',
            name: 'Test Policy',
            description: 'Test Description',
            status: TreasuryStatus::ACTIVE,
            minimumCashBalance: 10000.00,
            minimumCashBalanceCurrency: 'USD',
            maximumSingleTransaction: 50000.00,
            maximumSingleTransactionCurrency: 'USD',
            approvalRequired: true,
            approvalThreshold: 10000.00,
            approvalThresholdCurrency: 'USD',
            createdAt: new \DateTimeImmutable(),
            updatedAt: new \DateTimeImmutable(),
        );
        
        $repository->save($policy);
        
        $found = $repository->findById('policy-1');
        
        $this->assertNotNull($found);
        $this->assertEquals('policy-1', $found->getId());
        $this->assertEquals('Test Policy', $found->getName());
    }

    public function testTreasuryPolicyRepositoryFindByIdNotFound(): void
    {
        $repository = new InMemoryTreasuryPolicyRepository();
        
        $found = $repository->findById('non-existent');
        
        $this->assertNull($found);
    }

    public function testTreasuryPolicyRepositoryDelete(): void
    {
        $repository = new InMemoryTreasuryPolicyRepository();
        
        $policy = new TreasuryPolicy(
            id: 'policy-1',
            tenantId: 'tenant-1',
            name: 'Test Policy',
            description: null,
            status: TreasuryStatus::ACTIVE,
            minimumCashBalance: 10000.00,
            minimumCashBalanceCurrency: 'USD',
            maximumSingleTransaction: 50000.00,
            maximumSingleTransactionCurrency: 'USD',
            approvalRequired: false,
            approvalThreshold: 0,
            approvalThresholdCurrency: 'USD',
            createdAt: new \DateTimeImmutable(),
            updatedAt: new \DateTimeImmutable(),
        );
        
        $repository->save($policy);
        $repository->delete('policy-1');
        
        $found = $repository->findById('policy-1');
        
        $this->assertNull($found);
    }

    public function testAuthorizationLimitRepositoryFindByUserId(): void
    {
        $repository = new InMemoryAuthorizationLimitRepository();
        
        $limit = new AuthorizationLimit(
            id: 'limit-1',
            tenantId: 'tenant-1',
            userId: 'user-1',
            roleId: null,
            amount: 10000.00,
            currency: 'USD',
            transactionType: null,
            isActive: true,
            createdAt: new \DateTimeImmutable(),
            updatedAt: new \DateTimeImmutable(),
        );
        
        $repository->save($limit);
        
        $found = $repository->findByUserId('user-1');
        
        $this->assertCount(1, $found);
        $this->assertEquals('user-1', $found[0]->getUserId());
    }

    public function testAuthorizationLimitRepositoryFindByRoleId(): void
    {
        $repository = new InMemoryAuthorizationLimitRepository();
        
        $limit = new AuthorizationLimit(
            id: 'limit-1',
            tenantId: 'tenant-1',
            userId: null,
            roleId: 'role-1',
            amount: 10000.00,
            currency: 'USD',
            transactionType: null,
            isActive: true,
            createdAt: new \DateTimeImmutable(),
            updatedAt: new \DateTimeImmutable(),
        );
        
        $repository->save($limit);
        
        $found = $repository->findByRoleId('role-1');
        
        $this->assertCount(1, $found);
        $this->assertEquals('role-1', $found[0]->getRoleId());
    }

    public function testAuthorizationLimitRepositoryFindActiveByAmount(): void
    {
        $repository = new InMemoryAuthorizationLimitRepository();
        
        $limit = new AuthorizationLimit(
            id: 'limit-1',
            tenantId: 'tenant-1',
            userId: 'user-1',
            roleId: null,
            amount: 10000.00,
            currency: 'USD',
            transactionType: null,
            isActive: true,
            createdAt: new \DateTimeImmutable(),
            updatedAt: new \DateTimeImmutable(),
        );
        
        $repository->save($limit);
        
        $found = $repository->findActiveByAmount('tenant-1', 5000.00, 'USD');
        
        $this->assertNotNull($found);
        $this->assertEquals('user-1', $found->getUserId());
    }

    public function testAuthorizationLimitRepositoryFindActiveByAmountNotFound(): void
    {
        $repository = new InMemoryAuthorizationLimitRepository();
        
        $limit = new AuthorizationLimit(
            id: 'limit-1',
            tenantId: 'tenant-1',
            userId: 'user-1',
            roleId: null,
            amount: 10000.00,
            currency: 'USD',
            transactionType: null,
            isActive: true,
            createdAt: new \DateTimeImmutable(),
            updatedAt: new \DateTimeImmutable(),
        );
        
        $repository->save($limit);
        
        // Amount exceeds limit - should return highest limit for comparison
        $found = $repository->findActiveByAmount('tenant-1', 20000.00, 'USD');
        
        $this->assertNotNull($found);
        $this->assertEquals(10000.00, $found->getAmount());
    }

    public function testAuthorizationLimitRepositoryDelete(): void
    {
        $repository = new InMemoryAuthorizationLimitRepository();
        
        $limit = new AuthorizationLimit(
            id: 'limit-1',
            tenantId: 'tenant-1',
            userId: 'user-1',
            roleId: null,
            amount: 10000.00,
            currency: 'USD',
            transactionType: null,
            isActive: true,
            createdAt: new \DateTimeImmutable(),
            updatedAt: new \DateTimeImmutable(),
        );
        
        $repository->save($limit);
        $repository->delete('limit-1');
        
        $found = $repository->findById('limit-1');
        
        $this->assertNull($found);
    }

    public function testTreasuryApprovalRepositoryFindByTenantId(): void
    {
        $repository = new InMemoryTreasuryApprovalRepository();
        
        $approval = new TreasuryApproval(
            id: 'approval-1',
            tenantId: 'tenant-1',
            transactionType: 'payment',
            amount: 10000.00,
            currency: 'USD',
            description: 'Test payment',
            status: ApprovalStatus::PENDING,
            submittedBy: 'user-1',
            submittedAt: new \DateTimeImmutable(),
            approvedBy: null,
            approvedAt: null,
            rejectedBy: null,
            rejectedAt: null,
            rejectionReason: null,
            comments: null,
        );
        
        $repository->save($approval);
        
        $found = $repository->findByTenantId('tenant-1');
        
        $this->assertCount(1, $found);
        $this->assertEquals('tenant-1', $found[0]->getTenantId());
    }

    public function testTreasuryApprovalRepositoryFindByStatus(): void
    {
        $repository = new InMemoryTreasuryApprovalRepository();
        
        $approval1 = new TreasuryApproval(
            id: 'approval-1',
            tenantId: 'tenant-1',
            transactionType: 'payment',
            amount: 10000.00,
            currency: 'USD',
            description: 'Test payment 1',
            status: ApprovalStatus::PENDING,
            submittedBy: 'user-1',
            submittedAt: new \DateTimeImmutable(),
            approvedBy: null,
            approvedAt: null,
            rejectedBy: null,
            rejectedAt: null,
            rejectionReason: null,
            comments: null,
        );
        
        $approval2 = new TreasuryApproval(
            id: 'approval-2',
            tenantId: 'tenant-1',
            transactionType: 'payment',
            amount: 20000.00,
            currency: 'USD',
            description: 'Test payment 2',
            status: ApprovalStatus::APPROVED,
            submittedBy: 'user-1',
            submittedAt: new \DateTimeImmutable(),
            approvedBy: 'manager-1',
            approvedAt: new \DateTimeImmutable(),
            rejectedBy: null,
            rejectedAt: null,
            rejectionReason: null,
            comments: null,
        );
        
        $repository->save($approval1);
        $repository->save($approval2);
        
        $pending = $repository->findByStatus('tenant-1', ApprovalStatus::PENDING);
        
        $this->assertCount(1, $pending);
        $this->assertEquals(ApprovalStatus::PENDING, $pending[0]->getStatus());
    }

    public function testTreasuryApprovalRepositoryDelete(): void
    {
        $repository = new InMemoryTreasuryApprovalRepository();
        
        $approval = new TreasuryApproval(
            id: 'approval-1',
            tenantId: 'tenant-1',
            transactionType: 'payment',
            amount: 10000.00,
            currency: 'USD',
            description: 'Test payment',
            status: ApprovalStatus::PENDING,
            submittedBy: 'user-1',
            submittedAt: new \DateTimeImmutable(),
            approvedBy: null,
            approvedAt: null,
            rejectedBy: null,
            rejectedAt: null,
            rejectionReason: null,
            comments: null,
        );
        
        $repository->save($approval);
        $repository->delete('approval-1');
        
        $found = $repository->findById('approval-1');
        
        $this->assertNull($found);
    }

    public function testTreasuryApprovalWithStatus(): void
    {
        $approval = new TreasuryApproval(
            id: 'approval-1',
            tenantId: 'tenant-1',
            transactionType: 'payment',
            amount: 10000.00,
            currency: 'USD',
            description: 'Test payment',
            status: ApprovalStatus::PENDING,
            submittedBy: 'user-1',
            submittedAt: new \DateTimeImmutable(),
            approvedBy: null,
            approvedAt: null,
            rejectedBy: null,
            rejectedAt: null,
            rejectionReason: null,
            comments: null,
        );

        $approved = $approval->withStatus(ApprovalStatus::APPROVED);
        
        $this->assertEquals(ApprovalStatus::APPROVED, $approved->getStatus());
    }

    public function testAuthorizationLimitModelGetters(): void
    {
        $now = new \DateTimeImmutable();
        $limit = new AuthorizationLimit(
            id: 'limit-1',
            tenantId: 'tenant-1',
            userId: 'user-1',
            roleId: 'role-1',
            amount: 10000.00,
            currency: 'USD',
            transactionType: 'payment',
            isActive: true,
            createdAt: $now,
            updatedAt: $now,
        );

        $this->assertEquals('limit-1', $limit->getId());
        $this->assertEquals('tenant-1', $limit->getTenantId());
        $this->assertEquals('user-1', $limit->getUserId());
        $this->assertEquals('role-1', $limit->getRoleId());
        $this->assertEquals(10000.00, $limit->getAmount());
        $this->assertEquals('USD', $limit->getCurrency());
        $this->assertEquals('payment', $limit->getTransactionType());
        $this->assertTrue($limit->isActive());
        $this->assertEquals($now, $limit->getCreatedAt());
        $this->assertEquals($now, $limit->getUpdatedAt());
    }

    public function testTreasuryPolicyModelGetters(): void
    {
        $createdAt = new \DateTimeImmutable();
        $updatedAt = new \DateTimeImmutable();
        
        $policy = new TreasuryPolicy(
            id: 'policy-1',
            tenantId: 'tenant-1',
            name: 'Test Policy',
            description: 'Test Description',
            status: TreasuryStatus::ACTIVE,
            minimumCashBalance: 10000.00,
            minimumCashBalanceCurrency: 'USD',
            maximumSingleTransaction: 50000.00,
            maximumSingleTransactionCurrency: 'USD',
            approvalRequired: true,
            approvalThreshold: 10000.00,
            approvalThresholdCurrency: 'USD',
            createdAt: $createdAt,
            updatedAt: $updatedAt,
        );

        $this->assertEquals('policy-1', $policy->getId());
        $this->assertEquals('tenant-1', $policy->getTenantId());
        $this->assertEquals('Test Policy', $policy->getName());
        $this->assertEquals('Test Description', $policy->getDescription());
        $this->assertEquals(TreasuryStatus::ACTIVE, $policy->getStatus());
        $this->assertEquals(10000.00, $policy->getMinimumCashBalance());
        $this->assertEquals('USD', $policy->getMinimumCashBalanceCurrency());
        $this->assertEquals(50000.00, $policy->getMaximumSingleTransaction());
        $this->assertEquals('USD', $policy->getMaximumSingleTransactionCurrency());
        $this->assertTrue($policy->isApprovalRequired());
        $this->assertEquals(10000.00, $policy->getApprovalThreshold());
        $this->assertEquals('USD', $policy->getApprovalThresholdCurrency());
        $this->assertEquals($createdAt, $policy->getCreatedAt());
        $this->assertEquals($updatedAt, $policy->getUpdatedAt());
    }

    public function testTreasuryPolicyWithStatus(): void
    {
        $policy = new TreasuryPolicy(
            id: 'policy-1',
            tenantId: 'tenant-1',
            name: 'Test Policy',
            description: null,
            status: TreasuryStatus::ACTIVE,
            minimumCashBalance: 10000.00,
            minimumCashBalanceCurrency: 'USD',
            maximumSingleTransaction: 50000.00,
            maximumSingleTransactionCurrency: 'USD',
            approvalRequired: false,
            approvalThreshold: 0,
            approvalThresholdCurrency: 'USD',
            createdAt: new \DateTimeImmutable(),
            updatedAt: new \DateTimeImmutable(),
        );

        $suspended = $policy->withStatus(TreasuryStatus::SUSPENDED);
        $this->assertEquals(TreasuryStatus::SUSPENDED, $suspended->getStatus());
    }

    public function testTreasuryApprovalModelGetters(): void
    {
        $submittedAt = new \DateTimeImmutable();
        $approvedAt = new \DateTimeImmutable();
        
        $approval = new TreasuryApproval(
            id: 'approval-1',
            tenantId: 'tenant-1',
            transactionType: 'payment',
            amount: 10000.00,
            currency: 'USD',
            description: 'Test payment',
            status: ApprovalStatus::APPROVED,
            submittedBy: 'user-1',
            submittedAt: $submittedAt,
            approvedBy: 'manager-1',
            approvedAt: $approvedAt,
            rejectedBy: null,
            rejectedAt: null,
            rejectionReason: null,
            comments: 'Approved',
        );

        $this->assertEquals('approval-1', $approval->getId());
        $this->assertEquals('tenant-1', $approval->getTenantId());
        $this->assertEquals('payment', $approval->getTransactionType());
        $this->assertEquals(10000.00, $approval->getAmount());
        $this->assertEquals('USD', $approval->getCurrency());
        $this->assertEquals('Test payment', $approval->getDescription());
        $this->assertEquals(ApprovalStatus::APPROVED, $approval->getStatus());
        $this->assertEquals('user-1', $approval->getSubmittedBy());
        $this->assertEquals($submittedAt, $approval->getSubmittedAt());
        $this->assertEquals('manager-1', $approval->getApprovedBy());
        $this->assertEquals($approvedAt, $approval->getApprovedAt());
        $this->assertNull($approval->getRejectedBy());
        $this->assertNull($approval->getRejectedAt());
        $this->assertNull($approval->getRejectionReason());
        $this->assertEquals('Approved', $approval->getComments());
    }
}
