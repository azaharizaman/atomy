<?php

declare(strict_types=1);

namespace Nexus\Project\Tests\Unit\Enums;

use Nexus\Project\Enums\ProjectStatus;
use PHPUnit\Framework\TestCase;

final class ProjectStatusTest extends TestCase
{
    public function test_completed_is_terminal(): void
    {
        self::assertTrue(ProjectStatus::Completed->isTerminal());
    }

    public function test_allows_lessons_learned_when_completed(): void
    {
        self::assertTrue(ProjectStatus::Completed->allowsLessonsLearned());
        self::assertTrue(ProjectStatus::Cancelled->allowsLessonsLearned());
        self::assertFalse(ProjectStatus::Active->allowsLessonsLearned());
    }
}
