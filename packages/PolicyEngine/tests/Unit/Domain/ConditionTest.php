<?php

declare(strict_types=1);

namespace Nexus\PolicyEngine\Tests\Unit\Domain;

use Nexus\PolicyEngine\Domain\Condition;
use Nexus\PolicyEngine\Enums\ConditionOperator;
use PHPUnit\Framework\TestCase;

final class ConditionTest extends TestCase
{
    public function test_numeric_comparators_match_expected_values(): void
    {
        $context = ['amount' => 1500.0];

        self::assertTrue((new Condition('amount', ConditionOperator::GreaterThan, 1000))->matches($context));
        self::assertTrue((new Condition('amount', ConditionOperator::GreaterThanOrEquals, 1500))->matches($context));
        self::assertTrue((new Condition('amount', ConditionOperator::LessThan, 2000))->matches($context));
        self::assertTrue((new Condition('amount', ConditionOperator::LessThanOrEquals, 1500))->matches($context));
        self::assertTrue((new Condition('amount', ConditionOperator::Between, [1000, 1600]))->matches($context));
    }

    public function test_numeric_comparators_return_false_for_non_numeric_values(): void
    {
        $context = ['amount' => 'not_numeric'];
        self::assertFalse((new Condition('amount', ConditionOperator::GreaterThan, 1000))->matches($context));
        self::assertFalse((new Condition('amount', ConditionOperator::GreaterThanOrEquals, 1000))->matches($context));
        self::assertFalse((new Condition('amount', ConditionOperator::LessThan, 1000))->matches($context));
        self::assertFalse((new Condition('amount', ConditionOperator::LessThanOrEquals, 1000))->matches($context));
        self::assertFalse((new Condition('amount', ConditionOperator::Between, [1000, 2000]))->matches($context));
    }
}
