<?php

declare(strict_types=1);

namespace Nexus\Backoffice\Tests\Unit\ValueObjects;

use PHPUnit\Framework\TestCase;
use Nexus\Backoffice\ValueObjects\DepartmentStatus;

/**
 * Unit tests for DepartmentStatus enum.
 */
class DepartmentStatusTest extends TestCase
{
    public function test_active_status_value(): void
    {
        $this->assertSame('active', DepartmentStatus::ACTIVE->value);
    }

    public function test_inactive_status_value(): void
    {
        $this->assertSame('inactive', DepartmentStatus::INACTIVE->value);
    }

    public function test_suspended_status_value(): void
    {
        $this->assertSame('suspended', DepartmentStatus::SUSPENDED->value);
    }

    public function test_is_active_returns_true_for_active(): void
    {
        $this->assertTrue(DepartmentStatus::ACTIVE->isActive());
    }

    public function test_is_active_returns_false_for_inactive(): void
    {
        $this->assertFalse(DepartmentStatus::INACTIVE->isActive());
    }

    public function test_is_active_returns_false_for_suspended(): void
    {
        $this->assertFalse(DepartmentStatus::SUSPENDED->isActive());
    }

    public function test_from_valid_value(): void
    {
        $this->assertSame(DepartmentStatus::ACTIVE, DepartmentStatus::from('active'));
        $this->assertSame(DepartmentStatus::INACTIVE, DepartmentStatus::from('inactive'));
        $this->assertSame(DepartmentStatus::SUSPENDED, DepartmentStatus::from('suspended'));
    }

    public function test_from_invalid_value_throws_exception(): void
    {
        $this->expectException(\ValueError::class);
        DepartmentStatus::from('invalid');
    }

    public function test_all_cases_are_defined(): void
    {
        $cases = DepartmentStatus::cases();
        $this->assertCount(3, $cases);
        $this->assertContains(DepartmentStatus::ACTIVE, $cases);
        $this->assertContains(DepartmentStatus::INACTIVE, $cases);
        $this->assertContains(DepartmentStatus::SUSPENDED, $cases);
    }
}
