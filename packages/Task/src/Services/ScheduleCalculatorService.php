<?php

declare(strict_types=1);

namespace Nexus\Task\Services;

use DateTimeImmutable;
use Nexus\Task\Contracts\ScheduleCalculatorInterface;
use Nexus\Task\ValueObjects\TaskSummary;

/**
 * Computes early/late start and finish for Gantt (FUN-PRO-0570).
 * Uses forward pass for early dates, backward pass for late dates.
 * Assumes 1-day duration per task when dueDate is not set.
 */
final readonly class ScheduleCalculatorService implements ScheduleCalculatorInterface
{
    private const string DEFAULT_DURATION = 'P1D';

    public function computeSchedule(array $tasks, array $taskIdToPredecessorIds, ?DateTimeImmutable $projectStart = null): array
    {
        $byId = [];
        foreach ($tasks as $t) {
            if ($t instanceof TaskSummary) {
                $byId[$t->id] = $t;
            }
        }
        $projectStart ??= new DateTimeImmutable('today');
        $early = $this->forwardPass($byId, $taskIdToPredecessorIds, $projectStart);
        $late = $this->backwardPass($byId, $taskIdToPredecessorIds, $early);
        $result = [];
        foreach (array_keys($byId) as $id) {
            $result[$id] = [
                'earlyStart' => $early[$id]['start'],
                'earlyFinish' => $early[$id]['finish'],
                'lateStart' => $late[$id]['start'],
                'lateFinish' => $late[$id]['finish'],
            ];
        }
        return $result;
    }

    /**
     * @param array<string, TaskSummary> $byId
     * @param array<string, list<string>> $taskIdToPredecessorIds
     * @return array<string, array{start: DateTimeImmutable, finish: DateTimeImmutable}>
     */
    private function forwardPass(array $byId, array $taskIdToPredecessorIds, DateTimeImmutable $projectStart): array
    {
        $early = [];
        $remaining = array_fill_keys(array_keys($byId), true);
        $iterations = 0;
        $maxIterations = count($byId) * 2;
        while ($remaining !== [] && $iterations < $maxIterations) {
            $iterations++;
            foreach (array_keys($remaining) as $id) {
                $preds = $taskIdToPredecessorIds[$id] ?? [];
                $allPredDone = true;
                $maxPredFinish = $projectStart;
                foreach ($preds as $predId) {
                    if (isset($remaining[$predId])) {
                        $allPredDone = false;
                        break;
                    }
                    if (isset($early[$predId]) && $early[$predId]['finish'] > $maxPredFinish) {
                        $maxPredFinish = $early[$predId]['finish'];
                    }
                }
                if (!$allPredDone) {
                    continue;
                }
                $task = $byId[$id];
                $start = $maxPredFinish;
                $duration = new \DateInterval(self::DEFAULT_DURATION);
                if ($task->dueDate && $task->dueDate > $start) {
                    $duration = $start->diff($task->dueDate);
                }
                $finish = $start->add($duration);
                if ($task->dueDate && $finish > $task->dueDate) {
                    $finish = $task->dueDate;
                }
                $early[$id] = ['start' => $start, 'finish' => $finish];
                unset($remaining[$id]);
            }
        }
        foreach (array_keys($remaining) as $id) {
            $task = $byId[$id];
            $start = $projectStart;
            $finish = $task->dueDate ?? $start->add(new \DateInterval(self::DEFAULT_DURATION));
            $early[$id] = ['start' => $start, 'finish' => $finish];
        }
        return $early;
    }

    /**
     * @param array<string, TaskSummary> $byId
     * @param array<string, list<string>> $taskIdToPredecessorIds
     * @param array<string, array{start: DateTimeImmutable, finish: DateTimeImmutable}> $early
     * @return array<string, array{start: DateTimeImmutable, finish: DateTimeImmutable}>
     */
    private function backwardPass(array $byId, array $taskIdToPredecessorIds, array $early): array
    {
        $successors = [];
        foreach ($taskIdToPredecessorIds as $taskId => $preds) {
            foreach ($preds as $p) {
                $successors[$p] = $successors[$p] ?? [];
                $successors[$p][] = $taskId;
            }
        }
        $late = [];
        $remaining = array_fill_keys(array_keys($byId), true);
        $iterations = 0;
        $maxIterations = count($byId) * 2;
        while ($remaining !== [] && $iterations < $maxIterations) {
            $iterations++;
            foreach (array_keys($remaining) as $id) {
                $succs = $successors[$id] ?? [];
                $minSuccStart = null;
                foreach ($succs as $s) {
                    if (isset($late[$s]) && ($minSuccStart === null || $late[$s]['start'] < $minSuccStart)) {
                        $minSuccStart = $late[$s]['start'];
                    }
                }
                if ($minSuccStart === null) {
                    $late[$id] = $early[$id];
                    unset($remaining[$id]);
                    continue;
                }
                $task = $byId[$id];
                $finish = $minSuccStart;
                $duration = $task->dueDate && $early[$id]['start'] < $task->dueDate
                    ? $early[$id]['start']->diff($early[$id]['finish'])
                    : new \DateInterval(self::DEFAULT_DURATION);
                $start = $finish->sub($duration);
                if ($start < $early[$id]['start']) {
                    $start = $early[$id]['start'];
                    $finish = $start->add($duration);
                }
                $late[$id] = ['start' => $start, 'finish' => $finish];
                unset($remaining[$id]);
            }
        }
        foreach (array_keys($remaining) as $id) {
            $late[$id] = $early[$id];
        }
        return $late;
    }
}
