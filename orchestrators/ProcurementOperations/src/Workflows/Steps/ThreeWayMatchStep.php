<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Workflows\Steps;

use Nexus\ProcurementOperations\Contracts\SagaStepInterface;
use Nexus\ProcurementOperations\Contracts\ThreeWayMatchingServiceInterface;
use Nexus\ProcurementOperations\DTOs\SagaStepContext;
use Nexus\ProcurementOperations\DTOs\SagaStepResult;
use Nexus\ProcurementOperations\Enums\MatchingStatus;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Saga step: Perform 3-way matching (PO, GR, Invoice).
 *
 * Forward action: Matches PO, goods receipt, and vendor invoice.
 * Compensation: Unmatches the documents.
 */
final readonly class ThreeWayMatchStep implements SagaStepInterface
{
    public function __construct(
        private ThreeWayMatchingServiceInterface $matchingService,
        private LoggerInterface $logger = new NullLogger(),
    ) {}

    public function getId(): string
    {
        return 'three_way_match';
    }

    public function getName(): string
    {
        return '3-Way Matching';
    }

    public function getDescription(): string
    {
        return 'Performs 3-way matching between PO, Goods Receipt, and Vendor Invoice';
    }

    public function execute(SagaStepContext $context): SagaStepResult
    {
        $this->logger->info('Performing 3-way matching', [
            'saga_instance_id' => $context->sagaInstanceId,
            'tenant_id' => $context->tenantId,
        ]);

        try {
            $poId = $context->getStepOutput('create_purchase_order', 'purchase_order_id')
                ?? $context->get('purchase_order_id');

            $grId = $context->getStepOutput('receive_goods', 'goods_receipt_id')
                ?? $context->get('goods_receipt_id');

            $invoiceId = $context->get('vendor_invoice_id');

            $matchResult = $this->matchingService->performMatch(
                tenantId: $context->tenantId,
                purchaseOrderId: $poId,
                goodsReceiptId: $grId,
                vendorInvoiceId: $invoiceId,
            );

            // Check if match is acceptable
            if (!$matchResult->status->isMatched() && !$matchResult->status->requiresIntervention()) {
                return SagaStepResult::failure(
                    errorMessage: sprintf(
                        '3-way match failed: %s. Variances: %s',
                        $matchResult->status->label(),
                        json_encode($matchResult->variances)
                    ),
                    canRetry: false,
                );
            }

            // If requires intervention, we might pause the saga
            if ($matchResult->status->requiresIntervention()) {
                $this->logger->warning('3-way match requires intervention', [
                    'match_status' => $matchResult->status->value,
                    'variances' => $matchResult->variances,
                ]);
            }

            return SagaStepResult::success([
                'match_id' => $matchResult->matchId,
                'match_status' => $matchResult->status->value,
                'matched_amount' => $matchResult->matchedAmount,
                'variances' => $matchResult->variances,
                'requires_approval' => $matchResult->status->requiresIntervention(),
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to perform 3-way matching', [
                'error' => $e->getMessage(),
            ]);

            return SagaStepResult::failure(
                errorMessage: 'Failed to perform 3-way matching: ' . $e->getMessage(),
                canRetry: true,
            );
        }
    }

    public function compensate(SagaStepContext $context): SagaStepResult
    {
        $this->logger->info('Compensating: Unmatching documents', [
            'saga_instance_id' => $context->sagaInstanceId,
        ]);

        try {
            $matchId = $context->getStepOutput('three_way_match', 'match_id');

            if ($matchId === null) {
                return SagaStepResult::compensated([
                    'message' => 'No match to reverse',
                ]);
            }

            $this->matchingService->unmatch(
                matchId: $matchId,
                reason: 'Saga compensation - process rolled back',
            );

            return SagaStepResult::compensated([
                'unmatched_id' => $matchId,
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to unmatch documents during compensation', [
                'error' => $e->getMessage(),
            ]);

            return SagaStepResult::compensationFailed(
                'Failed to unmatch documents: ' . $e->getMessage()
            );
        }
    }

    public function hasCompensation(): bool
    {
        return true;
    }

    public function getOrder(): int
    {
        return 7;
    }

    public function isRequired(): bool
    {
        return true;
    }

    public function getTimeout(): int
    {
        return 300; // 5 minutes
    }

    public function getRetryAttempts(): int
    {
        return 3;
    }
}
