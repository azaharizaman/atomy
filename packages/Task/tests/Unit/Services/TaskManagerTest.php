<?php

declare(strict_types=1);

namespace Nexus\Task\Tests\Unit\Services;

use Nexus\Task\Contracts\DependencyGraphInterface;
use Nexus\Task\Contracts\TaskPersistInterface;
use Nexus\Task\Exceptions\CircularDependencyException;
use Nexus\Task\Services\TaskManager;
use Nexus\Task\ValueObjects\TaskSummary;
use Nexus\Task\Enums\TaskPriority;
use Nexus\Task\Enums\TaskStatus;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

final class TaskManagerTest extends TestCase
{
    private TaskPersistInterface&MockObject $persist;
    private DependencyGraphInterface&MockObject $dependencyGraph;
    private TaskManager $manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->persist = $this->createMock(TaskPersistInterface::class);
        $this->dependencyGraph = $this->createMock(DependencyGraphInterface::class);
        $this->manager = new TaskManager($this->persist, $this->dependencyGraph);
    }

    public function test_create_persists_when_no_cycle(): void
    {
        $task = new TaskSummary(
            't1',
            'Title',
            'Desc',
            TaskStatus::Pending,
            TaskPriority::Medium,
            null,
            [],
            []
        );
        $this->dependencyGraph->expects(self::once())
            ->method('hasCycle')
            ->with(['t1' => []])
            ->willReturn(false);
        $this->persist->expects(self::once())->method('persist')->with($task);
        $this->manager->create($task, []);
    }

    public function test_create_throws_when_cycle_detected(): void
    {
        $task = new TaskSummary(
            't1',
            'Title',
            'Desc',
            TaskStatus::Pending,
            TaskPriority::Medium,
            null,
            [],
            ['t2']
        );
        $existing = ['t2' => ['t1']];
        $graph = ['t2' => ['t1'], 't1' => ['t2']];
        $this->dependencyGraph->expects(self::once())
            ->method('hasCycle')
            ->with(self::callback(static function (array $g) use ($graph) {
                return isset($g['t1'], $g['t2']) && $g['t1'] === ['t2'] && $g['t2'] === ['t1'];
            }))
            ->willReturn(true);
        $this->persist->expects(self::never())->method('persist');
        $this->expectException(CircularDependencyException::class);
        $this->manager->create($task, $existing);
    }

    public function test_validate_dependencies_throws_on_cycle(): void
    {
        $this->dependencyGraph->expects(self::once())
            ->method('hasCycle')
            ->with(['a' => ['b'], 'b' => ['a']])
            ->willReturn(true);
        $this->expectException(CircularDependencyException::class);
        $this->manager->validateDependencies(['a' => ['b'], 'b' => ['a']]);
    }

    public function test_validate_dependencies_passes_when_acyclic(): void
    {
        $this->dependencyGraph->expects(self::once())
            ->method('hasCycle')
            ->willReturn(false);
        $this->manager->validateDependencies(['a' => [], 'b' => ['a']]);
    }

    public function test_update_persists_when_graph_valid(): void
    {
        $task = new TaskSummary(
            't1',
            'Title',
            'Desc',
            TaskStatus::Completed,
            TaskPriority::High,
            null,
            ['u1'],
            []
        );
        $this->dependencyGraph->expects(self::once())->method('hasCycle')->willReturn(false);
        $this->persist->expects(self::once())->method('persist')->with($task);
        $this->manager->update($task, ['t1' => []]);
    }
}
