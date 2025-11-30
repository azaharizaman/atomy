<?php

declare(strict_types=1);

namespace Nexus\Backoffice\Tests\Unit\ValueObjects;

use PHPUnit\Framework\TestCase;
use Nexus\Backoffice\ValueObjects\OfficeStatus;

/**
 * Unit tests for OfficeStatus enum.
 */
class OfficeStatusTest extends TestCase
{
    public function test_active_status_value(): void
    {
        $this->assertSame('active', OfficeStatus::ACTIVE->value);
    }

    public function test_inactive_status_value(): void
    {
        $this->assertSame('inactive', OfficeStatus::INACTIVE->value);
    }

    public function test_temporary_status_value(): void
    {
        $this->assertSame('temporary', OfficeStatus::TEMPORARY->value);
    }

    public function test_closed_status_value(): void
    {
        $this->assertSame('closed', OfficeStatus::CLOSED->value);
    }

    public function test_is_active_returns_true_for_active_status(): void
    {
        $this->assertTrue(OfficeStatus::ACTIVE->isActive());
    }

    public function test_is_active_returns_true_for_temporary_status(): void
    {
        $this->assertTrue(OfficeStatus::TEMPORARY->isActive());
    }

    public function test_is_active_returns_false_for_inactive_status(): void
    {
        $this->assertFalse(OfficeStatus::INACTIVE->isActive());
    }

    public function test_is_active_returns_false_for_closed_status(): void
    {
        $this->assertFalse(OfficeStatus::CLOSED->isActive());
    }

    public function test_from_valid_value(): void
    {
        $this->assertSame(OfficeStatus::ACTIVE, OfficeStatus::from('active'));
        $this->assertSame(OfficeStatus::INACTIVE, OfficeStatus::from('inactive'));
        $this->assertSame(OfficeStatus::TEMPORARY, OfficeStatus::from('temporary'));
        $this->assertSame(OfficeStatus::CLOSED, OfficeStatus::from('closed'));
    }

    public function test_from_invalid_value_throws_exception(): void
    {
        $this->expectException(\ValueError::class);
        OfficeStatus::from('invalid');
    }

    public function test_all_cases_are_defined(): void
    {
        $cases = OfficeStatus::cases();
        $this->assertCount(4, $cases);
        $this->assertContains(OfficeStatus::ACTIVE, $cases);
        $this->assertContains(OfficeStatus::INACTIVE, $cases);
        $this->assertContains(OfficeStatus::TEMPORARY, $cases);
        $this->assertContains(OfficeStatus::CLOSED, $cases);
    }
}
