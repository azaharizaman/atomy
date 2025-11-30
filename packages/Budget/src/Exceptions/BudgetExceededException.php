<?php

declare(strict_types=1);

namespace Nexus\Budget\Exceptions;

use Nexus\Budget\Enums\ApprovalLevel;
use Nexus\Finance\ValueObjects\Money;

/**
 * Budget Exceeded Exception
 * 
 * Thrown when a budget commitment or actual exceeds available funds.
 * Provides workflow integration hints for override approvals.
 */
final class BudgetExceededException extends BudgetException
{
    public function __construct(
        private readonly Money $requestedAmount,
        private readonly Money $availableAmount,
        private readonly Money $shortfall,
        private readonly bool $workflowAvailable,
        private readonly ?Money $workflowThreshold,
        private readonly ApprovalLevel $requiredApprovalLevel,
        string $message = '',
        int $code = 400
    ) {
        $message = $message ?: sprintf(
            'Budget exceeded: Requested %s, Available %s (Shortfall: %s)',
            $requestedAmount->format(),
            $availableAmount->format(),
            $shortfall->format()
        );
        parent::__construct($message, $code);
    }

    public function getRequestedAmount(): Money
    {
        return $this->requestedAmount;
    }

    public function getAvailableAmount(): Money
    {
        return $this->availableAmount;
    }

    public function getShortfall(): Money
    {
        return $this->shortfall;
    }

    public function isWorkflowAvailable(): bool
    {
        return $this->workflowAvailable;
    }

    public function getWorkflowThreshold(): ?Money
    {
        return $this->workflowThreshold;
    }

    public function getRequiredApprovalLevel(): ApprovalLevel
    {
        return $this->requiredApprovalLevel;
    }

    /**
     * Check if this exception requires workflow approval
     */
    public function requiresWorkflowApproval(): bool
    {
        if (!$this->workflowAvailable) {
            return false;
        }

        if ($this->workflowThreshold === null) {
            return true;
        }

        return $this->requestedAmount->isGreaterThanOrEqual($this->workflowThreshold);
    }

    /**
     * Get recommended action for user
     */
    public function getRecommendedAction(): string
    {
        if ($this->requiresWorkflowApproval()) {
            return sprintf(
                'Submit workflow approval request to %s for override (requires approval for amounts >= %s)',
                $this->requiredApprovalLevel->label(),
                $this->workflowThreshold?->format() ?? 'any amount'
            );
        }

        return sprintf(
            'Reduce requested amount to %s or below',
            $this->availableAmount->format()
        );
    }
}
