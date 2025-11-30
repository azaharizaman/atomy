<?php

declare(strict_types=1);

namespace Nexus\Workflow\Contracts;

/**
 * Contract for multi-approver strategies.
 *
 * Determines when a task with multiple approvers can proceed.
 */
interface ApprovalStrategyInterface
{
    /**
     * Get the strategy name.
     */
    public function getName(): string;

    /**
     * Check if the task can proceed based on current approvals.
     *
     * @param array<string, mixed> $approvals Current approvals with user_id => action
     * @param array<string, mixed> $config Strategy configuration (weights, threshold, etc.)
     */
    public function canProceed(array $approvals, array $config = []): bool;

    /**
     * Check if the task should be rejected based on current rejections.
     *
     * @param array<string, mixed> $approvals Current approvals with user_id => action
     * @param array<string, mixed> $config Strategy configuration
     */
    public function shouldReject(array $approvals, array $config = []): bool;
}
