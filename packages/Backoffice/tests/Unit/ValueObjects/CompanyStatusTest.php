<?php

declare(strict_types=1);

namespace Nexus\Backoffice\Tests\Unit\ValueObjects;

use PHPUnit\Framework\TestCase;
use Nexus\Backoffice\ValueObjects\CompanyStatus;

/**
 * Unit tests for CompanyStatus enum.
 */
class CompanyStatusTest extends TestCase
{
    public function test_active_status_value(): void
    {
        $this->assertSame('active', CompanyStatus::ACTIVE->value);
    }

    public function test_inactive_status_value(): void
    {
        $this->assertSame('inactive', CompanyStatus::INACTIVE->value);
    }

    public function test_suspended_status_value(): void
    {
        $this->assertSame('suspended', CompanyStatus::SUSPENDED->value);
    }

    public function test_dissolved_status_value(): void
    {
        $this->assertSame('dissolved', CompanyStatus::DISSOLVED->value);
    }

    public function test_is_active_returns_true_for_active_status(): void
    {
        $this->assertTrue(CompanyStatus::ACTIVE->isActive());
    }

    public function test_is_active_returns_false_for_inactive_status(): void
    {
        $this->assertFalse(CompanyStatus::INACTIVE->isActive());
    }

    public function test_is_active_returns_false_for_suspended_status(): void
    {
        $this->assertFalse(CompanyStatus::SUSPENDED->isActive());
    }

    public function test_is_active_returns_false_for_dissolved_status(): void
    {
        $this->assertFalse(CompanyStatus::DISSOLVED->isActive());
    }

    public function test_can_have_active_children_returns_true_for_active(): void
    {
        $this->assertTrue(CompanyStatus::ACTIVE->canHaveActiveChildren());
    }

    public function test_can_have_active_children_returns_false_for_inactive(): void
    {
        $this->assertFalse(CompanyStatus::INACTIVE->canHaveActiveChildren());
    }

    public function test_can_have_active_children_returns_false_for_suspended(): void
    {
        $this->assertFalse(CompanyStatus::SUSPENDED->canHaveActiveChildren());
    }

    public function test_can_have_active_children_returns_false_for_dissolved(): void
    {
        $this->assertFalse(CompanyStatus::DISSOLVED->canHaveActiveChildren());
    }

    public function test_from_valid_value(): void
    {
        $this->assertSame(CompanyStatus::ACTIVE, CompanyStatus::from('active'));
        $this->assertSame(CompanyStatus::INACTIVE, CompanyStatus::from('inactive'));
        $this->assertSame(CompanyStatus::SUSPENDED, CompanyStatus::from('suspended'));
        $this->assertSame(CompanyStatus::DISSOLVED, CompanyStatus::from('dissolved'));
    }

    public function test_from_invalid_value_throws_exception(): void
    {
        $this->expectException(\ValueError::class);
        CompanyStatus::from('invalid');
    }

    public function test_try_from_valid_value(): void
    {
        $this->assertSame(CompanyStatus::ACTIVE, CompanyStatus::tryFrom('active'));
    }

    public function test_try_from_invalid_value_returns_null(): void
    {
        $this->assertNull(CompanyStatus::tryFrom('invalid'));
    }

    public function test_all_cases_are_defined(): void
    {
        $cases = CompanyStatus::cases();
        $this->assertCount(4, $cases);
        $this->assertContains(CompanyStatus::ACTIVE, $cases);
        $this->assertContains(CompanyStatus::INACTIVE, $cases);
        $this->assertContains(CompanyStatus::SUSPENDED, $cases);
        $this->assertContains(CompanyStatus::DISSOLVED, $cases);
    }
}
