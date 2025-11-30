<?php

declare(strict_types=1);

namespace Nexus\CashManagement\Contracts;

/**
 * Reversal Handler Interface
 *
 * Manages automatic payment application reversal with GL workflow.
 */
interface ReversalHandlerInterface
{
    /**
     * Reverse a payment application and initiate GL reversal workflow
     */
    public function reversePaymentApplication(
        string $paymentApplicationId,
        string $reconciliationId,
        string $reason
    ): void;

    /**
     * Check if a payment application can be reversed
     */
    public function canReverse(string $paymentApplicationId): bool;

    /**
     * Get reversal workflow instance ID
     */
    public function getReversalWorkflowId(string $paymentApplicationId): ?string;
}
