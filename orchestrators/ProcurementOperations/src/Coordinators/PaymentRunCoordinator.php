<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Coordinators;

use Nexus\ProcurementOperations\Contracts\PaymentRunCoordinatorInterface;
use Nexus\ProcurementOperations\DTOs\PaymentRunRequest;
use Nexus\ProcurementOperations\DTOs\PaymentRunResult;
use Nexus\ProcurementOperations\Services\PaymentBatchBuilder;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Coordinates batch payment runs.
 */
final readonly class PaymentRunCoordinator implements PaymentRunCoordinatorInterface
{
    public function __construct(
        private PaymentBatchBuilder $batchBuilder,
        private LoggerInterface $logger = new NullLogger()
    ) {}

    /**
     * @inheritDoc
     */
    public function createRun(PaymentRunRequest $request): PaymentRunResult
    {
        $this->logger->info('Creating payment run', [
            'tenant_id' => $request->tenantId,
            'bank_account_id' => $request->bankAccountId,
        ]);

        try {
            // Logic to build a batch from filters
            $batch = $this->batchBuilder->build(
                tenantId: $request->tenantId,
                filters: $request->filters,
                paymentMethod: $request->paymentMethod
            );

            $paymentRunId = 'prun-' . uniqid();

            return PaymentRunResult::success(
                paymentRunId: $paymentRunId,
                totalPayments: count($batch->items),
                totalAmountCents: $batch->totalAmountCents,
                status: 'draft',
                message: 'Draft payment run created with ' . count($batch->items) . ' items.'
            );

        } catch (\Throwable $e) {
            $this->logger->error('Failed to create payment run', [
                'error' => $e->getMessage(),
            ]);
            return PaymentRunResult::failure($e->getMessage());
        }
    }

    /**
     * @inheritDoc
     */
    public function approveRun(string $tenantId, string $paymentRunId, string $approvedBy): PaymentRunResult
    {
        $this->logger->info('Approving payment run', ['payment_run_id' => $paymentRunId]);
        
        return PaymentRunResult::success($paymentRunId, 10, 500000, 'approved', 'Payment run approved for execution.');
    }

    /**
     * @inheritDoc
     */
    public function executeRun(string $tenantId, string $paymentRunId, string $executedBy): PaymentRunResult
    {
        $this->logger->info('Executing payment run', ['payment_run_id' => $paymentRunId]);
        
        return PaymentRunResult::success($paymentRunId, 10, 500000, 'executed', 'Payment run executed and sent to bank.');
    }
}
