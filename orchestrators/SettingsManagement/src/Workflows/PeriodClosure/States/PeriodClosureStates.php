<?php

declare(strict_types=1);

namespace Nexus\SettingsManagement\Workflows\PeriodClosure;

use Nexus\SettingsManagement\DTOs\FiscalPeriod\PeriodCloseRequest;

/**
 * Workflow state interface for period closure.
 */
interface PeriodClosureWorkflowState
{
    public function getState(): string;
    public function isComplete(): bool;
}

/**
 * Initial state for period closure.
 */
final class PeriodClosureInitiated implements PeriodClosureWorkflowState
{
    public function __construct(
        private readonly PeriodCloseRequest $request,
    ) {}

    public function getState(): string
    {
        return 'initiated';
    }

    public function isComplete(): bool
    {
        return false;
    }
}

/**
 * Processing state for period closure.
 */
final class PeriodClosureProcessing implements PeriodClosureWorkflowState
{
    public function __construct(
        private readonly PeriodCloseRequest $request,
    ) {}

    public function getState(): string
    {
        return 'processing';
    }

    public function isComplete(): bool
    {
        return false;
    }
}

/**
 * Completed state for period closure.
 */
final class PeriodClosureCompleted implements PeriodClosureWorkflowState
{
    public function __construct(
        private readonly PeriodCloseRequest $request,
    ) {}

    public function getState(): string
    {
        return 'completed';
    }

    public function isComplete(): bool
    {
        return true;
    }
}
