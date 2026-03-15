<?php

declare(strict_types=1);

namespace Nexus\Task\Services;

use Nexus\Task\Contracts\DependencyGraphInterface;

/**
 * Detects cycles in a task dependency graph (BUS-PRO-0090).
 * Uses DFS-based cycle detection; no framework dependencies.
 */
final readonly class DependencyGraphService implements DependencyGraphInterface
{
    public function hasCycle(array $taskIdToPredecessorIds): bool
    {
        $visited = [];
        $recStack = [];

        foreach (array_keys($taskIdToPredecessorIds) as $taskId) {
            if ($this->visit((string) $taskId, $taskIdToPredecessorIds, $visited, $recStack)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<string, list<string>> $taskIdToPredecessorIds
     * @param array<string, bool> $visited
     * @param array<string, bool> $recStack
     */
    private function visit(string $taskId, array $taskIdToPredecessorIds, array &$visited, array &$recStack): bool
    {
        $visited[$taskId] = true;
        $recStack[$taskId] = true;

        $predecessors = $taskIdToPredecessorIds[$taskId] ?? [];
        foreach ($predecessors as $predId) {
            $predId = (string) $predId;
            if (!isset($visited[$predId])) {
                if ($this->visit($predId, $taskIdToPredecessorIds, $visited, $recStack)) {
                    return true;
                }
            } elseif (isset($recStack[$predId]) && $recStack[$predId]) {
                return true;
            }
        }

        $recStack[$taskId] = false;

        return false;
    }
}
