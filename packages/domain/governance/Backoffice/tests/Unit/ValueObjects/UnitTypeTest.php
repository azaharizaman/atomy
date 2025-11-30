<?php

declare(strict_types=1);

namespace Nexus\Backoffice\Tests\Unit\ValueObjects;

use PHPUnit\Framework\TestCase;
use Nexus\Backoffice\ValueObjects\UnitType;

/**
 * Unit tests for UnitType enum.
 */
class UnitTypeTest extends TestCase
{
    public function test_project_team_type_value(): void
    {
        $this->assertSame('project_team', UnitType::PROJECT_TEAM->value);
    }

    public function test_committee_type_value(): void
    {
        $this->assertSame('committee', UnitType::COMMITTEE->value);
    }

    public function test_task_force_type_value(): void
    {
        $this->assertSame('task_force', UnitType::TASK_FORCE->value);
    }

    public function test_working_group_type_value(): void
    {
        $this->assertSame('working_group', UnitType::WORKING_GROUP->value);
    }

    public function test_center_of_excellence_type_value(): void
    {
        $this->assertSame('center_of_excellence', UnitType::CENTER_OF_EXCELLENCE->value);
    }

    public function test_is_temporary_by_nature_for_project_team(): void
    {
        $this->assertTrue(UnitType::PROJECT_TEAM->isTemporaryByNature());
    }

    public function test_is_temporary_by_nature_for_task_force(): void
    {
        $this->assertTrue(UnitType::TASK_FORCE->isTemporaryByNature());
    }

    public function test_is_temporary_by_nature_for_working_group(): void
    {
        $this->assertTrue(UnitType::WORKING_GROUP->isTemporaryByNature());
    }

    public function test_is_not_temporary_by_nature_for_committee(): void
    {
        $this->assertFalse(UnitType::COMMITTEE->isTemporaryByNature());
    }

    public function test_is_not_temporary_by_nature_for_center_of_excellence(): void
    {
        $this->assertFalse(UnitType::CENTER_OF_EXCELLENCE->isTemporaryByNature());
    }

    public function test_from_valid_value(): void
    {
        $this->assertSame(UnitType::PROJECT_TEAM, UnitType::from('project_team'));
        $this->assertSame(UnitType::COMMITTEE, UnitType::from('committee'));
        $this->assertSame(UnitType::TASK_FORCE, UnitType::from('task_force'));
        $this->assertSame(UnitType::WORKING_GROUP, UnitType::from('working_group'));
        $this->assertSame(UnitType::CENTER_OF_EXCELLENCE, UnitType::from('center_of_excellence'));
    }

    public function test_from_invalid_value_throws_exception(): void
    {
        $this->expectException(\ValueError::class);
        UnitType::from('invalid');
    }

    public function test_all_cases_are_defined(): void
    {
        $cases = UnitType::cases();
        $this->assertCount(5, $cases);
        $this->assertContains(UnitType::PROJECT_TEAM, $cases);
        $this->assertContains(UnitType::COMMITTEE, $cases);
        $this->assertContains(UnitType::TASK_FORCE, $cases);
        $this->assertContains(UnitType::WORKING_GROUP, $cases);
        $this->assertContains(UnitType::CENTER_OF_EXCELLENCE, $cases);
    }
}
