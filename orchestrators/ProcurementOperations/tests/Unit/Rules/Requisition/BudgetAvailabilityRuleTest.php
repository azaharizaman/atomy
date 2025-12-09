<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Tests\Unit\Rules\Requisition;

use Nexus\ProcurementOperations\DTOs\ApprovalChainContext;
use Nexus\ProcurementOperations\Rules\Requisition\BudgetAvailabilityRule;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for BudgetAvailabilityRule.
 */
final class BudgetAvailabilityRuleTest extends TestCase
{
    private BudgetAvailabilityRule $rule;

    protected function setUp(): void
    {
        $this->rule = new BudgetAvailabilityRule();
    }

    /**
     * Test rule passes when budget is available.
     */
    public function test_passes_when_budget_is_available(): void
    {
        $context = $this->createContextWithBudget(
            hasBudget: true,
            budgetAmountCents: 1000000,
            requestedAmountCents: 500000
        );

        $result = $this->rule->check($context);

        $this->assertTrue($result->passed());
    }

    /**
     * Test rule passes when budget is exactly available.
     */
    public function test_passes_when_budget_is_exactly_available(): void
    {
        $context = $this->createContextWithBudget(
            hasBudget: true,
            budgetAmountCents: 500000,
            requestedAmountCents: 500000
        );

        $result = $this->rule->check($context);

        $this->assertTrue($result->passed());
    }

    /**
     * Test rule fails when budget is insufficient.
     */
    public function test_fails_when_budget_is_insufficient(): void
    {
        $context = $this->createContextWithBudget(
            hasBudget: true,
            budgetAmountCents: 300000,
            requestedAmountCents: 500000
        );

        $result = $this->rule->check($context);

        $this->assertTrue($result->failed());
        $this->assertStringContainsString('Insufficient budget', $result->getMessage());
    }

    /**
     * Test rule fails when no budget is configured.
     */
    public function test_fails_when_no_budget_configured(): void
    {
        $context = $this->createContextWithBudget(
            hasBudget: false,
            budgetAmountCents: 0,
            requestedAmountCents: 500000
        );

        $result = $this->rule->check($context);

        $this->assertTrue($result->failed());
        $this->assertStringContainsString('No budget', $result->getMessage());
    }

    /**
     * Test rule skips validation when budget check is disabled.
     */
    public function test_skips_validation_when_budget_check_disabled(): void
    {
        $context = $this->createContextWithBudget(
            hasBudget: false,
            budgetAmountCents: 0,
            requestedAmountCents: 500000,
            budgetCheckEnabled: false
        );

        $result = $this->rule->check($context);

        $this->assertTrue($result->passed());
    }

    /**
     * Create a context with budget information.
     */
    private function createContextWithBudget(
        bool $hasBudget,
        int $budgetAmountCents,
        int $requestedAmountCents,
        bool $budgetCheckEnabled = true
    ): ApprovalChainContext {
        return new ApprovalChainContext(
            tenantId: 'tenant-1',
            documentId: 'doc-123',
            documentType: 'REQUISITION',
            amountCents: $requestedAmountCents,
            currency: 'MYR',
            requesterId: 'user-1',
            requesterInfo: [
                'id' => 'user-1',
                'name' => 'Test User',
                'department_id' => 'dept-1',
            ],
            budgetInfo: [
                'has_budget' => $hasBudget,
                'available_amount_cents' => $budgetAmountCents,
                'budget_code' => $hasBudget ? 'BUD-2024-001' : null,
                'budget_check_enabled' => $budgetCheckEnabled,
            ],
            approvalSettings: [],
            resolvedApprovers: []
        );
    }
}
