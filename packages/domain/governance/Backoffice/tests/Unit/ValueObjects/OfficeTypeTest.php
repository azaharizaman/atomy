<?php

declare(strict_types=1);

namespace Nexus\Backoffice\Tests\Unit\ValueObjects;

use PHPUnit\Framework\TestCase;
use Nexus\Backoffice\ValueObjects\OfficeType;

/**
 * Unit tests for OfficeType enum.
 */
class OfficeTypeTest extends TestCase
{
    public function test_head_office_type_value(): void
    {
        $this->assertSame('head_office', OfficeType::HEAD_OFFICE->value);
    }

    public function test_branch_type_value(): void
    {
        $this->assertSame('branch', OfficeType::BRANCH->value);
    }

    public function test_regional_type_value(): void
    {
        $this->assertSame('regional', OfficeType::REGIONAL->value);
    }

    public function test_satellite_type_value(): void
    {
        $this->assertSame('satellite', OfficeType::SATELLITE->value);
    }

    public function test_virtual_type_value(): void
    {
        $this->assertSame('virtual', OfficeType::VIRTUAL->value);
    }

    public function test_is_head_office_returns_true_for_head_office(): void
    {
        $this->assertTrue(OfficeType::HEAD_OFFICE->isHeadOffice());
    }

    public function test_is_head_office_returns_false_for_other_types(): void
    {
        $this->assertFalse(OfficeType::BRANCH->isHeadOffice());
        $this->assertFalse(OfficeType::REGIONAL->isHeadOffice());
        $this->assertFalse(OfficeType::SATELLITE->isHeadOffice());
        $this->assertFalse(OfficeType::VIRTUAL->isHeadOffice());
    }

    public function test_requires_physical_address_for_physical_offices(): void
    {
        $this->assertTrue(OfficeType::HEAD_OFFICE->requiresPhysicalAddress());
        $this->assertTrue(OfficeType::BRANCH->requiresPhysicalAddress());
        $this->assertTrue(OfficeType::REGIONAL->requiresPhysicalAddress());
        $this->assertTrue(OfficeType::SATELLITE->requiresPhysicalAddress());
    }

    public function test_requires_physical_address_false_for_virtual(): void
    {
        $this->assertFalse(OfficeType::VIRTUAL->requiresPhysicalAddress());
    }

    public function test_from_valid_value(): void
    {
        $this->assertSame(OfficeType::HEAD_OFFICE, OfficeType::from('head_office'));
        $this->assertSame(OfficeType::BRANCH, OfficeType::from('branch'));
        $this->assertSame(OfficeType::REGIONAL, OfficeType::from('regional'));
        $this->assertSame(OfficeType::SATELLITE, OfficeType::from('satellite'));
        $this->assertSame(OfficeType::VIRTUAL, OfficeType::from('virtual'));
    }

    public function test_from_invalid_value_throws_exception(): void
    {
        $this->expectException(\ValueError::class);
        OfficeType::from('invalid');
    }

    public function test_all_cases_are_defined(): void
    {
        $cases = OfficeType::cases();
        $this->assertCount(5, $cases);
        $this->assertContains(OfficeType::HEAD_OFFICE, $cases);
        $this->assertContains(OfficeType::BRANCH, $cases);
        $this->assertContains(OfficeType::REGIONAL, $cases);
        $this->assertContains(OfficeType::SATELLITE, $cases);
        $this->assertContains(OfficeType::VIRTUAL, $cases);
    }
}
