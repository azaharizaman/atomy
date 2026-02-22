<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\Tests\Unit\Rules;

use PHPUnit\Framework\TestCase;
use Nexus\FinanceOperations\Rules\BudgetAvailableRule;
use Nexus\FinanceOperations\DTOs\RuleResult;

/**
 * Unit tests for BudgetAvailableRule.
 *
 * Tests cover:
 * - Budget available (pass)
 * - Insufficient budget (fail in strict mode)
 * - Insufficient budget with warning (pass in non-strict mode)
 * - Budget not found (fail)
 * - Budget not active (fail)
 * - Missing budget ID (fail)
 * - Different context property formats
 *
 * @since 1.0.0
 */
final class BudgetAvailableRuleTest extends TestCase
{
    // =========================================================================
    // Test Suite: Budget Available - Pass Cases
    // =========================================================================

    /**
     * Test budget available passes validation.
     */
    public function testBudgetAvailablePassesValidation(): void
    {
        $rule = new BudgetAvailableRule(new class {
            public function getBudget(string $tenantId, string $budgetId): object {
                return new class {
                    public function isActive(): bool {
                        return true;
                    }
                };
            }
            public function getAvailableAmount(string $tenantId, string $budgetId, ?string $costCenterId = null): string {
                return '10000.00';
            }
        });

        $context = new class {
            public string $tenantId = 'tenant-001';
            public string $budgetId = 'budget-001';
            public string $amount = '5000.00';
        };

        $result = $rule->check($context);

        $this->assertTrue($result->passed);
        $this->assertEquals('budget_available', $result->ruleName);
    }

    /**
     * Test exact amount matches available budget.
     */
    public function testExactAmountMatchesAvailableBudget(): void
    {
        $rule = new BudgetAvailableRule(new class {
            public function getBudget(string $tenantId, string $budgetId): object {
                return new class {
                    public function isActive(): bool {
                        return true;
                    }
                };
            }
            public function getAvailableAmount(string $tenantId, string $budgetId, ?string $costCenterId = null): string {
                return '5000.00';
            }
        });

        $context = new class {
            public string $tenantId = 'tenant-001';
            public string $budgetId = 'budget-001';
            public string $amount = '5000.00';
        };

        $result = $rule->check($context);

        $this->assertTrue($result->passed);
    }

    // =========================================================================
    // Test Suite: Insufficient Budget - Fail Cases (Strict Mode)
    // =========================================================================

    /**
     * Test insufficient budget fails in strict mode.
     */
    public function testInsufficientBudgetFailsInStrictMode(): void
    {
        $rule = new BudgetAvailableRule(new class {
            public function getBudget(string $tenantId, string $budgetId): object {
                return new class {
                    public function isActive(): bool {
                        return true;
                    }
                };
            }
            public function getAvailableAmount(string $tenantId, string $budgetId, ?string $costCenterId = null): string {
                return '3000.00';
            }
        }, strictMode: true);

        $context = new class {
            public string $tenantId = 'tenant-001';
            public string $budgetId = 'budget-001';
            public string $amount = '5000.00';
        };

        $result = $rule->check($context);

        $this->assertFalse($result->passed);
        $this->assertStringContainsString('Insufficient budget', $result->message);
    }

    /**
     * Test insufficient budget shows shortfall in violations.
     */
    public function testInsufficientBudgetShowsShortfall(): void
    {
        $rule = new BudgetAvailableRule(new class {
            public function getBudget(string $tenantId, string $budgetId): object {
                return new class {
                    public function isActive(): bool {
                        return true;
                    }
                };
            }
            public function getAvailableAmount(string $tenantId, string $budgetId, ?string $costCenterId = null): string {
                return '3000.00';
            }
        }, strictMode: true);

        $context = new class {
            public string $tenantId = 'tenant-001';
            public string $budgetId = 'budget-001';
            public string $amount = '5000.00';
        };

        $result = $rule->check($context);

        $this->assertNotEmpty($result->violations);
        $this->assertArrayHasKey('shortfall', $result->violations[0]);
    }

    // =========================================================================
    // Test Suite: Insufficient Budget - Pass with Warning (Non-Strict Mode)
    // =========================================================================

    /**
     * Test insufficient budget passes with warning in non-strict mode.
     */
    public function testInsufficientBudgetPassesWithWarningInNonStrictMode(): void
    {
        $rule = new BudgetAvailableRule(new class {
            public function getBudget(string $tenantId, string $budgetId): object {
                return new class {
                    public function isActive(): bool {
                        return true;
                    }
                };
            }
            public function getAvailableAmount(string $tenantId, string $budgetId, ?string $costCenterId = null): string {
                return '3000.00';
            }
        }, strictMode: false);

        $context = new class {
            public string $tenantId = 'tenant-001';
            public string $budgetId = 'budget-001';
            public string $amount = '5000.00';
        };

        $result = $rule->check($context);

        $this->assertTrue($result->passed);
        $this->assertStringContainsString('warning', $result->message);
    }

    // =========================================================================
    // Test Suite: Budget Not Found - Fail Cases
    // =========================================================================

    /**
     * Test budget not found fails validation.
     */
    public function testBudgetNotFoundFailsValidation(): void
    {
        $rule = new BudgetAvailableRule(new class {
            public function getBudget(string $tenantId, string $budgetId): ?object {
                return null;
            }
        });

        $context = new class {
            public string $tenantId = 'tenant-001';
            public string $budgetId = 'non-existent';
            public string $amount = '1000.00';
        };

        $result = $rule->check($context);

        $this->assertFalse($result->passed);
        $this->assertStringContainsString('not found', $result->message);
    }

    // =========================================================================
    // Test Suite: Budget Not Active - Fail Cases
    // =========================================================================

    /**
     * Test inactive budget fails validation.
     */
    public function testInactiveBudgetFailsValidation(): void
    {
        $rule = new BudgetAvailableRule(new class {
            public function getBudget(string $tenantId, string $budgetId): object {
                return new class {
                    public function isActive(): bool {
                        return false;
                    }
                };
            }
            public function getAvailableAmount(string $tenantId, string $budgetId, ?string $costCenterId = null): string {
                return '10000.00';
            }
        });

        $context = new class {
            public string $tenantId = 'tenant-001';
            public string $budgetId = 'budget-001';
            public string $amount = '5000.00';
        };

        $result = $rule->check($context);

        $this->assertFalse($result->passed);
        $this->assertStringContainsString('not active', $result->message);
    }

    /**
     * Test inactive budget using is_active property.
     */
    public function testInactiveBudgetUsingIsActiveProperty(): void
    {
        $rule = new BudgetAvailableRule(new class {
            public function getBudget(string $tenantId, string $budgetId): object {
                return new class {
                    public bool $is_active = false;
                };
            }
            public function getAvailableAmount(string $tenantId, string $budgetId, ?string $costCenterId = null): string {
                return '10000.00';
            }
        });

        $context = new class {
            public string $tenantId = 'tenant-001';
            public string $budgetId = 'budget-001';
            public string $amount = '5000.00';
        };

        $result = $rule->check($context);

        $this->assertFalse($result->passed);
    }

    /**
     * Test inactive budget using getIsActive method.
     */
    public function testInactiveBudgetUsingGetIsActiveMethod(): void
    {
        $rule = new BudgetAvailableRule(new class {
            public function getBudget(string $tenantId, string $budgetId): object {
                return new class {
                    public function getIsActive(): bool {
                        return false;
                    }
                };
            }
            public function getAvailableAmount(string $tenantId, string $budgetId, ?string $costCenterId = null): string {
                return '10000.00';
            }
        });

        $context = new class {
            public string $tenantId = 'tenant-001';
            public string $budgetId = 'budget-001';
            public string $amount = '5000.00';
        };

        $result = $rule->check($context);

        $this->assertFalse($result->passed);
    }

    // =========================================================================
    // Test Suite: Missing Budget ID - Fail Cases
    // =========================================================================

    /**
     * Test missing budget ID fails validation.
     */
    public function testMissingBudgetIdFailsValidation(): void
    {
        $rule = new BudgetAvailableRule(new class {
            public function getBudget(string $tenantId, string $budgetId): ?object {
                return null;
            }
        });

        $context = new class {
            public string $tenantId = 'tenant-001';
            public string $budgetId = '';
            public string $amount = '1000.00';
        };

        $result = $rule->check($context);

        $this->assertFalse($result->passed);
        $this->assertStringContainsString('required', $result->message);
    }

    // =========================================================================
    // Test Suite: Context Property Format Variations
    // =========================================================================

    /**
     * Test context with underscore format properties.
     */
    public function testContextWithUnderscoreFormatProperties(): void
    {
        $rule = new BudgetAvailableRule(new class {
            public function getBudget(string $tenantId, string $budgetId): object {
                return new class {
                    public function isActive(): bool {
                        return true;
                    }
                };
            }
            public function getAvailableAmount(string $tenantId, string $budgetId, ?string $costCenterId = null): string {
                return '10000.00';
            }
        });

        $context = new class {
            public string $tenant_id = 'tenant-001';
            public string $budget_id = 'budget-001';
            public string $amount = '5000.00';
        };

        $result = $rule->check($context);

        $this->assertTrue($result->passed);
    }

    /**
     * Test context with getter methods.
     */
    public function testContextWithGetterMethods(): void
    {
        $rule = new BudgetAvailableRule(new class {
            public function getBudget(string $tenantId, string $budgetId): object {
                return new class {
                    public function isActive(): bool {
                        return true;
                    }
                };
            }
            public function getAvailableAmount(string $tenantId, string $budgetId, ?string $costCenterId = null): string {
                return '10000.00';
            }
        });

        $context = new class {
            public function getTenantId(): string {
                return 'tenant-001';
            }
            public function getBudgetId(): string {
                return 'budget-001';
            }
            public function getAmount(): string {
                return '5000.00';
            }
        };

        $result = $rule->check($context);

        $this->assertTrue($result->passed);
    }

    /**
     * Test context with requestedAmount property.
     */
    public function testContextWithRequestedAmountProperty(): void
    {
        $rule = new BudgetAvailableRule(new class {
            public function getBudget(string $tenantId, string $budgetId): object {
                return new class {
                    public function isActive(): bool {
                        return true;
                    }
                };
            }
            public function getAvailableAmount(string $tenantId, string $budgetId, ?string $costCenterId = null): string {
                return '10000.00';
            }
        });

        $context = new class {
            public string $tenantId = 'tenant-001';
            public string $budgetId = 'budget-001';
            public string $requestedAmount = '5000.00';
        };

        $result = $rule->check($context);

        $this->assertTrue($result->passed);
    }

    /**
     * Test context with cost center ID.
     */
    public function testContextWithCostCenterId(): void
    {
        $rule = new BudgetAvailableRule(new class {
            public function getBudget(string $tenantId, string $budgetId): object {
                return new class {
                    public function isActive(): bool {
                        return true;
                    }
                };
            }
            public function getAvailableAmount(string $tenantId, string $budgetId, ?string $costCenterId = null): string {
                // Verify cost center ID is passed through
                return $costCenterId === 'cc-001' ? '5000.00' : '0';
            }
        });

        $context = new class {
            public string $tenantId = 'tenant-001';
            public string $budgetId = 'budget-001';
            public string $amount = '3000.00';
            public string $costCenterId = 'cc-001';
        };

        $result = $rule->check($context);

        $this->assertTrue($result->passed);
    }

    // =========================================================================
    // Test Suite: getName()
    // =========================================================================

    /**
     * Test getName returns correct rule name.
     */
    public function testGetNameReturnsBudgetAvailable(): void
    {
        $rule = new BudgetAvailableRule(new class {
            public function getBudget(string $tenantId, string $budgetId): ?object {
                return null;
            }
        });

        $this->assertEquals('budget_available', $rule->getName());
    }
}
