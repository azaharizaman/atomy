<?php

declare(strict_types=1);

namespace Nexus\ProjectManagementOperations\Tests\Unit\Services;

use Nexus\ProjectManagementOperations\Contracts\ProjectQueryInterface;
use Nexus\ProjectManagementOperations\Contracts\SchedulerQueryInterface;
use Nexus\ProjectManagementOperations\DTOs\MilestoneDTO;
use Nexus\ProjectManagementOperations\Services\TimelineDriftService;
use PHPUnit\Framework\TestCase;

final class TimelineDriftServiceTest extends TestCase
{
    public function test_it_calculates_timeline_drift_correctly(): void
    {
        $projectId = 'proj-123';
        $now = new \DateTimeImmutable('2024-02-01');

        $projectQuery = $this->createMock(ProjectQueryInterface::class);
        $projectQuery->method('getMilestones')->willReturn([
            new MilestoneDTO(
                id: 'm1',
                projectId: $projectId,
                name: 'Milestone 1',
                dueDate: new \DateTimeImmutable('2024-01-15'),
                completedAt: new \DateTimeImmutable('2024-01-14'), // On time
                isBillable: true
            ),
            new MilestoneDTO(
                id: 'm2',
                projectId: $projectId,
                name: 'Milestone 2',
                dueDate: new \DateTimeImmutable('2024-01-30'),
                completedAt: new \DateTimeImmutable('2024-02-01'), // Delayed
                isBillable: true
            ),
            new MilestoneDTO(
                id: 'm3',
                projectId: $projectId,
                name: 'Milestone 3',
                dueDate: new \DateTimeImmutable('2024-02-15'),
                completedAt: null, // Pending
                isBillable: false
            ),
        ]);

        $schedulerQuery = $this->createMock(SchedulerQueryInterface::class);
        $schedulerQuery->method('getScheduledDates')->willReturn([
            'start' => new \DateTimeImmutable('2024-01-01'),
            'end' => new \DateTimeImmutable('2024-03-31')
        ]);

        $service = new TimelineDriftService($projectQuery, $schedulerQuery);
        $health = $service->calculate($projectId, $now);

        $this->assertEquals($projectId, $health->projectId);
        $this->assertEquals(3, $health->totalMilestones);
        $this->assertEquals(2, $health->completedMilestones);
        $this->assertEquals(1, $health->delayedMilestones);
        $this->assertEquals(66.67, round($health->completionPercentage, 2));
        $this->assertCount(1, $health->driftDetails);
        $this->assertEquals('Milestone 2', $health->driftDetails[0]['name']);
    }

    public function test_it_detects_delayed_pending_milestones(): void
    {
        $projectId = 'proj-123';
        $now = new \DateTimeImmutable('2024-02-01');

        $projectQuery = $this->createMock(ProjectQueryInterface::class);
        $projectQuery->method('getMilestones')->willReturn([
            new MilestoneDTO(
                id: 'm1',
                projectId: $projectId,
                name: 'Milestone 1',
                dueDate: new \DateTimeImmutable('2024-01-15'),
                completedAt: null, // Delayed and pending
                isBillable: true
            ),
        ]);

        $service = new TimelineDriftService($projectQuery, $this->createMock(SchedulerQueryInterface::class));
        $health = $service->calculate($projectId, $now);

        $this->assertEquals(1, $health->delayedMilestones);
        $this->assertEquals(0, $health->completedMilestones);
        // diff between 2024-02-01 and 2024-01-15 is 17 days
        $this->assertEquals(17, $health->driftDetails[0]['drift_days']);
    }

    public function test_it_handles_empty_milestones(): void
    {
        $projectQuery = $this->createMock(ProjectQueryInterface::class);
        $projectQuery->method('getMilestones')->willReturn([]);

        $service = new TimelineDriftService($projectQuery, $this->createMock(SchedulerQueryInterface::class));
        $health = $service->calculate('empty');

        $this->assertEquals(0, $health->totalMilestones);
        $this->assertEquals(0.0, $health->completionPercentage);
    }
}
