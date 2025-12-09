<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Tests\Unit\Rules\Requisition;

use Nexus\ProcurementOperations\DTOs\ApprovalChainContext;
use Nexus\ProcurementOperations\Rules\Requisition\CategoryApprovalRule;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for CategoryApprovalRule.
 */
final class CategoryApprovalRuleTest extends TestCase
{
    private CategoryApprovalRule $rule;

    protected function setUp(): void
    {
        $this->rule = new CategoryApprovalRule();
    }

    /**
     * Test rule passes for standard category with regular approval.
     */
    public function test_passes_for_standard_category(): void
    {
        $context = $this->createContextWithCategory(
            categoryCode: 'OFFICE_SUPPLIES',
            amountCents: 50000
        );

        $result = $this->rule->check($context);

        $this->assertTrue($result->passed());
    }

    /**
     * Test rule requires additional approval for CAPITAL category.
     */
    public function test_requires_finance_approval_for_capital_expenditure(): void
    {
        $context = $this->createContextWithCategory(
            categoryCode: 'CAPITAL',
            amountCents: 5000000, // $50,000
            hasFinanceApprover: false
        );

        $result = $this->rule->check($context);

        $this->assertTrue($result->failed());
        $this->assertStringContainsString('Finance', $result->getMessage());
    }

    /**
     * Test rule passes for CAPITAL with finance approver.
     */
    public function test_passes_for_capital_with_finance_approver(): void
    {
        $context = $this->createContextWithCategory(
            categoryCode: 'CAPITAL',
            amountCents: 5000000,
            hasFinanceApprover: true
        );

        $result = $this->rule->check($context);

        $this->assertTrue($result->passed());
    }

    /**
     * Test rule requires IT approval for IT_HARDWARE category.
     */
    public function test_requires_it_approval_for_hardware(): void
    {
        $context = $this->createContextWithCategory(
            categoryCode: 'IT_HARDWARE',
            amountCents: 200000,
            hasItApprover: false
        );

        $result = $this->rule->check($context);

        $this->assertTrue($result->failed());
        $this->assertStringContainsString('IT', $result->getMessage());
    }

    /**
     * Test rule passes for IT_HARDWARE with IT approver.
     */
    public function test_passes_for_it_hardware_with_it_approver(): void
    {
        $context = $this->createContextWithCategory(
            categoryCode: 'IT_HARDWARE',
            amountCents: 200000,
            hasItApprover: true
        );

        $result = $this->rule->check($context);

        $this->assertTrue($result->passed());
    }

    /**
     * Test rule requires legal approval for PROFESSIONAL_SERVICES over threshold.
     */
    public function test_requires_legal_approval_for_large_professional_services(): void
    {
        $context = $this->createContextWithCategory(
            categoryCode: 'PROFESSIONAL_SERVICES',
            amountCents: 5000000, // $50,000
            hasLegalApprover: false
        );

        $result = $this->rule->check($context);

        $this->assertTrue($result->failed());
        $this->assertStringContainsString('Legal', $result->getMessage());
    }

    /**
     * Test rule passes for small professional services without legal.
     */
    public function test_passes_for_small_professional_services(): void
    {
        $context = $this->createContextWithCategory(
            categoryCode: 'PROFESSIONAL_SERVICES',
            amountCents: 100000, // $1,000 - under threshold
            hasLegalApprover: false
        );

        $result = $this->rule->check($context);

        $this->assertTrue($result->passed());
    }

    /**
     * Create a context with category information.
     */
    private function createContextWithCategory(
        string $categoryCode,
        int $amountCents,
        bool $hasFinanceApprover = false,
        bool $hasItApprover = false,
        bool $hasLegalApprover = false
    ): ApprovalChainContext {
        $approvers = [
            [
                'id' => 'manager-1',
                'level' => 1,
                'role' => 'MANAGER',
            ],
        ];

        if ($hasFinanceApprover) {
            $approvers[] = [
                'id' => 'finance-1',
                'level' => 3,
                'role' => 'FINANCE_DIRECTOR',
            ];
        }

        if ($hasItApprover) {
            $approvers[] = [
                'id' => 'it-1',
                'level' => 2,
                'role' => 'IT_MANAGER',
            ];
        }

        if ($hasLegalApprover) {
            $approvers[] = [
                'id' => 'legal-1',
                'level' => 3,
                'role' => 'LEGAL_COUNSEL',
            ];
        }

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
                'category_code' => $categoryCode,
            ],
            budgetInfo: [
                'has_budget' => true,
                'available_amount_cents' => 10000000,
            ],
            approvalSettings: [
                'category_rules' => [
                    'CAPITAL' => ['requires' => 'FINANCE_DIRECTOR'],
                    'IT_HARDWARE' => ['requires' => 'IT_MANAGER'],
                    'PROFESSIONAL_SERVICES' => [
                        'requires' => 'LEGAL_COUNSEL',
                        'threshold_cents' => 2500000,
                    ],
                ],
            ],
            resolvedApprovers: $approvers,
            categoryCode: $categoryCode
        );
    }
}
