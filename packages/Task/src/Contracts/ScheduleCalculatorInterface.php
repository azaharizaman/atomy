<?php

declare(strict_types=1);

namespace Nexus\Task\Contracts;

use Nexus\Task\ValueObjects\TaskSummary;

/**
 * Early/late start and finish dates for Gantt (FUN-PRO-0570).
 *
 * @phpstan-type ScheduleEntry array{earlyStart: \DateTimeImmutable, earlyFinish: \DateTimeImmutable, lateStart: \DateTimeImmutable, lateFinish: \DateTimeImmutable}
 */
interface ScheduleCalculatorInterface
{
    /**
     * Compute early/late start and finish for each task given dependencies and optional start/end.
     *
     * @param list<TaskSummary> $tasks Tasks with dueDate or null; order does not matter.
     * @param array<string, list<string>> $taskIdToPredecessorIds Predecessor map.
     * @param \DateTimeImmutable|null $projectStart Optional project start for forward pass.
     * @return array<string, ScheduleEntry> taskId => schedule entry
     */
    public function computeSchedule(array $tasks, array $taskIdToPredecessorIds, ?\DateTimeImmutable $projectStart = null): array;
}
