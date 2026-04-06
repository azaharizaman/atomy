<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\Tests\Unit\Rules;

use Nexus\FinanceOperations\Contracts\PeriodRuleViewInterface;
use Nexus\FinanceOperations\Contracts\PeriodStatusQueryInterface;
use Nexus\FinanceOperations\DTOs\RuleContext;
use Nexus\FinanceOperations\Rules\PeriodOpenRule;
use PHPUnit\Framework\TestCase;

final class PeriodOpenRuleTest extends TestCase
{
    public function testPeriodIsOpenPassesValidation(): void
    {
        $rule = new PeriodOpenRule(
            $this->periodManager(found: true, open: true, status: 'open')
        );

        $result = $rule->check(
            RuleContext::forPeriodValidation('tenant-001', '2026-01')
        );

        self::assertTrue($result->passed);
    }

    public function testMissingPeriodIdFailsValidation(): void
    {
        $rule = new PeriodOpenRule(
            $this->periodManager(found: true, open: true, status: 'open')
        );

        $result = $rule->check(
            RuleContext::forPeriodValidation('tenant-001', '')
        );

        self::assertFalse($result->passed);
        self::assertStringContainsString('required', $result->message);
    }

    public function testPeriodNotFoundFailsValidation(): void
    {
        $rule = new PeriodOpenRule(
            $this->periodManager(found: false, open: false, status: 'unknown')
        );

        $result = $rule->check(
            RuleContext::forPeriodValidation('tenant-001', 'missing')
        );

        self::assertFalse($result->passed);
        self::assertStringContainsString('not found', $result->message);
    }

    public function testClosedPeriodFailsValidation(): void
    {
        $rule = new PeriodOpenRule(
            $this->periodManager(found: true, open: false, status: 'closed')
        );

        $result = $rule->check(
            RuleContext::forPeriodValidation('tenant-001', '2025-12')
        );

        self::assertFalse($result->passed);
        self::assertStringContainsString('not open', $result->message);
    }

    public function testGetNameReturnsPeriodOpen(): void
    {
        $rule = new PeriodOpenRule(
            $this->periodManager(found: true, open: true, status: 'open')
        );

        self::assertSame('period_open', $rule->getName());
    }

    private function periodManager(bool $found, bool $open, string $status): PeriodStatusQueryInterface
    {
        return new class($found, $open, $status) implements PeriodStatusQueryInterface {
            public function __construct(
                private bool $found,
                private bool $open,
                private string $status,
            ) {}

            public function getPeriod(string $tenantId, string $periodId): ?PeriodRuleViewInterface
            {
                if (!$this->found) {
                    return null;
                }

                return new class($this->open, $this->status) implements PeriodRuleViewInterface {
                    public function __construct(
                        private bool $open,
                        private string $status,
                    ) {}

                    public function isOpen(): bool
                    {
                        return $this->open;
                    }

                    public function getStatus(): string
                    {
                        return $this->status;
                    }
                };
            }

            public function isSubledgerClosed(string $tenantId, string $periodId, string $subledgerType): bool
            {
                return false;
            }
        };
    }
}
