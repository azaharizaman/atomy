<?php

declare(strict_types=1);

namespace Nexus\Project\Tests\Unit\ValueObjects;

use DateTimeImmutable;
use Nexus\Project\Enums\ProjectStatus;
use Nexus\Project\ValueObjects\ProjectSummary;
use PHPUnit\Framework\TestCase;

final class ProjectSummaryTest extends TestCase
{
    public function test_empty_name_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Project name cannot be empty');
        new ProjectSummary(
            'p1',
            '',
            'c1',
            new DateTimeImmutable(),
            new DateTimeImmutable('+1 year'),
            'pm1',
            ProjectStatus::Active
        );
    }

    public function test_empty_project_manager_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Project manager must be assigned');
        new ProjectSummary(
            'p1',
            'Name',
            'c1',
            new DateTimeImmutable(),
            new DateTimeImmutable(),
            '',
            ProjectStatus::Active
        );
    }

    public function test_completion_percentage_out_of_range_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new ProjectSummary(
            'p1',
            'Name',
            'c1',
            new DateTimeImmutable(),
            new DateTimeImmutable(),
            'pm1',
            ProjectStatus::Active,
            'fixed_price',
            150.0
        );
    }

    public function test_is_completed(): void
    {
        $p = new ProjectSummary(
            'p1',
            'Name',
            'c1',
            new DateTimeImmutable(),
            new DateTimeImmutable(),
            'pm1',
            ProjectStatus::Completed
        );
        self::assertTrue($p->isCompleted());
    }
}
