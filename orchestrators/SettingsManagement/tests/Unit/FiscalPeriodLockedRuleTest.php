<?php

declare(strict_types=1);

namespace Nexus\SettingsManagement\Tests\Unit;

use PHPUnit\Framework\TestCase;

use Nexus\SettingsManagement\DTOs\FiscalPeriod\PeriodOperationType;
use Nexus\SettingsManagement\Rules\FiscalPeriodLockedRule;

final class FiscalPeriodLockedRuleTest extends TestCase
{
    public function testEvaluateTreatsOpenStatusAsNotClosed(): void
    {
        $rule = new FiscalPeriodLockedRule();

        $result = $rule->evaluate([
            'periodId' => 'FY2026-P01',
            'operationType' => PeriodOperationType::TRANSACTION->value,
            'period' => [
                'status' => 'open',
                'isLocked' => false,
            ],
        ]);

        self::assertTrue($result->passed);
        self::assertNull($result->error);
    }

    public function testEvaluateTreatsMissingStatusAsOpenByDefault(): void
    {
        $rule = new FiscalPeriodLockedRule();

        $result = $rule->evaluate([
            'periodId' => 'FY2026-P02',
            'operationType' => PeriodOperationType::TRANSACTION->value,
            'period' => [
                'isLocked' => false,
            ],
        ]);

        self::assertTrue($result->passed);
        self::assertNull($result->error);
    }

    /**
     * @dataProvider blankStatusProvider
     */
    public function testEvaluateTreatsBlankOrWhitespaceStatusAsOpenByDefault(string $status): void
    {
        $rule = new FiscalPeriodLockedRule();

        $result = $rule->evaluate([
            'periodId' => 'FY2026-P02B',
            'operationType' => PeriodOperationType::TRANSACTION->value,
            'period' => [
                'isLocked' => false,
                'status' => $status,
            ],
        ]);

        self::assertTrue($result->passed);
        self::assertNull($result->error);
    }

    /**
     * @return array<string, array<string>>
     */
    public static function blankStatusProvider(): array
    {
        return [
            'empty string' => [''],
            'whitespace' => ['   '],
            'tab' => ["\t"],
            'newline' => ["\n"],
            'mixed whitespace' => [" \t\n "],
        ];
    }

    public function testEvaluateFailsWhenPeriodIsClosed(): void
    {
        $rule = new FiscalPeriodLockedRule();

        $result = $rule->evaluate([
            'periodId' => 'FY2026-P03',
            'operationType' => PeriodOperationType::TRANSACTION->value,
            'period' => [
                'status' => 'closed',
                'isLocked' => false,
            ],
        ]);

        self::assertFalse($result->passed);
        self::assertSame("Period 'FY2026-P03' is closed and cannot be modified", $result->error);
        self::assertSame(['period_id' => 'FY2026-P03', 'is_closed' => true], $result->details);
    }

    public function testEvaluateAllowsLockedPeriodAdjustmentWhenAllowedUsingStringOperationType(): void
    {
        $rule = new FiscalPeriodLockedRule();

        $result = $rule->evaluate([
            'periodId' => 'FY2026-P04',
            'operationType' => 'adjustment',
            'period' => [
                'status' => 'open',
                'isLocked' => true,
                'allowsAdjustment' => true,
            ],
        ]);

        self::assertTrue($result->passed);
        self::assertNull($result->error);
    }

    public function testEvaluateFailsLockedPeriodAdjustmentWhenNotAllowed(): void
    {
        $rule = new FiscalPeriodLockedRule();

        $result = $rule->evaluate([
            'periodId' => 'FY2026-P05',
            'operationType' => PeriodOperationType::ADJUSTMENT->value,
            'period' => [
                'status' => 'open',
                'isLocked' => true,
                'allowsAdjustment' => false,
            ],
        ]);

        self::assertFalse($result->passed);
        self::assertSame("Period 'FY2026-P05' is locked and does not allow adjustments", $result->error);
        self::assertSame(['period_id' => 'FY2026-P05', 'is_locked' => true], $result->details);
    }

    public function testEvaluateFailsLockedPeriodForNonAdjustmentOperation(): void
    {
        $rule = new FiscalPeriodLockedRule();

        $result = $rule->evaluate([
            'periodId' => 'FY2026-P06',
            'operationType' => PeriodOperationType::TRANSACTION->value,
            'period' => [
                'status' => 'open',
                'isLocked' => true,
                'allowsAdjustment' => true,
            ],
        ]);

        self::assertFalse($result->passed);
        self::assertSame("Period 'FY2026-P06' is locked", $result->error);
        self::assertSame(['period_id' => 'FY2026-P06', 'is_locked' => true], $result->details);
    }
}