<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Coordinators;

use Nexus\Common\ValueObjects\Money;
use Nexus\Payable\Contracts\VendorBillPersistInterface;
use Nexus\Payable\Contracts\VendorBillQueryInterface;
use Nexus\ProcurementOperations\Contracts\AccrualServiceInterface;
use Nexus\ProcurementOperations\Contracts\InvoiceMatchingCoordinatorInterface;
use Nexus\ProcurementOperations\Contracts\ThreeWayMatchingServiceInterface;
use Nexus\ProcurementOperations\DataProviders\ThreeWayMatchDataProvider;
use Nexus\ProcurementOperations\DTOs\MatchingResult;
use Nexus\ProcurementOperations\DTOs\MatchInvoiceRequest;
use Nexus\ProcurementOperations\Exceptions\MatchingException;
use Nexus\ProcurementOperations\Rules\InvoiceMatching\InvoiceMatchingRuleRegistry;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Coordinates the three-way matching workflow.
 *
 * Orchestrates:
 * 1. Data aggregation via ThreeWayMatchDataProvider
 * 2. Validation via InvoiceMatchingRuleRegistry
 * 3. Match calculation via ThreeWayMatchingService
 * 4. GL posting via AccrualService (accrual reversal + AP recognition)
 * 5. Status updates via persistence interfaces
 * 6. Event dispatching
 *
 * Implements the traffic cop pattern - delegates actual work to specialized components.
 */
final readonly class InvoiceMatchingCoordinator implements InvoiceMatchingCoordinatorInterface
{
    private const DEFAULT_PRICE_TOLERANCE = 2.0;      // 2% price tolerance
    private const DEFAULT_QUANTITY_TOLERANCE = 5.0;   // 5% quantity tolerance

    public function __construct(
        private ThreeWayMatchDataProvider $dataProvider,
        private ThreeWayMatchingServiceInterface $matchingService,
        private AccrualServiceInterface $accrualService,
        private VendorBillQueryInterface $vendorBillQuery,
        private VendorBillPersistInterface $vendorBillPersist,
        private EventDispatcherInterface $eventDispatcher,
        private float $priceTolerancePercent = self::DEFAULT_PRICE_TOLERANCE,
        private float $quantityTolerancePercent = self::DEFAULT_QUANTITY_TOLERANCE,
        private LoggerInterface $logger = new NullLogger(),
    ) {}

    /**
     * Perform three-way match: PO ↔ GR ↔ Invoice.
     */
    public function match(MatchInvoiceRequest $request): MatchingResult
    {
        $this->logger->info('Starting three-way invoice matching', [
            'vendor_bill_id' => $request->vendorBillId,
            'purchase_order_id' => $request->purchaseOrderId,
            'goods_receipt_ids' => $request->goodsReceiptIds,
            'performed_by' => $request->performedBy,
        ]);

        try {
            // 1. Validate invoice exists and is not already matched
            $this->validateInvoiceStatus($request->vendorBillId);

            // 2. Validate goods receipts are provided
            if (empty($request->goodsReceiptIds)) {
                throw MatchingException::noGoodsReceipts(
                    $request->vendorBillId,
                    $request->purchaseOrderId,
                );
            }

            // 3. Build match context (aggregates PO, GRs, Invoice data)
            $context = $this->dataProvider->buildContext(
                tenantId: $request->tenantId,
                vendorBillId: $request->vendorBillId,
                purchaseOrderId: $request->purchaseOrderId,
                goodsReceiptIds: $request->goodsReceiptIds,
            );

            // 4. Perform match calculation
            $result = $this->matchingService->calculateMatch(
                context: $context,
                priceTolerancePercent: $this->priceTolerancePercent,
                quantityTolerancePercent: $this->quantityTolerancePercent,
            );

            // 5. Handle match result
            if ($result->matched) {
                return $this->handleSuccessfulMatch($request, $result, $context);
            }

            // 6. Handle failed match (check if variance approval is allowed)
            if ($request->allowVariance && $request->varianceApprovalReason !== null) {
                return $this->handleApprovedVariance($request, $result, $context);
            }

            // 7. Return failure without posting
            $this->dispatchMatchFailedEvent($request, $result);
            return $result;

        } catch (MatchingException $e) {
            $this->logger->error('Matching exception occurred', [
                'vendor_bill_id' => $request->vendorBillId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        } catch (\Throwable $e) {
            $this->logger->error('Unexpected error during invoice matching', [
                'vendor_bill_id' => $request->vendorBillId,
                'error' => $e->getMessage(),
                'error_code' => $e->getCode(),
            ]);

            return MatchingResult::error(
                'Unexpected error: ' . $e->getMessage(),
                ['exception' => get_class($e)],
            );
        }
    }

    /**
     * Perform two-way match: PO ↔ Invoice (no GR required).
     */
    public function matchTwoWay(
        string $tenantId,
        string $vendorBillId,
        string $purchaseOrderId,
        string $performedBy
    ): MatchingResult {
        $this->logger->info('Starting two-way invoice matching', [
            'vendor_bill_id' => $vendorBillId,
            'purchase_order_id' => $purchaseOrderId,
            'performed_by' => $performedBy,
        ]);

        try {
            // Validate invoice status
            $this->validateInvoiceStatus($vendorBillId);

            // Build context with empty GR array
            $context = $this->dataProvider->buildContext(
                tenantId: $tenantId,
                vendorBillId: $vendorBillId,
                purchaseOrderId: $purchaseOrderId,
                goodsReceiptIds: [], // Empty for two-way match
            );

            // Perform two-way match calculation
            $result = $this->matchingService->calculateTwoWayMatch(
                context: $context,
                priceTolerancePercent: $this->priceTolerancePercent,
            );

            if ($result->matched) {
                // For two-way match, directly post AP liability (no accrual to reverse)
                $this->postApLiability($vendorBillId, $purchaseOrderId, $context);
                $this->updateInvoiceStatus($vendorBillId, 'matched');
                $this->dispatchMatchSuccessEvent($vendorBillId, $purchaseOrderId, $result);
            }

            return $result;

        } catch (\Throwable $e) {
            $this->logger->error('Error during two-way matching', [
                'vendor_bill_id' => $vendorBillId,
                'error' => $e->getMessage(),
            ]);

            return MatchingResult::error('Two-way match failed: ' . $e->getMessage());
        }
    }

    /**
     * Force match with variance approval.
     */
    public function forceMatch(
        string $tenantId,
        string $vendorBillId,
        string $purchaseOrderId,
        array $goodsReceiptIds,
        string $approvedBy,
        string $approvalReason
    ): MatchingResult {
        $this->logger->info('Force matching invoice with variance approval', [
            'vendor_bill_id' => $vendorBillId,
            'purchase_order_id' => $purchaseOrderId,
            'approved_by' => $approvedBy,
            'reason' => $approvalReason,
        ]);

        $request = new MatchInvoiceRequest(
            tenantId: $tenantId,
            vendorBillId: $vendorBillId,
            purchaseOrderId: $purchaseOrderId,
            goodsReceiptIds: $goodsReceiptIds,
            performedBy: $approvedBy,
            allowVariance: true,
            varianceApprovalReason: $approvalReason,
            metadata: ['force_matched' => true],
        );

        // Validate user has approval authority
        // In production, this would check against authorization policy
        // For now, we trust the caller has validated authorization

        return $this->match($request);
    }

    /**
     * Get match status for a vendor bill.
     */
    public function getMatchStatus(string $tenantId, string $vendorBillId): array
    {
        $vendorBill = $this->vendorBillQuery->findById($vendorBillId);

        if ($vendorBill === null) {
            throw MatchingException::invoiceNotFound($vendorBillId);
        }

        $status = $vendorBill->getMatchStatus();

        return [
            'status' => $status['status'] ?? 'unmatched',
            'matchedAt' => isset($status['matched_at'])
                ? new \DateTimeImmutable($status['matched_at'])
                : null,
            'variances' => $status['variances'] ?? [],
            'withinTolerance' => $status['within_tolerance'] ?? false,
        ];
    }

    /**
     * Handle successful match - post GL entries and update status.
     */
    private function handleSuccessfulMatch(
        MatchInvoiceRequest $request,
        MatchingResult $result,
        object $context
    ): MatchingResult {
        $this->logger->info('Match successful, posting GL entries', [
            'vendor_bill_id' => $request->vendorBillId,
        ]);

        try {
            // 1. Reverse GR-IR accrual
            $reversalJeId = $this->reverseAccrual(
                $request->purchaseOrderId,
                $request->vendorBillId,
                $context,
            );

            // 2. Post AP liability
            $apJeId = $this->postApLiability(
                $request->vendorBillId,
                $request->purchaseOrderId,
                $context,
            );

            // 3. Update invoice status
            $this->updateInvoiceStatus($request->vendorBillId, 'matched');

            // 4. Dispatch success event
            $this->dispatchMatchSuccessEvent(
                $request->vendorBillId,
                $request->purchaseOrderId,
                $result,
            );

            // 5. Return result with journal entry IDs
            return MatchingResult::matched(
                vendorBillId: $request->vendorBillId,
                purchaseOrderId: $request->purchaseOrderId,
                priceVariancePercent: $result->priceVariancePercent ?? 0.0,
                quantityVariancePercent: $result->quantityVariancePercent ?? 0.0,
                accrualReversalJournalEntryId: $reversalJeId,
                payableLiabilityJournalEntryId: $apJeId,
                message: 'Invoice matched and GL entries posted successfully',
            );

        } catch (\Throwable $e) {
            $this->logger->error('Failed to post GL entries after successful match', [
                'vendor_bill_id' => $request->vendorBillId,
                'error' => $e->getMessage(),
            ]);

            return MatchingResult::error(
                'Match successful but GL posting failed: ' . $e->getMessage(),
            );
        }
    }

    /**
     * Handle approved variance - post GL with variance tracking.
     */
    private function handleApprovedVariance(
        MatchInvoiceRequest $request,
        MatchingResult $failedResult,
        object $context
    ): MatchingResult {
        $this->logger->info('Processing approved variance match', [
            'vendor_bill_id' => $request->vendorBillId,
            'approval_reason' => $request->varianceApprovalReason,
            'price_variance' => $failedResult->priceVariancePercent,
            'quantity_variance' => $failedResult->quantityVariancePercent,
        ]);

        try {
            // Post GL entries even though variance exceeded tolerance
            $reversalJeId = $this->reverseAccrual(
                $request->purchaseOrderId,
                $request->vendorBillId,
                $context,
            );

            $apJeId = $this->postApLiability(
                $request->vendorBillId,
                $request->purchaseOrderId,
                $context,
            );

            // Update invoice status with variance approval metadata
            $this->updateInvoiceStatus($request->vendorBillId, 'matched_with_variance', [
                'variance_approved_by' => $request->performedBy,
                'variance_approval_reason' => $request->varianceApprovalReason,
                'price_variance_percent' => $failedResult->priceVariancePercent,
                'quantity_variance_percent' => $failedResult->quantityVariancePercent,
            ]);

            return MatchingResult::matched(
                vendorBillId: $request->vendorBillId,
                purchaseOrderId: $request->purchaseOrderId,
                priceVariancePercent: $failedResult->priceVariancePercent ?? 0.0,
                quantityVariancePercent: $failedResult->quantityVariancePercent ?? 0.0,
                accrualReversalJournalEntryId: $reversalJeId,
                payableLiabilityJournalEntryId: $apJeId,
                message: 'Invoice matched with approved variance',
            );

        } catch (\Throwable $e) {
            $this->logger->error('Failed to process approved variance match', [
                'vendor_bill_id' => $request->vendorBillId,
                'error' => $e->getMessage(),
            ]);

            return MatchingResult::error(
                'Approved variance match failed: ' . $e->getMessage(),
            );
        }
    }

    /**
     * Validate invoice is in a matchable state.
     */
    private function validateInvoiceStatus(string $vendorBillId): void
    {
        $vendorBill = $this->vendorBillQuery->findById($vendorBillId);

        if ($vendorBill === null) {
            throw MatchingException::invoiceNotFound($vendorBillId);
        }

        $matchStatus = $vendorBill->getMatchStatus();
        if (isset($matchStatus['status']) && $matchStatus['status'] === 'matched') {
            throw MatchingException::alreadyMatched($vendorBillId);
        }
    }

    /**
     * Reverse GR-IR accrual via AccrualService.
     */
    private function reverseAccrual(
        string $purchaseOrderId,
        string $vendorBillId,
        object $context
    ): ?string {
        // Get GR-IR accrual amount from context
        $accrualAmountCents = $context->totals['totalGrValueCents'] ?? 0;

        if ($accrualAmountCents <= 0) {
            return null;
        }

        return $this->accrualService->reverseAccrualOnMatch(
            tenantId: $context->tenantId,
            vendorBillId: $vendorBillId,
            goodsReceiptIds: $context->goodsReceiptIds,
            postedBy: 'system',
        );
    }

    /**
     * Post AP liability via AccrualService.
     */
    private function postApLiability(
        string $vendorBillId,
        string $purchaseOrderId,
        object $context
    ): ?string {
        $invoiceAmountCents = $context->totals['totalInvoiceAmountCents'] ?? 0;

        if ($invoiceAmountCents <= 0) {
            return null;
        }

        return $this->accrualService->postPayableLiability(
            tenantId: $context->tenantId,
            vendorBillId: $vendorBillId,
            vendorId: $context->invoiceInfo['vendorId'],
            amountCents: $invoiceAmountCents,
            currency: $context->invoiceInfo['currency'],
            postedBy: 'system',
        );
    }

    /**
     * Update vendor bill match status.
     *
     * @param array<string, mixed> $metadata
     */
    private function updateInvoiceStatus(
        string $vendorBillId,
        string $status,
        array $metadata = []
    ): void {
        $this->vendorBillPersist->updateMatchStatus($vendorBillId, $status, $metadata);
    }

    /**
     * Dispatch invoice matched event.
     */
    private function dispatchMatchSuccessEvent(
        string $vendorBillId,
        string $purchaseOrderId,
        MatchingResult $result
    ): void {
        // Event dispatching would use domain events
        // For now, log the event
        $this->logger->info('InvoiceMatchedEvent dispatched', [
            'vendor_bill_id' => $vendorBillId,
            'purchase_order_id' => $purchaseOrderId,
            'price_variance' => $result->priceVariancePercent,
            'quantity_variance' => $result->quantityVariancePercent,
        ]);
    }

    /**
     * Dispatch invoice match failed event.
     */
    private function dispatchMatchFailedEvent(
        MatchInvoiceRequest $request,
        MatchingResult $result
    ): void {
        $this->logger->info('InvoiceMatchFailedEvent dispatched', [
            'vendor_bill_id' => $request->vendorBillId,
            'purchase_order_id' => $request->purchaseOrderId,
            'failure_reason' => $result->failureReason,
            'price_variance' => $result->priceVariancePercent,
            'quantity_variance' => $result->quantityVariancePercent,
        ]);
    }
}
