<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\Tests\Unit\Rules;

use Nexus\FinanceOperations\Contracts\BudgetAvailabilityQueryInterface;
use Nexus\FinanceOperations\Contracts\BudgetRuleViewInterface;
use Nexus\FinanceOperations\DTOs\RuleContext;
use Nexus\FinanceOperations\Rules\BudgetAvailableRule;
use PHPUnit\Framework\TestCase;

final class BudgetAvailableRuleTest extends TestCase
{
    public function testBudgetAvailablePassesValidation(): void
    {
        $rule = new BudgetAvailableRule(
            budgetQuery: $this->query(active: true, availableAmount: '10000.00', found: true, expectedTenantId: 'tenant-001', expectedBudgetId: 'budget-001'),
        );

        $result = $rule->check(
            RuleContext::forBudgetAvailability('tenant-001', 'budget-001', '5000.00')
        );

        self::assertTrue($result->passed);
    }

    public function testBudgetNotFoundFailsValidation(): void
    {
        $rule = new BudgetAvailableRule(
            budgetQuery: $this->query(active: true, availableAmount: '10000.00', found: false, expectedTenantId: 'tenant-001', expectedBudgetId: 'missing-budget'),
        );

        $result = $rule->check(
            RuleContext::forBudgetAvailability('tenant-001', 'missing-budget', '5000.00')
        );

        self::assertFalse($result->passed);
        self::assertStringContainsString('not found', $result->message);
    }

    public function testInactiveBudgetFailsValidation(): void
    {
        $rule = new BudgetAvailableRule(
            budgetQuery: $this->query(active: false, availableAmount: '10000.00', found: true, expectedTenantId: 'tenant-001', expectedBudgetId: 'budget-001'),
        );

        $result = $rule->check(
            RuleContext::forBudgetAvailability('tenant-001', 'budget-001', '5000.00')
        );

        self::assertFalse($result->passed);
        self::assertStringContainsString('not active', $result->message);
    }

    public function testInsufficientBudgetFailsInStrictMode(): void
    {
        $rule = new BudgetAvailableRule(
            budgetQuery: $this->query(active: true, availableAmount: '3000.00', found: true, expectedTenantId: 'tenant-001', expectedBudgetId: 'budget-001'),
            strictMode: true,
        );

        $result = $rule->check(
            RuleContext::forBudgetAvailability('tenant-001', 'budget-001', '5000.00')
        );

        self::assertFalse($result->passed);
        self::assertStringContainsString('Insufficient budget', $result->message);
    }

    public function testInsufficientBudgetPassesWithWarningInNonStrictMode(): void
    {
        $rule = new BudgetAvailableRule(
            budgetQuery: $this->query(active: true, availableAmount: '3000.00', found: true, expectedTenantId: 'tenant-001', expectedBudgetId: 'budget-001'),
            strictMode: false,
        );

        $result = $rule->check(
            RuleContext::forBudgetAvailability('tenant-001', 'budget-001', '5000.00')
        );

        self::assertTrue($result->passed);
        self::assertStringContainsString('warning', $result->message);
    }

    public function testMissingBudgetIdFailsValidation(): void
    {
        $rule = new BudgetAvailableRule(
            budgetQuery: $this->query(active: true, availableAmount: '10000.00', found: true, expectedTenantId: 'tenant-001', expectedBudgetId: ''),
        );

        $result = $rule->check(
            RuleContext::forBudgetAvailability('tenant-001', '', '5000.00')
        );

        self::assertFalse($result->passed);
        self::assertStringContainsString('required', $result->message);
    }

    private function query(bool $active, string $availableAmount, bool $found, string $expectedTenantId, string $expectedBudgetId, ?string $expectedCostCenterId = null): BudgetAvailabilityQueryInterface
    {
        return new class($active, $availableAmount, $found, $expectedTenantId, $expectedBudgetId, $expectedCostCenterId) implements BudgetAvailabilityQueryInterface {
            public function __construct(
                private bool $active,
                private string $availableAmount,
                private bool $found,
                private string $expectedTenantId,
                private string $expectedBudgetId,
                private ?string $expectedCostCenterId,
            ) {}

            public function getBudget(string $tenantId, string $budgetId): ?BudgetRuleViewInterface
            {
                if ($tenantId !== $this->expectedTenantId || $budgetId !== $this->expectedBudgetId) {
                    throw new \InvalidArgumentException("Unexpected tenant or budget: got $tenantId/$budgetId, expected {$this->expectedTenantId}/{$this->expectedBudgetId}");
                }

                if (!$this->found) {
                    return null;
                }

                return new class($this->active) implements BudgetRuleViewInterface {
                    public function __construct(private bool $active) {}

                    public function isActive(): bool
                    {
                        return $this->active;
                    }
                };
            }

            public function getAvailableAmount(
                string $tenantId,
                string $budgetId,
                ?string $costCenterId = null,
            ): string {
                if ($tenantId !== $this->expectedTenantId || $budgetId !== $this->expectedBudgetId || $costCenterId !== $this->expectedCostCenterId) {
                    throw new \InvalidArgumentException("Unexpected parameters: tenant $tenantId/$budgetId/$costCenterId vs expected {$this->expectedTenantId}/{$this->expectedBudgetId}/{$this->expectedCostCenterId}");
                }

                return $this->availableAmount;
            }
        };
    }
}
