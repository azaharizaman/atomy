<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\Tests\Unit\Rules;

use PHPUnit\Framework\TestCase;
use Nexus\FinanceOperations\Rules\SubledgerClosedRule;
use Nexus\FinanceOperations\DTOs\RuleResult;

/**
 * Unit tests for SubledgerClosedRule.
 *
 * Tests cover:
 * - Subledger is closed (pass)
 * - Subledger is not closed (fail)
 * - Missing period ID (fail)
 * - Missing subledger type (fail)
 * - Different context property formats
 *
 * @since 1.0.0
 */
final class SubledgerClosedRuleTest extends TestCase
{
    // =========================================================================
    // Test Suite: Subledger Closed - Pass Cases
    // =========================================================================

    /**
     * Test subledger is closed passes validation.
     */
    public function testSubledgerClosedPassesValidation(): void
    {
        $rule = new SubledgerClosedRule(new class {
            public function isSubledgerClosed(string $tenantId, string $periodId, string $subledgerType): bool {
                return true;
            }
        });

        $context = new class {
            public string $tenantId = 'tenant-001';
            public string $periodId = '2026-01';
            public string $subledgerType = 'AR';
        };

        $result = $rule->check($context);

        $this->assertTrue($result->passed);
        $this->assertEquals('subledger_closed', $result->ruleName);
    }

    // =========================================================================
    // Test Suite: Subledger Not Closed - Fail Cases
    // =========================================================================

    /**
     * Test subledger not closed fails validation.
     */
    public function testSubledgerNotClosedFailsValidation(): void
    {
        $rule = new SubledgerClosedRule(new class {
            public function isSubledgerClosed(string $tenantId, string $periodId, string $subledgerType): bool {
                return false;
            }
        });

        $context = new class {
            public string $tenantId = 'tenant-001';
            public string $periodId = '2026-01';
            public string $subledgerType = 'AR';
        };

        $result = $rule->check($context);

        $this->assertFalse($result->passed);
        $this->assertStringContainsString('not closed', $result->message);
    }

    // =========================================================================
    // Test Suite: Missing Required Fields - Fail Cases
    // =========================================================================

    /**
     * Test missing period ID fails validation.
     */
    public function testMissingPeriodIdFailsValidation(): void
    {
        $rule = new SubledgerClosedRule(new class {
            public function isSubledgerClosed(string $tenantId, string $periodId, string $subledgerType): bool {
                return true;
            }
        });

        $context = new class {
            public string $tenantId = 'tenant-001';
            public string $periodId = '';
            public string $subledgerType = 'AR';
        };

        $result = $rule->check($context);

        $this->assertFalse($result->passed);
        $this->assertStringContainsString('required', $result->message);
    }

    /**
     * Test missing subledger type fails validation.
     */
    public function testMissingSubledgerTypeFailsValidation(): void
    {
        $rule = new SubledgerClosedRule(new class {
            public function isSubledgerClosed(string $tenantId, string $periodId, string $subledgerType): bool {
                return true;
            }
        });

        $context = new class {
            public string $tenantId = 'tenant-001';
            public string $periodId = '2026-01';
            public string $subledgerType = '';
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
        $rule = new SubledgerClosedRule(new class {
            public function isSubledgerClosed(string $tenantId, string $periodId, string $subledgerType): bool {
                return true;
            }
        });

        $context = new class {
            public string $tenant_id = 'tenant-001';
            public string $period_id = '2026-01';
            public string $subledger_type = 'AP';
        };

        $result = $rule->check($context);

        $this->assertTrue($result->passed);
    }

    /**
     * Test context with getter methods.
     */
    public function testContextWithGetterMethods(): void
    {
        $rule = new SubledgerClosedRule(new class {
            public function isSubledgerClosed(string $tenantId, string $periodId, string $subledgerType): bool {
                return true;
            }
        });

        $context = new class {
            public function getTenantId(): string {
                return 'tenant-001';
            }
            public function getPeriodId(): string {
                return '2026-01';
            }
            public function getSubledgerType(): string {
                return 'AR';
            }
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
    public function testGetNameReturnsSubledgerClosed(): void
    {
        $rule = new SubledgerClosedRule(new class {
            public function isSubledgerClosed(string $tenantId, string $periodId, string $subledgerType): bool {
                return true;
            }
        });

        $this->assertEquals('subledger_closed', $rule->getName());
    }
}
