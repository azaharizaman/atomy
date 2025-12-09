<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Tests\Unit\Rules\Requisition;

use Nexus\ProcurementOperations\DTOs\ApprovalChainContext;
use Nexus\ProcurementOperations\Enums\ApprovalLevel;
use Nexus\ProcurementOperations\Rules\Requisition\ApprovalHierarchyRule;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for ApprovalHierarchyRule.
 */
final class ApprovalHierarchyRuleTest extends TestCase
{
    private ApprovalHierarchyRule $rule;

    protected function setUp(): void
    {
        $this->rule = new ApprovalHierarchyRule();
    }

    /**
     * Test rule passes with valid approval chain.
     */
    public function test_passes_with_valid_approval_chain(): void
    {
        $context = $this->createContextWithApprovers([
            [
                'id' => 'approver-1',
                'level' => ApprovalLevel::LEVEL_1->value,
                'name' => 'Manager',
            ],
        ]);

        $result = $this->rule->check($context);

        $this->assertTrue($result->passed());
    }

    /**
     * Test rule fails with empty approval chain.
     */
    public function test_fails_with_empty_approval_chain(): void
    {
        $context = $this->createContextWithApprovers([]);

        $result = $this->rule->check($context);

        $this->assertTrue($result->failed());
        $this->assertStringContainsString('No approvers', $result->getMessage());
    }

    /**
     * Test rule fails when requester is in approval chain.
     */
    public function test_fails_when_requester_is_approver(): void
    {
        $context = $this->createContextWithApprovers(
            approvers: [
                [
                    'id' => 'user-1', // Same as requester
                    'level' => ApprovalLevel::LEVEL_1->value,
                    'name' => 'Self Approver',
                ],
            ],
            requesterId: 'user-1'
        );

        $result = $this->rule->check($context);

        $this->assertTrue($result->failed());
        $this->assertStringContainsString('cannot approve their own', $result->getMessage());
    }

    /**
     * Test rule fails when approval chain has circular delegation.
     */
    public function test_fails_with_circular_delegation(): void
    {
        $context = $this->createContextWithApprovers([
            [
                'id' => 'approver-1',
                'level' => ApprovalLevel::LEVEL_1->value,
                'name' => 'Approver 1',
                'delegated_from' => 'approver-2',
            ],
            [
                'id' => 'approver-2',
                'level' => ApprovalLevel::LEVEL_2->value,
                'name' => 'Approver 2',
                'delegated_from' => 'approver-1',
            ],
        ]);

        $result = $this->rule->check($context);

        $this->assertTrue($result->failed());
        $this->assertStringContainsString('circular', $result->getMessage());
    }

    /**
     * Test rule fails when chain depth exceeds maximum.
     */
    public function test_fails_when_chain_depth_exceeds_maximum(): void
    {
        // Create chain with more than 5 levels (max depth)
        $approvers = [];
        for ($i = 1; $i <= 7; $i++) {
            $approvers[] = [
                'id' => "approver-{$i}",
                'level' => min($i, 5),
                'name' => "Approver {$i}",
            ];
        }

        $context = $this->createContextWithApprovers($approvers);

        $result = $this->rule->check($context);

        $this->assertTrue($result->failed());
        $this->assertStringContainsString('exceeds maximum', $result->getMessage());
    }

    /**
     * Create a context with specified approvers.
     */
    private function createContextWithApprovers(
        array $approvers,
        string $requesterId = 'requester-1'
    ): ApprovalChainContext {
        return new ApprovalChainContext(
            tenantId: 'tenant-1',
            documentId: 'doc-123',
            documentType: 'REQUISITION',
            amountCents: 500000,
            currency: 'MYR',
            requesterId: $requesterId,
            requesterInfo: [
                'id' => $requesterId,
                'name' => 'Test Requester',
                'department_id' => 'dept-1',
            ],
            budgetInfo: [
                'has_budget' => true,
                'available_amount_cents' => 1000000,
            ],
            approvalSettings: [
                'max_chain_depth' => 5,
            ],
            resolvedApprovers: $approvers
        );
    }
}
