<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\Contracts;

/**
 * Interface for task services.
 *
 * Defines the contract for creating and managing tasks
 * within the FinanceOperations orchestrator.
 */
interface TaskServiceInterface
{
    /**
     * Create an adjustment task.
     *
     * @param array<string, mixed> $discrepancy The discrepancy data to create a task for
     * @return void
     */
    public function createAdjustmentTask(array $discrepancy): void;
}
