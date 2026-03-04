<?php

declare(strict_types=1);

namespace App\Service\Budget\Adapters;

use Nexus\Budget\Contracts\BudgetApprovalWorkflowInterface;
use Nexus\Budget\Enums\ApprovalStatus;
use Nexus\Common\ValueObjects\Money;

final class NullBudgetApprovalWorkflowAdapter implements BudgetApprovalWorkflowInterface
{
    public function requestBudgetOverrideApproval(
        string $budgetId,
        Money $requestedAmount,
        string $requestorId,
        string $reason,
        array $context = []
    ): string {
        return sprintf('wf_override_%s', bin2hex(random_bytes(8)));
    }

    public function requestReallocationApproval(
        string $fromBudgetId,
        string $toBudgetId,
        Money $amount,
        string $requestorId,
        string $reason
    ): string {
        return sprintf('wf_realloc_%s', bin2hex(random_bytes(8)));
    }

    public function requestInvestigationResponse(string $budgetId, float $variancePercentage, string $managerId): string
    {
        return sprintf('wf_investigation_%s', bin2hex(random_bytes(8)));
    }

    public function requestRolloverApproval(string $budgetId, Money $carryoverAmount, string $nextPeriodId): string
    {
        return sprintf('wf_rollover_%s', bin2hex(random_bytes(8)));
    }

    public function requestAmendmentApproval(
        string $budgetId,
        Money $currentAmount,
        Money $newAmount,
        string $requestorId,
        string $reason
    ): string {
        return sprintf('wf_amendment_%s', bin2hex(random_bytes(8)));
    }

    public function checkApprovalStatus(string $workflowId): ApprovalStatus
    {
        return ApprovalStatus::Pending;
    }
}
