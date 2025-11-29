<?php

declare(strict_types=1);

namespace Nexus\Backoffice\Tests\Unit\ValueObjects;

use PHPUnit\Framework\TestCase;
use Nexus\Backoffice\ValueObjects\UnitStatus;

/**
 * Unit tests for UnitStatus enum.
 */
class UnitStatusTest extends TestCase
{
    public function test_active_status_value(): void
    {
        $this->assertSame('active', UnitStatus::ACTIVE->value);
    }

    public function test_inactive_status_value(): void
    {
        $this->assertSame('inactive', UnitStatus::INACTIVE->value);
    }

    public function test_completed_status_value(): void
    {
        $this->assertSame('completed', UnitStatus::COMPLETED->value);
    }

    public function test_disbanded_status_value(): void
    {
        $this->assertSame('disbanded', UnitStatus::DISBANDED->value);
    }

    public function test_is_active_returns_true_for_active(): void
    {
        $this->assertTrue(UnitStatus::ACTIVE->isActive());
    }

    public function test_is_active_returns_false_for_other_statuses(): void
    {
        $this->assertFalse(UnitStatus::INACTIVE->isActive());
        $this->assertFalse(UnitStatus::COMPLETED->isActive());
        $this->assertFalse(UnitStatus::DISBANDED->isActive());
    }

    public function test_is_ended_returns_true_for_completed(): void
    {
        $this->assertTrue(UnitStatus::COMPLETED->isEnded());
    }

    public function test_is_ended_returns_true_for_disbanded(): void
    {
        $this->assertTrue(UnitStatus::DISBANDED->isEnded());
    }

    public function test_is_ended_returns_false_for_active(): void
    {
        $this->assertFalse(UnitStatus::ACTIVE->isEnded());
    }

    public function test_is_ended_returns_false_for_inactive(): void
    {
        $this->assertFalse(UnitStatus::INACTIVE->isEnded());
    }

    public function test_from_valid_value(): void
    {
        $this->assertSame(UnitStatus::ACTIVE, UnitStatus::from('active'));
        $this->assertSame(UnitStatus::INACTIVE, UnitStatus::from('inactive'));
        $this->assertSame(UnitStatus::COMPLETED, UnitStatus::from('completed'));
        $this->assertSame(UnitStatus::DISBANDED, UnitStatus::from('disbanded'));
    }

    public function test_from_invalid_value_throws_exception(): void
    {
        $this->expectException(\ValueError::class);
        UnitStatus::from('invalid');
    }

    public function test_all_cases_are_defined(): void
    {
        $cases = UnitStatus::cases();
        $this->assertCount(4, $cases);
        $this->assertContains(UnitStatus::ACTIVE, $cases);
        $this->assertContains(UnitStatus::INACTIVE, $cases);
        $this->assertContains(UnitStatus::COMPLETED, $cases);
        $this->assertContains(UnitStatus::DISBANDED, $cases);
    }
}
