<?php

declare(strict_types=1);

namespace Nexus\Backoffice\Tests\Unit\ValueObjects;

use PHPUnit\Framework\TestCase;
use Nexus\Backoffice\ValueObjects\DepartmentType;

/**
 * Unit tests for DepartmentType enum.
 */
class DepartmentTypeTest extends TestCase
{
    public function test_functional_type_value(): void
    {
        $this->assertSame('functional', DepartmentType::FUNCTIONAL->value);
    }

    public function test_divisional_type_value(): void
    {
        $this->assertSame('divisional', DepartmentType::DIVISIONAL->value);
    }

    public function test_matrix_type_value(): void
    {
        $this->assertSame('matrix', DepartmentType::MATRIX->value);
    }

    public function test_project_based_type_value(): void
    {
        $this->assertSame('project_based', DepartmentType::PROJECT_BASED->value);
    }

    public function test_from_valid_value(): void
    {
        $this->assertSame(DepartmentType::FUNCTIONAL, DepartmentType::from('functional'));
        $this->assertSame(DepartmentType::DIVISIONAL, DepartmentType::from('divisional'));
        $this->assertSame(DepartmentType::MATRIX, DepartmentType::from('matrix'));
        $this->assertSame(DepartmentType::PROJECT_BASED, DepartmentType::from('project_based'));
    }

    public function test_from_invalid_value_throws_exception(): void
    {
        $this->expectException(\ValueError::class);
        DepartmentType::from('invalid');
    }

    public function test_all_cases_are_defined(): void
    {
        $cases = DepartmentType::cases();
        $this->assertCount(4, $cases);
        $this->assertContains(DepartmentType::FUNCTIONAL, $cases);
        $this->assertContains(DepartmentType::DIVISIONAL, $cases);
        $this->assertContains(DepartmentType::MATRIX, $cases);
        $this->assertContains(DepartmentType::PROJECT_BASED, $cases);
    }
}
