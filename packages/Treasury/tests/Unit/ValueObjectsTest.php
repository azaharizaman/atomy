<?php

declare(strict_types=1);

namespace Nexus\Treasury\Tests\Unit;

use Nexus\Treasury\Enums\ApprovalStatus;
use Nexus\Treasury\Enums\TreasuryStatus;
use Nexus\Treasury\ValueObjects\AuthorizationLimit;
use Nexus\Treasury\ValueObjects\TreasuryPolicyData;
use PHPUnit\Framework\TestCase;

class ValueObjectsTest extends TestCase
{
    public function testTreasuryPolicyDataFromArray(): void
    {
        $data = [
            'name' => 'Test Policy',
            'description' => 'Test Description',
            'minimum_cash_balance' => 10000.00,
            'minimum_cash_balance_currency' => 'USD',
            'maximum_single_transaction' => 50000.00,
            'maximum_single_transaction_currency' => 'USD',
            'approval_required' => true,
            'approval_threshold' => 10000.00,
            'approval_threshold_currency' => 'USD',
        ];

        $policyData = TreasuryPolicyData::fromArray($data);

        $this->assertEquals('Test Policy', $policyData->name);
        $this->assertEquals('Test Description', $policyData->description);
        $this->assertEquals(10000.00, $policyData->minimumCashBalance);
        $this->assertEquals('USD', $policyData->minimumCashBalanceCurrency);
        $this->assertEquals(50000.00, $policyData->maximumSingleTransaction);
        $this->assertEquals('USD', $policyData->maximumSingleTransactionCurrency);
        $this->assertTrue($policyData->approvalRequired);
        $this->assertEquals(10000.00, $policyData->approvalThreshold);
        $this->assertEquals('USD', $policyData->approvalThresholdCurrency);
    }

    public function testTreasuryPolicyDataToArray(): void
    {
        $policyData = new TreasuryPolicyData(
            name: 'Test Policy',
            description: 'Test Description',
            minimumCashBalance: 10000.00,
            minimumCashBalanceCurrency: 'USD',
            maximumSingleTransaction: 50000.00,
            maximumSingleTransactionCurrency: 'USD',
            approvalRequired: true,
            approvalThreshold: 10000.00,
            approvalThresholdCurrency: 'USD',
        );

        $array = $policyData->toArray();

        $this->assertEquals('Test Policy', $array['name']);
        $this->assertEquals('Test Description', $array['description']);
        $this->assertEquals(10000.00, $array['minimum_cash_balance']);
        $this->assertEquals('USD', $array['minimum_cash_balance_currency']);
        $this->assertEquals(50000.00, $array['maximum_single_transaction']);
        $this->assertEquals('USD', $array['maximum_single_transaction_currency']);
        $this->assertTrue($array['approval_required']);
        $this->assertEquals(10000.00, $array['approval_threshold']);
        $this->assertEquals('USD', $array['approval_threshold_currency']);
    }

    public function testTreasuryPolicyDataWithNullDescription(): void
    {
        $data = [
            'name' => 'Test Policy',
            'minimum_cash_balance' => 10000.00,
            'minimum_cash_balance_currency' => 'USD',
            'maximum_single_transaction' => 50000.00,
            'maximum_single_transaction_currency' => 'USD',
            'approval_required' => false,
        ];

        $policyData = TreasuryPolicyData::fromArray($data);

        $this->assertNull($policyData->description);
    }

    public function testAuthorizationLimitFromArray(): void
    {
        $data = [
            'user_id' => 'user-1',
            'role_id' => null,
            'amount' => 10000.00,
            'currency' => 'USD',
            'transaction_type' => 'payment',
        ];

        $limit = AuthorizationLimit::fromArray($data);

        $this->assertEquals('user-1', $limit->userId);
        $this->assertNull($limit->roleId);
        $this->assertEquals(10000.00, $limit->amount);
        $this->assertEquals('USD', $limit->currency);
        $this->assertEquals('payment', $limit->transactionType);
    }

    public function testAuthorizationLimitToArray(): void
    {
        $limit = new AuthorizationLimit(
            userId: 'user-1',
            roleId: null,
            amount: 10000.00,
            currency: 'USD',
            transactionType: 'payment',
        );

        $array = $limit->toArray();

        $this->assertEquals('user-1', $array['user_id']);
        $this->assertNull($array['role_id']);
        $this->assertEquals(10000.00, $array['amount']);
        $this->assertEquals('USD', $array['currency']);
        $this->assertEquals('payment', $array['transaction_type']);
    }

    public function testTreasuryStatusLabel(): void
    {
        $this->assertEquals('Active', TreasuryStatus::ACTIVE->label());
        $this->assertEquals('Inactive', TreasuryStatus::INACTIVE->label());
        $this->assertEquals('Pending', TreasuryStatus::PENDING->label());
        $this->assertEquals('Suspended', TreasuryStatus::SUSPENDED->label());
        $this->assertEquals('Closed', TreasuryStatus::CLOSED->label());
    }

    public function testTreasuryStatusIsOperational(): void
    {
        $this->assertTrue(TreasuryStatus::ACTIVE->isOperational());
        $this->assertFalse(TreasuryStatus::INACTIVE->isOperational());
        $this->assertFalse(TreasuryStatus::PENDING->isOperational());
        $this->assertFalse(TreasuryStatus::SUSPENDED->isOperational());
        $this->assertFalse(TreasuryStatus::CLOSED->isOperational());
    }

    public function testApprovalStatusLabel(): void
    {
        $this->assertEquals('Pending', ApprovalStatus::PENDING->label());
        $this->assertEquals('Approved', ApprovalStatus::APPROVED->label());
        $this->assertEquals('Rejected', ApprovalStatus::REJECTED->label());
        $this->assertEquals('Cancelled', ApprovalStatus::CANCELLED->label());
        $this->assertEquals('Expired', ApprovalStatus::EXPIRED->label());
        $this->assertEquals('Requires Review', ApprovalStatus::REQUIRES_REVIEW->label());
    }

    public function testApprovalStatusIsFinal(): void
    {
        $this->assertFalse(ApprovalStatus::PENDING->isFinal());
        $this->assertTrue(ApprovalStatus::APPROVED->isFinal());
        $this->assertTrue(ApprovalStatus::REJECTED->isFinal());
        $this->assertTrue(ApprovalStatus::CANCELLED->isFinal());
        $this->assertTrue(ApprovalStatus::EXPIRED->isFinal());
        $this->assertFalse(ApprovalStatus::REQUIRES_REVIEW->isFinal());
    }

    public function testApprovalStatusIsPending(): void
    {
        $this->assertTrue(ApprovalStatus::PENDING->isPending());
        $this->assertFalse(ApprovalStatus::APPROVED->isPending());
    }
}
