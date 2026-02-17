<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Contracts;

use Nexus\ProcurementOperations\DTOs\PaymentRunRequest;
use Nexus\ProcurementOperations\DTOs\PaymentRunResult;

/**
 * Contract for batch payment run coordination.
 */
interface PaymentRunCoordinatorInterface
{
    /**
     * Create a new draft payment run based on filters.
     */
    public function createRun(PaymentRunRequest $request): PaymentRunResult;

    /**
     * Approve a draft payment run for execution.
     */
    public function approveRun(
        string $tenantId,
        string $paymentRunId,
        string $approvedBy
    ): PaymentRunResult;

    /**
     * Execute an approved payment run (send to bank/process).
     */
    public function executeRun(
        string $tenantId,
        string $paymentRunId,
        string $executedBy
    ): PaymentRunResult;
}
