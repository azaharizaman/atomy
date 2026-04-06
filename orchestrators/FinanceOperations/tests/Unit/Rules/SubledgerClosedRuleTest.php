<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\Tests\Unit\Rules;

use Nexus\FinanceOperations\Contracts\PeriodRuleViewInterface;
use Nexus\FinanceOperations\Contracts\PeriodStatusQueryInterface;
use Nexus\FinanceOperations\DTOs\RuleContext;
use Nexus\FinanceOperations\Rules\SubledgerClosedRule;
use PHPUnit\Framework\TestCase;

final class SubledgerClosedRuleTest extends TestCase
{
    public function testSubledgerClosedPassesValidation(): void
    {
        $rule = new SubledgerClosedRule($this->periodManager(isClosed: true));

        $result = $rule->check(
            RuleContext::forSubledgerClosure('tenant-001', '2026-01', 'AR')
        );

        self::assertTrue($result->passed);
    }

    public function testSubledgerNotClosedFailsValidation(): void
    {
        $rule = new SubledgerClosedRule($this->periodManager(isClosed: false));

        $result = $rule->check(
            RuleContext::forSubledgerClosure('tenant-001', '2026-01', 'AR')
        );

        self::assertFalse($result->passed);
        self::assertStringContainsString('not closed', $result->message);
    }

    public function testMissingPeriodIdFailsValidation(): void
    {
        $rule = new SubledgerClosedRule($this->periodManager(isClosed: true));

        $result = $rule->check(
            RuleContext::forSubledgerClosure('tenant-001', '', 'AR')
        );

        self::assertFalse($result->passed);
        self::assertStringContainsString('required', $result->message);
    }

    public function testMissingSubledgerTypeFailsValidation(): void
    {
        $rule = new SubledgerClosedRule($this->periodManager(isClosed: true));

        $result = $rule->check(
            RuleContext::forSubledgerClosure('tenant-001', '2026-01', '')
        );

        self::assertFalse($result->passed);
        self::assertStringContainsString('required', $result->message);
    }

    public function testGetNameReturnsSubledgerClosed(): void
    {
        $rule = new SubledgerClosedRule($this->periodManager(isClosed: true));

        self::assertSame('subledger_closed', $rule->getName());
    }

    private function periodManager(bool $isClosed): PeriodStatusQueryInterface
    {
        return new class($isClosed) implements PeriodStatusQueryInterface {
            public function __construct(private bool $isClosed) {}

            public function getPeriod(string $tenantId, string $periodId): ?PeriodRuleViewInterface
            {
                return null;
            }

            public function isSubledgerClosed(string $tenantId, string $periodId, string $subledgerType): bool
            {
                return $this->isClosed;
            }
        };
    }
}
