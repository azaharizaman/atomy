<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\Tests\Unit\Rules;

use Nexus\FinanceOperations\Contracts\SubledgerPeriodStateInterface;
use Nexus\FinanceOperations\DTOs\RuleContexts\SubledgerClosedRuleContext;
use Nexus\FinanceOperations\Enums\SubledgerType;
use Nexus\FinanceOperations\Rules\SubledgerClosedRule;
use PHPUnit\Framework\TestCase;

final class SubledgerClosedRuleTest extends TestCase
{
    public function testSubledgerClosedPassesValidation(): void
    {
        $rule = new SubledgerClosedRule(new class implements SubledgerPeriodStateInterface {
            public function isSubledgerClosed(string $tenantId, string $periodId, string $subledgerType): bool
            {
                return true;
            }
        });

        $result = $rule->check(new SubledgerClosedRuleContext(
            tenantId: 'tenant-001',
            periodId: '2026-01',
            subledgerType: SubledgerType::AR,
        ));

        $this->assertTrue($result->passed);
        $this->assertEquals('subledger_closed', $result->ruleName);
    }

    public function testSubledgerNotClosedFailsValidation(): void
    {
        $rule = new SubledgerClosedRule(new class implements SubledgerPeriodStateInterface {
            public function isSubledgerClosed(string $tenantId, string $periodId, string $subledgerType): bool
            {
                return false;
            }
        });

        $result = $rule->check(new SubledgerClosedRuleContext(
            tenantId: 'tenant-001',
            periodId: '2026-01',
            subledgerType: SubledgerType::AR,
        ));

        $this->assertFalse($result->passed);
        $this->assertStringContainsString('not closed', $result->message);
    }

    public function testMissingPeriodIdFailsValidation(): void
    {
        $rule = new SubledgerClosedRule(new class implements SubledgerPeriodStateInterface {
            public function isSubledgerClosed(string $tenantId, string $periodId, string $subledgerType): bool
            {
                return true;
            }
        });

        $result = $rule->check(new SubledgerClosedRuleContext(
            tenantId: 'tenant-001',
            periodId: '   ',
            subledgerType: SubledgerType::AR,
        ));

        $this->assertFalse($result->passed);
        $this->assertStringContainsString('required', $result->message);
    }

    public function testMissingTenantIdFailsValidation(): void
    {
        $rule = new SubledgerClosedRule(new class implements SubledgerPeriodStateInterface {
            public function isSubledgerClosed(string $tenantId, string $periodId, string $subledgerType): bool
            {
                return true;
            }
        });

        $result = $rule->check(new SubledgerClosedRuleContext(
            tenantId: '   ',
            periodId: '2026-01',
            subledgerType: SubledgerType::AR,
        ));

        $this->assertFalse($result->passed);
        $this->assertSame(['missing_field' => 'tenantId'], $result->violations);
    }

    public function testGetNameReturnsSubledgerClosed(): void
    {
        $rule = new SubledgerClosedRule(new class implements SubledgerPeriodStateInterface {
            public function isSubledgerClosed(string $tenantId, string $periodId, string $subledgerType): bool
            {
                return true;
            }
        });

        $this->assertEquals('subledger_closed', $rule->getName());
    }
}
