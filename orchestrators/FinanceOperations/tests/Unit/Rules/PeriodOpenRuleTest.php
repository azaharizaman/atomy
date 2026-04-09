<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\Tests\Unit\Rules;

use Nexus\FinanceOperations\Contracts\PeriodQueryInterface;
use Nexus\FinanceOperations\DTOs\RuleContexts\PeriodOpenRuleContext;
use Nexus\FinanceOperations\Rules\PeriodOpenRule;
use PHPUnit\Framework\TestCase;

final class PeriodOpenRuleTest extends TestCase
{
    public function testPeriodIsOpenUsingIsOpenMethod(): void
    {
        $rule = new PeriodOpenRule(new class implements PeriodQueryInterface {
            public function getPeriod(string $tenantId, string $periodId): ?object
            {
                return new class {
                    public function isOpen(): bool
                    {
                        return true;
                    }
                };
            }
        });

        $result = $rule->check(new PeriodOpenRuleContext(
            tenantId: 'tenant-001',
            periodId: '2026-01',
        ));

        $this->assertTrue($result->passed);
        $this->assertEquals('period_open', $result->ruleName);
    }

    public function testPeriodIsClosedFailsValidation(): void
    {
        $rule = new PeriodOpenRule(new class implements PeriodQueryInterface {
            public function getPeriod(string $tenantId, string $periodId): ?object
            {
                return new class {
                    public function isOpen(): bool
                    {
                        return false;
                    }
                    public function getStatus(): string
                    {
                        return 'closed';
                    }
                };
            }
        });

        $result = $rule->check(new PeriodOpenRuleContext(
            tenantId: 'tenant-001',
            periodId: '2025-12',
        ));

        $this->assertFalse($result->passed);
        $this->assertStringContainsString('not open', $result->message);
    }

    public function testPeriodNotFoundFailsValidation(): void
    {
        $rule = new PeriodOpenRule(new class implements PeriodQueryInterface {
            public function getPeriod(string $tenantId, string $periodId): ?object
            {
                return null;
            }
        });

        $result = $rule->check(new PeriodOpenRuleContext(
            tenantId: 'tenant-001',
            periodId: 'non-existent',
        ));

        $this->assertFalse($result->passed);
        $this->assertStringContainsString('not found', $result->message);
    }

    public function testMissingPeriodIdFailsValidation(): void
    {
        $rule = new PeriodOpenRule(new class implements PeriodQueryInterface {
            public function getPeriod(string $tenantId, string $periodId): ?object
            {
                return null;
            }
        });

        $result = $rule->check(new PeriodOpenRuleContext(
            tenantId: 'tenant-001',
            periodId: '',
        ));

        $this->assertFalse($result->passed);
        $this->assertStringContainsString('required', $result->message);
    }

    public function testMissingTenantIdFailsValidation(): void
    {
        $rule = new PeriodOpenRule(new class implements PeriodQueryInterface {
            public function getPeriod(string $tenantId, string $periodId): ?object
            {
                return null;
            }
        });

        $result = $rule->check(new PeriodOpenRuleContext(
            tenantId: '   ',
            periodId: '2026-01',
        ));

        $this->assertFalse($result->passed);
        $this->assertSame(['missing_field' => 'tenantId'], $result->violations);
    }

    public function testGetNameReturnsPeriodOpen(): void
    {
        $rule = new PeriodOpenRule(new class implements PeriodQueryInterface {
            public function getPeriod(string $tenantId, string $periodId): ?object
            {
                return null;
            }
        });

        $this->assertEquals('period_open', $rule->getName());
    }
}
