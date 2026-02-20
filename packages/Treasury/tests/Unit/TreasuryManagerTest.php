<?php

declare(strict_types=1);

namespace Nexus\Treasury\Tests\Unit;

use Nexus\Treasury\Enums\ApprovalStatus;
use Nexus\Treasury\Enums\TreasuryStatus;
use Nexus\Treasury\Repositories\InMemoryAuthorizationLimitRepository;
use Nexus\Treasury\Repositories\InMemoryTreasuryApprovalRepository;
use Nexus\Treasury\Repositories\InMemoryTreasuryPolicyRepository;
use Nexus\Treasury\Services\SimpleSequenceGenerator;
use Nexus\Treasury\Services\TreasuryManager;
use Nexus\Treasury\ValueObjects\AuthorizationLimit;
use Nexus\Treasury\ValueObjects\TreasuryPolicyData;
use PHPUnit\Framework\TestCase;

class TreasuryManagerTest extends TestCase
{
    private TreasuryManager $manager;
    private InMemoryTreasuryPolicyRepository $policyRepository;
    private InMemoryAuthorizationLimitRepository $limitRepository;
    private InMemoryTreasuryApprovalRepository $approvalRepository;
    private SimpleSequenceGenerator $sequenceGenerator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policyRepository = new InMemoryTreasuryPolicyRepository();
        $this->limitRepository = new InMemoryAuthorizationLimitRepository();
        $this->approvalRepository = new InMemoryTreasuryApprovalRepository();
        $this->sequenceGenerator = new SimpleSequenceGenerator();

        $this->manager = new TreasuryManager(
            policyRepository: $this->policyRepository,
            limitRepository: $this->limitRepository,
            approvalRepository: $this->approvalRepository,
            sequenceGenerator: $this->sequenceGenerator,
        );
    }

    public function testCreatePolicy(): void
    {
        $policyData = new TreasuryPolicyData(
            name: 'Default Treasury Policy',
            description: 'Default policy for treasury operations',
            minimumCashBalance: 10000.00,
            minimumCashBalanceCurrency: 'USD',
            maximumSingleTransaction: 50000.00,
            maximumSingleTransactionCurrency: 'USD',
            approvalRequired: true,
            approvalThreshold: 10000.00,
            approvalThresholdCurrency: 'USD',
        );

        $policy = $this->manager->createPolicy('tenant-1', $policyData);

        $this->assertNotEmpty($policy->getId());
        $this->assertEquals('tenant-1', $policy->getTenantId());
        $this->assertEquals('Default Treasury Policy', $policy->getName());
        $this->assertEquals(TreasuryStatus::ACTIVE, $policy->getStatus());
        $this->assertEquals(10000.00, $policy->getMinimumCashBalance());
    }

    public function testGetPolicy(): void
    {
        $policyData = new TreasuryPolicyData(
            name: 'Test Policy',
            description: null,
            minimumCashBalance: 5000.00,
            minimumCashBalanceCurrency: 'EUR',
            maximumSingleTransaction: 25000.00,
            maximumSingleTransactionCurrency: 'EUR',
            approvalRequired: false,
            approvalThreshold: 0,
            approvalThresholdCurrency: 'EUR',
        );

        $created = $this->manager->createPolicy('tenant-1', $policyData);
        $retrieved = $this->manager->getPolicy($created->getId());

        $this->assertEquals($created->getId(), $retrieved->getId());
        $this->assertEquals('Test Policy', $retrieved->getName());
    }

    public function testGetPolicies(): void
    {
        $policyData1 = new TreasuryPolicyData(
            name: 'Policy 1',
            description: null,
            minimumCashBalance: 1000.00,
            minimumCashBalanceCurrency: 'USD',
            maximumSingleTransaction: 5000.00,
            maximumSingleTransactionCurrency: 'USD',
            approvalRequired: false,
            approvalThreshold: 0,
            approvalThresholdCurrency: 'USD',
        );

        $policyData2 = new TreasuryPolicyData(
            name: 'Policy 2',
            description: null,
            minimumCashBalance: 2000.00,
            minimumCashBalanceCurrency: 'USD',
            maximumSingleTransaction: 10000.00,
            maximumSingleTransactionCurrency: 'USD',
            approvalRequired: false,
            approvalThreshold: 0,
            approvalThresholdCurrency: 'USD',
        );

        $this->manager->createPolicy('tenant-1', $policyData1);
        $this->manager->createPolicy('tenant-1', $policyData2);
        $this->manager->createPolicy('tenant-2', $policyData1);

        $policies = $this->manager->getPolicies('tenant-1');

        $this->assertCount(2, $policies);
    }

    public function testUpdatePolicyStatus(): void
    {
        $policyData = new TreasuryPolicyData(
            name: 'Policy to Update',
            description: null,
            minimumCashBalance: 1000.00,
            minimumCashBalanceCurrency: 'USD',
            maximumSingleTransaction: 5000.00,
            maximumSingleTransactionCurrency: 'USD',
            approvalRequired: false,
            approvalThreshold: 0,
            approvalThresholdCurrency: 'USD',
        );

        $policy = $this->manager->createPolicy('tenant-1', $policyData);
        $this->assertEquals(TreasuryStatus::ACTIVE, $policy->getStatus());

        $this->manager->updatePolicyStatus($policy->getId(), TreasuryStatus::SUSPENDED);

        $updated = $this->manager->getPolicy($policy->getId());
        $this->assertEquals(TreasuryStatus::SUSPENDED, $updated->getStatus());
    }

    public function testCreateAuthorizationLimit(): void
    {
        $limitData = new AuthorizationLimit(
            userId: 'user-1',
            roleId: null,
            amount: 10000.00,
            currency: 'USD',
            transactionType: null,
        );

        $limit = $this->manager->createAuthorizationLimit('tenant-1', $limitData);

        $this->assertNotEmpty($limit->getId());
        $this->assertEquals('tenant-1', $limit->getTenantId());
        $this->assertEquals('user-1', $limit->getUserId());
        $this->assertEquals(10000.00, $limit->getAmount());
        $this->assertTrue($limit->isActive());
    }

    public function testSubmitForApproval(): void
    {
        $approval = $this->manager->submitForApproval(
            tenantId: 'tenant-1',
            transactionType: 'payment',
            amount: 15000.00,
            currency: 'USD',
            description: 'Test payment',
            submittedBy: 'user-1',
        );

        $this->assertNotEmpty($approval->getId());
        $this->assertEquals('tenant-1', $approval->getTenantId());
        $this->assertEquals('payment', $approval->getTransactionType());
        $this->assertEquals(15000.00, $approval->getAmount());
        $this->assertEquals(ApprovalStatus::PENDING, $approval->getStatus());
        $this->assertEquals('user-1', $approval->getSubmittedBy());
    }

    public function testApproveTransaction(): void
    {
        $approval = $this->manager->submitForApproval(
            tenantId: 'tenant-1',
            transactionType: 'payment',
            amount: 15000.00,
            currency: 'USD',
            description: 'Test payment',
            submittedBy: 'user-1',
        );

        $approved = $this->manager->approveTransaction(
            approvalId: $approval->getId(),
            approvedBy: 'manager-1',
            comments: 'Approved',
        );

        $this->assertEquals(ApprovalStatus::APPROVED, $approved->getStatus());
        $this->assertEquals('manager-1', $approved->getApprovedBy());
        $this->assertEquals('Approved', $approved->getComments());
    }

    public function testRejectTransaction(): void
    {
        $approval = $this->manager->submitForApproval(
            tenantId: 'tenant-1',
            transactionType: 'payment',
            amount: 15000.00,
            currency: 'USD',
            description: 'Test payment',
            submittedBy: 'user-1',
        );

        $rejected = $this->manager->rejectTransaction(
            approvalId: $approval->getId(),
            rejectedBy: 'manager-1',
            reason: 'Insufficient documentation',
        );

        $this->assertEquals(ApprovalStatus::REJECTED, $rejected->getStatus());
        $this->assertEquals('manager-1', $rejected->getRejectedBy());
        $this->assertEquals('Insufficient documentation', $rejected->getRejectionReason());
    }

    public function testRequiresApprovalWithPolicyThreshold(): void
    {
        $policyData = new TreasuryPolicyData(
            name: 'Policy with threshold',
            description: null,
            minimumCashBalance: 1000.00,
            minimumCashBalanceCurrency: 'USD',
            maximumSingleTransaction: 50000.00,
            maximumSingleTransactionCurrency: 'USD',
            approvalRequired: true,
            approvalThreshold: 10000.00,
            approvalThresholdCurrency: 'USD',
        );

        $this->manager->createPolicy('tenant-1', $policyData);

        // Amount below threshold
        $this->assertFalse($this->manager->requiresApproval('tenant-1', 5000.00, 'USD'));

        // Amount above threshold
        $this->assertTrue($this->manager->requiresApproval('tenant-1', 15000.00, 'USD'));
    }

    public function testRequiresApprovalWithAuthorizationLimit(): void
    {
        // Create policy that doesn't require approval
        $policyData = new TreasuryPolicyData(
            name: 'Policy without threshold',
            description: null,
            minimumCashBalance: 1000.00,
            minimumCashBalanceCurrency: 'USD',
            maximumSingleTransaction: 50000.00,
            maximumSingleTransactionCurrency: 'USD',
            approvalRequired: false,
            approvalThreshold: 0,
            approvalThresholdCurrency: 'USD',
        );

        $this->manager->createPolicy('tenant-1', $policyData);

        // Create authorization limit
        $limitData = new AuthorizationLimit(
            userId: 'user-1',
            roleId: null,
            amount: 10000.00,
            currency: 'USD',
            transactionType: null,
        );
        $this->manager->createAuthorizationLimit('tenant-1', $limitData);

        // Amount within limit - no approval needed
        $this->assertFalse($this->manager->requiresApproval('tenant-1', 5000.00, 'USD'));

        // Amount exceeds limit - approval needed
        $this->assertTrue($this->manager->requiresApproval('tenant-1', 20000.00, 'USD'));
    }

    public function testRequiresApprovalWithDifferentCurrency(): void
    {
        // Note: Current implementation doesn't filter by currency for policy thresholds
        // This test documents current behavior - policy applies regardless of currency
        $policyData = new TreasuryPolicyData(
            name: 'USD Policy',
            description: null,
            minimumCashBalance: 1000.00,
            minimumCashBalanceCurrency: 'USD',
            maximumSingleTransaction: 50000.00,
            maximumSingleTransactionCurrency: 'USD',
            approvalRequired: true,
            approvalThreshold: 10000.00,
            approvalThresholdCurrency: 'USD',
        );

        $this->manager->createPolicy('tenant-1', $policyData);

        // Current behavior: policy applies to all currencies
        $this->assertTrue($this->manager->requiresApproval('tenant-1', 15000.00, 'EUR'));
    }

    public function testRequiresApprovalNoMatchingLimit(): void
    {
        // Policy without approval requirements
        $policyData = new TreasuryPolicyData(
            name: 'No Approval Policy',
            description: null,
            minimumCashBalance: 1000.00,
            minimumCashBalanceCurrency: 'USD',
            maximumSingleTransaction: 50000.00,
            maximumSingleTransactionCurrency: 'USD',
            approvalRequired: false,
            approvalThreshold: 0,
            approvalThresholdCurrency: 'USD',
        );

        $this->manager->createPolicy('tenant-1', $policyData);

        // No matching limit exists, and no policy requires approval
        $this->assertFalse($this->manager->requiresApproval('tenant-1', 15000.00, 'USD'));
    }

    public function testGetAuthorizationLimitForAmountFound(): void
    {
        $limitData = new AuthorizationLimit(
            userId: 'user-1',
            roleId: null,
            amount: 10000.00,
            currency: 'USD',
            transactionType: null,
        );
        $this->manager->createAuthorizationLimit('tenant-1', $limitData);

        $limit = $this->manager->getAuthorizationLimitForAmount('tenant-1', 5000.00, 'USD');
        $this->assertNotNull($limit);
        $this->assertEquals(10000.00, $limit->getAmount());
    }

    public function testGetAuthorizationLimitForAmountNotFound(): void
    {
        $limitData = new AuthorizationLimit(
            userId: 'user-1',
            roleId: null,
            amount: 10000.00,
            currency: 'USD',
            transactionType: null,
        );
        $this->manager->createAuthorizationLimit('tenant-1', $limitData);

        // Amount exceeds limit - returns highest limit for comparison
        $limit = $this->manager->getAuthorizationLimitForAmount('tenant-1', 20000.00, 'USD');
        $this->assertNotNull($limit);
        $this->assertEquals(10000.00, $limit->getAmount());
    }

    public function testGetPendingApprovals(): void
    {
        $this->manager->submitForApproval(
            tenantId: 'tenant-1',
            transactionType: 'payment',
            amount: 5000.00,
            currency: 'USD',
            description: 'Payment 1',
            submittedBy: 'user-1',
        );

        $this->manager->submitForApproval(
            tenantId: 'tenant-1',
            transactionType: 'payment',
            amount: 10000.00,
            currency: 'USD',
            description: 'Payment 2',
            submittedBy: 'user-2',
        );

        $approvals = $this->manager->getPendingApprovals('user-1');

        $this->assertCount(1, $approvals);
    }

    public function testUpdatePolicy(): void
    {
        $policyData = new TreasuryPolicyData(
            name: 'Original Name',
            description: 'Original Description',
            minimumCashBalance: 1000.00,
            minimumCashBalanceCurrency: 'USD',
            maximumSingleTransaction: 5000.00,
            maximumSingleTransactionCurrency: 'USD',
            approvalRequired: false,
            approvalThreshold: 0,
            approvalThresholdCurrency: 'USD',
        );

        $policy = $this->manager->createPolicy('tenant-1', $policyData);

        $updatedData = new TreasuryPolicyData(
            name: 'Updated Name',
            description: 'Updated Description',
            minimumCashBalance: 2000.00,
            minimumCashBalanceCurrency: 'USD',
            maximumSingleTransaction: 10000.00,
            maximumSingleTransactionCurrency: 'USD',
            approvalRequired: true,
            approvalThreshold: 5000.00,
            approvalThresholdCurrency: 'USD',
        );

        $updated = $this->manager->updatePolicy($policy->getId(), $updatedData);

        $this->assertEquals('Updated Name', $updated->getName());
        $this->assertEquals('Updated Description', $updated->getDescription());
        $this->assertEquals(2000.00, $updated->getMinimumCashBalance());
        $this->assertTrue($updated->isApprovalRequired());
    }

    public function testGetPolicyNotFound(): void
    {
        $this->expectException(\Nexus\Treasury\Exceptions\TreasuryPolicyNotFoundException::class);
        $this->manager->getPolicy('non-existent-id');
    }

    public function testGetApprovalNotFound(): void
    {
        $this->expectException(\Nexus\Treasury\Exceptions\TreasuryException::class);
        $this->manager->getApproval('non-existent-id');
    }

    public function testGetApprovalsByStatus(): void
    {
        $this->manager->submitForApproval(
            tenantId: 'tenant-1',
            transactionType: 'payment',
            amount: 5000.00,
            currency: 'USD',
            description: 'Payment 1',
            submittedBy: 'user-1',
        );

        $this->manager->submitForApproval(
            tenantId: 'tenant-1',
            transactionType: 'payment',
            amount: 10000.00,
            currency: 'USD',
            description: 'Payment 2',
            submittedBy: 'user-1',
        );

        $approvals = $this->manager->getApprovalsByStatus('tenant-1', ApprovalStatus::PENDING);

        $this->assertCount(2, $approvals);
    }
}
