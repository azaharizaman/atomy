<?php

declare(strict_types=1);

namespace Nexus\Task\Contracts;

/**
 * Predecessor/dependency graph and acyclicity check (BUS-PRO-0090).
 */
interface DependencyGraphInterface
{
    /**
     * Whether the dependency graph contains a cycle.
     *
     * @param array<string, list<string>> $taskIdToPredecessorIds map of task id => list of predecessor task ids
     */
    public function hasCycle(array $taskIdToPredecessorIds): bool;
}
