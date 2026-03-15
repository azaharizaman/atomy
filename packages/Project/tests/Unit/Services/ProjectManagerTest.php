<?php

declare(strict_types=1);

namespace Nexus\Project\Tests\Unit\Services;

use DateTimeImmutable;
use Nexus\Project\Contracts\IncompleteTaskCountInterface;
use Nexus\Project\Contracts\ProjectPersistInterface;
use Nexus\Project\Enums\ProjectStatus;
use Nexus\Project\Exceptions\ProjectCompletionException;
use Nexus\Project\Services\ProjectManager;
use Nexus\Project\ValueObjects\ProjectSummary;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

final class ProjectManagerTest extends TestCase
{
    private ProjectPersistInterface&MockObject $persist;
    private ProjectManager $manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->persist = $this->createMock(ProjectPersistInterface::class);
        $this->manager = new ProjectManager($this->persist);
    }

    public function test_create_persists(): void
    {
        $project = new ProjectSummary(
            'p1',
            'Project One',
            'c1',
            new DateTimeImmutable(),
            new DateTimeImmutable('+1 year'),
            'pm1',
            ProjectStatus::Active
        );
        $this->persist->expects(self::once())->method('persist')->with($project);
        $this->manager->create($project);
    }

    public function test_update_to_completed_throws_when_incomplete_tasks(): void
    {
        $incomplete = $this->createMock(IncompleteTaskCountInterface::class);
        $incomplete->method('getIncompleteTaskCount')->with('p1')->willReturn(2);
        $manager = new ProjectManager($this->persist, $incomplete);
        $project = new ProjectSummary(
            'p1',
            'Project',
            'c1',
            new DateTimeImmutable(),
            new DateTimeImmutable(),
            'pm1',
            ProjectStatus::Completed
        );
        $this->persist->expects(self::never())->method('persist');
        $this->expectException(ProjectCompletionException::class);
        $this->expectExceptionMessage('incomplete task');
        $manager->update($project);
    }

    public function test_update_to_completed_persists_when_no_incomplete_tasks(): void
    {
        $incomplete = $this->createMock(IncompleteTaskCountInterface::class);
        $incomplete->method('getIncompleteTaskCount')->willReturn(0);
        $manager = new ProjectManager($this->persist, $incomplete);
        $project = new ProjectSummary(
            'p1',
            'Project',
            'c1',
            new DateTimeImmutable(),
            new DateTimeImmutable(),
            'pm1',
            ProjectStatus::Completed
        );
        $this->persist->expects(self::once())->method('persist');
        $manager->update($project);
    }
}
