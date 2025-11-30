<?php

declare(strict_types=1);

namespace Nexus\Tax\Tests\Unit\Enums;

use Nexus\Tax\Enums\TaxLevel;
use PHPUnit\Framework\TestCase;

final class TaxLevelTest extends TestCase
{
    public function test_it_has_expected_cases(): void
    {
        $this->assertSame('federal', TaxLevel::Federal->value);
        $this->assertSame('state', TaxLevel::State->value);
        $this->assertSame('local', TaxLevel::Local->value);
        $this->assertSame('municipal', TaxLevel::Municipal->value);
    }

    public function test_get_hierarchy_order_returns_correct_values(): void
    {
        $this->assertSame(1, TaxLevel::Federal->getHierarchyOrder());
        $this->assertSame(2, TaxLevel::State->getHierarchyOrder());
        $this->assertSame(3, TaxLevel::Local->getHierarchyOrder());
        $this->assertSame(4, TaxLevel::Municipal->getHierarchyOrder());
    }

    public function test_is_higher_than_returns_true_when_higher(): void
    {
        $this->assertTrue(TaxLevel::Federal->isHigherThan(TaxLevel::State));
        $this->assertTrue(TaxLevel::State->isHigherThan(TaxLevel::Local));
        $this->assertTrue(TaxLevel::Local->isHigherThan(TaxLevel::Municipal));
    }

    public function test_is_higher_than_returns_false_when_same_or_lower(): void
    {
        $this->assertFalse(TaxLevel::Federal->isHigherThan(TaxLevel::Federal));
        $this->assertFalse(TaxLevel::State->isHigherThan(TaxLevel::Federal));
        $this->assertFalse(TaxLevel::Municipal->isHigherThan(TaxLevel::Local));
    }

    public function test_get_parent_levels_returns_higher_levels(): void
    {
        $parents = TaxLevel::Municipal->getParentLevels();

        $this->assertCount(3, $parents);
        $this->assertContains(TaxLevel::Federal, $parents);
        $this->assertContains(TaxLevel::State, $parents);
        $this->assertContains(TaxLevel::Local, $parents);
    }

    public function test_get_parent_levels_returns_empty_for_federal(): void
    {
        $parents = TaxLevel::Federal->getParentLevels();

        $this->assertCount(0, $parents);
    }
}
