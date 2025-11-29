<?php

declare(strict_types=1);

namespace Nexus\Backoffice\Tests\Unit\ValueObjects;

use PHPUnit\Framework\TestCase;
use Nexus\Backoffice\ValueObjects\StaffStatus;

/**
 * Unit tests for StaffStatus enum.
 */
class StaffStatusTest extends TestCase
{
    public function test_active_status_value(): void
    {
        $this->assertSame('active', StaffStatus::ACTIVE->value);
    }

    public function test_inactive_status_value(): void
    {
        $this->assertSame('inactive', StaffStatus::INACTIVE->value);
    }

    public function test_on_leave_status_value(): void
    {
        $this->assertSame('on_leave', StaffStatus::ON_LEAVE->value);
    }

    public function test_terminated_status_value(): void
    {
        $this->assertSame('terminated', StaffStatus::TERMINATED->value);
    }

    public function test_is_active_returns_true_for_active(): void
    {
        $this->assertTrue(StaffStatus::ACTIVE->isActive());
    }

    public function test_is_active_returns_true_for_on_leave(): void
    {
        $this->assertTrue(StaffStatus::ON_LEAVE->isActive());
    }

    public function test_is_active_returns_false_for_inactive(): void
    {
        $this->assertFalse(StaffStatus::INACTIVE->isActive());
    }

    public function test_is_active_returns_false_for_terminated(): void
    {
        $this->assertFalse(StaffStatus::TERMINATED->isActive());
    }

    public function test_is_terminated_returns_true_for_terminated(): void
    {
        $this->assertTrue(StaffStatus::TERMINATED->isTerminated());
    }

    public function test_is_terminated_returns_false_for_other_statuses(): void
    {
        $this->assertFalse(StaffStatus::ACTIVE->isTerminated());
        $this->assertFalse(StaffStatus::INACTIVE->isTerminated());
        $this->assertFalse(StaffStatus::ON_LEAVE->isTerminated());
    }

    public function test_can_have_assignments_returns_true_for_active(): void
    {
        $this->assertTrue(StaffStatus::ACTIVE->canHaveAssignments());
    }

    public function test_can_have_assignments_returns_true_for_on_leave(): void
    {
        $this->assertTrue(StaffStatus::ON_LEAVE->canHaveAssignments());
    }

    public function test_can_have_assignments_returns_true_for_inactive(): void
    {
        $this->assertTrue(StaffStatus::INACTIVE->canHaveAssignments());
    }

    public function test_can_have_assignments_returns_false_for_terminated(): void
    {
        $this->assertFalse(StaffStatus::TERMINATED->canHaveAssignments());
    }

    public function test_from_valid_value(): void
    {
        $this->assertSame(StaffStatus::ACTIVE, StaffStatus::from('active'));
        $this->assertSame(StaffStatus::INACTIVE, StaffStatus::from('inactive'));
        $this->assertSame(StaffStatus::ON_LEAVE, StaffStatus::from('on_leave'));
        $this->assertSame(StaffStatus::TERMINATED, StaffStatus::from('terminated'));
    }

    public function test_from_invalid_value_throws_exception(): void
    {
        $this->expectException(\ValueError::class);
        StaffStatus::from('invalid');
    }

    public function test_all_cases_are_defined(): void
    {
        $cases = StaffStatus::cases();
        $this->assertCount(4, $cases);
        $this->assertContains(StaffStatus::ACTIVE, $cases);
        $this->assertContains(StaffStatus::INACTIVE, $cases);
        $this->assertContains(StaffStatus::ON_LEAVE, $cases);
        $this->assertContains(StaffStatus::TERMINATED, $cases);
    }
}
