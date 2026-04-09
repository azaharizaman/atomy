<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\Tests\Unit\Rules;

use Nexus\FinanceOperations\Contracts\BudgetAvailabilityQueryInterface;
use Nexus\FinanceOperations\DTOs\RuleContexts\BudgetAvailableRuleContext;
use PHPUnit\Framework\TestCase;
use Nexus\FinanceOperations\Rules\BudgetAvailableRule;

final class BudgetAvailableRuleTest extends TestCase
{
    public function testBudgetAvailablePassesValidation(): void
    {
        $rule = new BudgetAvailableRule(new class implements BudgetAvailabilityQueryInterface {
            public function getBudget(string $tenantId, string $budgetId): ?object
            {
                return new class {
                    public function isActive(): bool
                    {
                        return true;
                    }
                };
            }

            public function getAvailableAmount(string $tenantId, string $budgetId, ?string $costCenterId = null): string
            {
                return '10000.00';
            }
        });

        $result = $rule->check(new BudgetAvailableRuleContext(
            tenantId: 'tenant-001',
            budgetId: 'budget-001',
            amount: '5000.00',
        ));

        $this->assertTrue($result->passed);
        $this->assertEquals('budget_available', $result->ruleName);
    }

    public function testInsufficientBudgetFailsInStrictMode(): void
    {
        $rule = new BudgetAvailableRule(new class implements BudgetAvailabilityQueryInterface {
            public function getBudget(string $tenantId, string $budgetId): ?object
            {
                return new class {
                    public function isActive(): bool
                    {
                        return true;
                    }
                };
            }

            public function getAvailableAmount(string $tenantId, string $budgetId, ?string $costCenterId = null): string
            {
                return '3000.00';
            }
        }, strictMode: true);

        $result = $rule->check(new BudgetAvailableRuleContext(
            tenantId: 'tenant-001',
            budgetId: 'budget-001',
            amount: '5000.00',
        ));

        $this->assertFalse($result->passed);
        $this->assertStringContainsString('Insufficient budget', $result->message);
        $this->assertArrayHasKey('shortfall', $result->violations[0]);
    }

    public function testInsufficientBudgetPassesWithWarningInNonStrictMode(): void
    {
        $rule = new BudgetAvailableRule(new class implements BudgetAvailabilityQueryInterface {
            public function getBudget(string $tenantId, string $budgetId): ?object
            {
                return new class {
                    public function isActive(): bool
                    {
                        return true;
                    }
                };
            }

            public function getAvailableAmount(string $tenantId, string $budgetId, ?string $costCenterId = null): string
            {
                return '3000.00';
            }
        }, strictMode: false);

        $result = $rule->check(new BudgetAvailableRuleContext(
            tenantId: 'tenant-001',
            budgetId: 'budget-001',
            amount: '5000.00',
        ));

        $this->assertTrue($result->passed);
        $this->assertStringContainsString('warning', $result->message);
    }

    public function testBudgetNotFoundFailsValidation(): void
    {
        $rule = new BudgetAvailableRule(new class implements BudgetAvailabilityQueryInterface {
            public function getBudget(string $tenantId, string $budgetId): ?object
            {
                return null;
            }

            public function getAvailableAmount(string $tenantId, string $budgetId, ?string $costCenterId = null): string
            {
                return '0';
            }
        });

        $result = $rule->check(new BudgetAvailableRuleContext(
            tenantId: 'tenant-001',
            budgetId: 'non-existent',
            amount: '1000.00',
        ));

        $this->assertFalse($result->passed);
        $this->assertStringContainsString('not found', $result->message);
    }

    public function testInactiveBudgetFailsValidation(): void
    {
        $rule = new BudgetAvailableRule(new class implements BudgetAvailabilityQueryInterface {
            public function getBudget(string $tenantId, string $budgetId): ?object
            {
                return new class {
                    public function isActive(): bool
                    {
                        return false;
                    }
                };
            }

            public function getAvailableAmount(string $tenantId, string $budgetId, ?string $costCenterId = null): string
            {
                return '10000.00';
            }
        });

        $result = $rule->check(new BudgetAvailableRuleContext(
            tenantId: 'tenant-001',
            budgetId: 'budget-001',
            amount: '5000.00',
        ));

        $this->assertFalse($result->passed);
        $this->assertStringContainsString('not active', $result->message);
    }

    public function testMissingBudgetIdFailsValidation(): void
    {
        $rule = new BudgetAvailableRule(new class implements BudgetAvailabilityQueryInterface {
            public function getBudget(string $tenantId, string $budgetId): ?object
            {
                return null;
            }

            public function getAvailableAmount(string $tenantId, string $budgetId, ?string $costCenterId = null): string
            {
                return '0';
            }
        });

        $result = $rule->check(new BudgetAvailableRuleContext(
            tenantId: 'tenant-001',
            budgetId: '',
            amount: '1000.00',
        ));

        $this->assertFalse($result->passed);
        $this->assertStringContainsString('required', $result->message);
    }

    public function testContextWithCostCenterId(): void
    {
        $budgetQuery = new class implements BudgetAvailabilityQueryInterface {
            public ?string $receivedCostCenterId = null;

            public function getBudget(string $tenantId, string $budgetId): ?object
            {
                return new class {
                    public function isActive(): bool
                    {
                        return true;
                    }
                };
            }

            public function getAvailableAmount(string $tenantId, string $budgetId, ?string $costCenterId = null): string
            {
                $this->receivedCostCenterId = $costCenterId;

                return '5000.00';
            }
        };
        $rule = new BudgetAvailableRule($budgetQuery);

        $result = $rule->check(new BudgetAvailableRuleContext(
            tenantId: 'tenant-001',
            budgetId: 'budget-001',
            amount: '3000.00',
            costCenterId: 'cc-001',
        ));

        $this->assertTrue($result->passed);
        $this->assertSame('cc-001', $budgetQuery->receivedCostCenterId);
    }

    public function testGetNameReturnsBudgetAvailable(): void
    {
        $rule = new BudgetAvailableRule(new class implements BudgetAvailabilityQueryInterface {
            public function getBudget(string $tenantId, string $budgetId): ?object
            {
                return null;
            }

            public function getAvailableAmount(string $tenantId, string $budgetId, ?string $costCenterId = null): string
            {
                return '0';
            }
        });

        $this->assertEquals('budget_available', $rule->getName());
    }

    public function testMissingTenantIdFailsValidation(): void
    {
        $rule = new BudgetAvailableRule(new class implements BudgetAvailabilityQueryInterface {
            public function getBudget(string $tenantId, string $budgetId): ?object
            {
                return null;
            }

            public function getAvailableAmount(string $tenantId, string $budgetId, ?string $costCenterId = null): string
            {
                return '0';
            }
        });

        $result = $rule->check(new BudgetAvailableRuleContext(
            tenantId: '   ',
            budgetId: 'budget-001',
            amount: '1000.00',
        ));

        $this->assertFalse($result->passed);
        $this->assertSame(['missing_field' => 'tenantId'], $result->violations);
    }

    public function testNonNumericAmountFailsValidation(): void
    {
        $rule = new BudgetAvailableRule(new class implements BudgetAvailabilityQueryInterface {
            public function getBudget(string $tenantId, string $budgetId): ?object
            {
                return null;
            }

            public function getAvailableAmount(string $tenantId, string $budgetId, ?string $costCenterId = null): string
            {
                return '0';
            }
        });

        $result = $rule->check(new BudgetAvailableRuleContext(
            tenantId: 'tenant-001',
            budgetId: 'budget-001',
            amount: 'not-a-number',
        ));

        $this->assertFalse($result->passed);
        $this->assertSame(['invalid_field' => 'amount'], $result->violations);
    }
}
