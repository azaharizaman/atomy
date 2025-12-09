<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Tests\Unit\Rules\Requisition;

use Nexus\ProcurementOperations\DTOs\ApprovalChainContext;
use Nexus\ProcurementOperations\Enums\ApprovalLevel;
use Nexus\ProcurementOperations\Rules\Requisition\SpendLimitRule;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for SpendLimitRule.
 */
final class SpendLimitRuleTest extends TestCase
{
    private SpendLimitRule $rule;

    protected function setUp(): void
    {
        $this->rule = new SpendLimitRule();
    }

    /**
     * Test rule passes when approver has sufficient spend authority.
     */
    public function test_passes_when_approver_has_sufficient_authority(): void
    {
        $context = $this->createContextWithSpendLimits(
            amountCents: 400000, // $4,000
            approverLimitCents: 500000 // $5,000 limit
        );

        $result = $this->rule->check($context);

        $this->assertTrue($result->passed());
    }

    /**
     * Test rule passes when amount equals spend limit.
     */
    public function test_passes_when_amount_equals_spend_limit(): void
    {
        $context = $this->createContextWithSpendLimits(
            amountCents: 500000,
            approverLimitCents: 500000
        );

        $result = $this->rule->check($context);

        $this->assertTrue($result->passed());
    }

    /**
     * Test rule fails when amount exceeds highest approver limit.
     */
    public function test_fails_when_amount_exceeds_all_approver_limits(): void
    {
        $context = $this->createContextWithSpendLimits(
            amountCents: 15000000, // $150,000
            approverLimitCents: 10000000 // $100,000 max
        );

        $result = $this->rule->check($context);

        $this->assertTrue($result->failed());
        $this->assertStringContainsString('exceeds', $result->getMessage());
    }

    /**
     * Test rule handles multiple approvers with escalation.
     */
    public function test_passes_with_escalation_to_higher_authority(): void
    {
        $context = $this->createContextWithMultipleApprovers(
            amountCents: 2000000, // $20,000
            approvers: [
                ['id' => 'manager', 'limit_cents' => 500000, 'level' => 1],
                ['id' => 'dept-head', 'limit_cents' => 2500000, 'level' => 2],
            ]
        );

        $result = $this->rule->check($context);

        $this->assertTrue($result->passed());
    }

    /**
     * Create a context with spend limit info.
     */
    private function createContextWithSpendLimits(
        int $amountCents,
        int $approverLimitCents
    ): ApprovalChainContext {
        return new ApprovalChainContext(
            tenantId: 'tenant-1',
            documentId: 'doc-123',
            documentType: 'REQUISITION',
            amountCents: $amountCents,
            currency: 'MYR',
            requesterId: 'user-1',
            requesterInfo: [
                'id' => 'user-1',
                'name' => 'Test Requester',
            ],
            budgetInfo: [
                'has_budget' => true,
                'available_amount_cents' => 10000000,
            ],
            approvalSettings: [
                'level_thresholds' => [
                    ApprovalLevel::LEVEL_1->value => $approverLimitCents,
                ],
            ],
            resolvedApprovers: [
                [
                    'id' => 'approver-1',
                    'level' => ApprovalLevel::LEVEL_1->value,
                    'spend_limit_cents' => $approverLimitCents,
                ],
            ]
        );
    }

    /**
     * Create a context with multiple approvers.
     */
    private function createContextWithMultipleApprovers(
        int $amountCents,
        array $approvers
    ): ApprovalChainContext {
        $resolvedApprovers = array_map(
            fn(array $approver) => [
                'id' => $approver['id'],
                'level' => $approver['level'],
                'spend_limit_cents' => $approver['limit_cents'],
            ],
            $approvers
        );

        $maxLimit = max(array_column($approvers, 'limit_cents'));

        return new ApprovalChainContext(
            tenantId: 'tenant-1',
            documentId: 'doc-123',
            documentType: 'REQUISITION',
            amountCents: $amountCents,
            currency: 'MYR',
            requesterId: 'user-1',
            requesterInfo: [
                'id' => 'user-1',
                'name' => 'Test Requester',
            ],
            budgetInfo: [
                'has_budget' => true,
                'available_amount_cents' => 10000000,
            ],
            approvalSettings: [
                'max_approval_limit_cents' => $maxLimit,
            ],
            resolvedApprovers: $resolvedApprovers
        );
    }
}
