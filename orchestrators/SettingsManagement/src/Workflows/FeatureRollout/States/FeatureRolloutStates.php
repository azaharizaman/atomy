<?php

declare(strict_types=1);

namespace Nexus\SettingsManagement\Workflows\FeatureRollout;

use Nexus\SettingsManagement\DTOs\FeatureFlags\FeatureRolloutRequest;

/**
 * Workflow state interface for feature rollout.
 */
interface FeatureRolloutWorkflowState
{
    public function getState(): string;
    public function getPercentage(): int;
    public function isComplete(): bool;
}

/**
 * Initial state for feature rollout.
 */
final class RolloutInitiated implements FeatureRolloutWorkflowState
{
    public function __construct(
        private readonly FeatureRolloutRequest $request,
    ) {}

    public function getState(): string
    {
        return 'initiated';
    }

    public function getPercentage(): int
    {
        return $this->request->percentage ?? 0;
    }

    public function isComplete(): bool
    {
        return false;
    }
}

/**
 * Progressing state for feature rollout.
 */
final class RolloutProgressing implements FeatureRolloutWorkflowState
{
    public function __construct(
        private readonly FeatureRolloutRequest $request,
        private readonly int $currentPercentage,
    ) {}

    public function getState(): string
    {
        return 'progressing';
    }

    public function getPercentage(): int
    {
        return $this->currentPercentage;
    }

    public function isComplete(): bool
    {
        return $this->currentPercentage >= ($this->request->percentage ?? 100);
    }
}

/**
 * Completed state for feature rollout.
 */
final class RolloutCompleted implements FeatureRolloutWorkflowState
{
    public function __construct(
        private readonly FeatureRolloutRequest $request,
    ) {}

    public function getState(): string
    {
        return 'completed';
    }

    public function getPercentage(): int
    {
        return $this->request->percentage ?? 100;
    }

    public function isComplete(): bool
    {
        return true;
    }
}
