<?php

declare(strict_types=1);

namespace Nexus\Backoffice\Tests\Unit\ValueObjects;

use PHPUnit\Framework\TestCase;
use Nexus\Backoffice\ValueObjects\StaffType;

/**
 * Unit tests for StaffType enum.
 */
class StaffTypeTest extends TestCase
{
    public function test_permanent_type_value(): void
    {
        $this->assertSame('permanent', StaffType::PERMANENT->value);
    }

    public function test_contract_type_value(): void
    {
        $this->assertSame('contract', StaffType::CONTRACT->value);
    }

    public function test_temporary_type_value(): void
    {
        $this->assertSame('temporary', StaffType::TEMPORARY->value);
    }

    public function test_intern_type_value(): void
    {
        $this->assertSame('intern', StaffType::INTERN->value);
    }

    public function test_consultant_type_value(): void
    {
        $this->assertSame('consultant', StaffType::CONSULTANT->value);
    }

    public function test_is_permanent_returns_true_for_permanent(): void
    {
        $this->assertTrue(StaffType::PERMANENT->isPermanent());
    }

    public function test_is_permanent_returns_false_for_other_types(): void
    {
        $this->assertFalse(StaffType::CONTRACT->isPermanent());
        $this->assertFalse(StaffType::TEMPORARY->isPermanent());
        $this->assertFalse(StaffType::INTERN->isPermanent());
        $this->assertFalse(StaffType::CONSULTANT->isPermanent());
    }

    public function test_is_contractual_returns_true_for_contract(): void
    {
        $this->assertTrue(StaffType::CONTRACT->isContractual());
    }

    public function test_is_contractual_returns_true_for_temporary(): void
    {
        $this->assertTrue(StaffType::TEMPORARY->isContractual());
    }

    public function test_is_contractual_returns_true_for_consultant(): void
    {
        $this->assertTrue(StaffType::CONSULTANT->isContractual());
    }

    public function test_is_contractual_returns_false_for_permanent(): void
    {
        $this->assertFalse(StaffType::PERMANENT->isContractual());
    }

    public function test_is_contractual_returns_false_for_intern(): void
    {
        $this->assertFalse(StaffType::INTERN->isContractual());
    }

    public function test_from_valid_value(): void
    {
        $this->assertSame(StaffType::PERMANENT, StaffType::from('permanent'));
        $this->assertSame(StaffType::CONTRACT, StaffType::from('contract'));
        $this->assertSame(StaffType::TEMPORARY, StaffType::from('temporary'));
        $this->assertSame(StaffType::INTERN, StaffType::from('intern'));
        $this->assertSame(StaffType::CONSULTANT, StaffType::from('consultant'));
    }

    public function test_from_invalid_value_throws_exception(): void
    {
        $this->expectException(\ValueError::class);
        StaffType::from('invalid');
    }

    public function test_all_cases_are_defined(): void
    {
        $cases = StaffType::cases();
        $this->assertCount(5, $cases);
        $this->assertContains(StaffType::PERMANENT, $cases);
        $this->assertContains(StaffType::CONTRACT, $cases);
        $this->assertContains(StaffType::TEMPORARY, $cases);
        $this->assertContains(StaffType::INTERN, $cases);
        $this->assertContains(StaffType::CONSULTANT, $cases);
    }
}
