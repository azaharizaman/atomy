<?php

declare(strict_types=1);

namespace Nexus\Workflow\Core;

use Nexus\Workflow\Contracts\ApprovalStrategyInterface;
use Nexus\Workflow\Exceptions\UnknownApprovalStrategyException;

/**
 * Internal approval logic engine.
 *
 * Evaluates multi-approver strategies.
 */
final class ApprovalEngine
{
    /**
     * @var array<string, ApprovalStrategyInterface>
     */
    private array $strategies = [];

    public function registerStrategy(ApprovalStrategyInterface $strategy): void
    {
        $this->strategies[$strategy->getName()] = $strategy;
    }

    /**
     * Check if task can proceed based on approval strategy.
     *
     * @param array<string, mixed> $approvals
     * @param array<string, mixed> $config
     */
    public function canProceed(
        string $strategyName,
        array $approvals,
        array $config = []
    ): bool {
        $strategy = $this->strategies[$strategyName]
            ?? throw UnknownApprovalStrategyException::forStrategy($strategyName);

        return $strategy->canProceed($approvals, $config);
    }

    /**
     * Check if task should be rejected.
     *
     * @param array<string, mixed> $approvals
     * @param array<string, mixed> $config
     */
    public function shouldReject(
        string $strategyName,
        array $approvals,
        array $config = []
    ): bool {
        $strategy = $this->strategies[$strategyName]
            ?? throw UnknownApprovalStrategyException::forStrategy($strategyName);

        return $strategy->shouldReject($approvals, $config);
    }
}
