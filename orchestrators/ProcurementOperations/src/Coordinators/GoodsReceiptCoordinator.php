<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Coordinators;

use Nexus\Procurement\Contracts\GoodsReceiptPersistInterface;
use Nexus\Procurement\Contracts\PurchaseOrderQueryInterface;
use Nexus\Procurement\Contracts\PurchaseOrderPersistInterface;
use Nexus\ProcurementOperations\Contracts\GoodsReceiptCoordinatorInterface;
use Nexus\ProcurementOperations\DataProviders\GoodsReceiptContextProvider;
use Nexus\ProcurementOperations\DTOs\GoodsReceiptContext;
use Nexus\ProcurementOperations\DTOs\GoodsReceiptResult;
use Nexus\ProcurementOperations\DTOs\RecordGoodsReceiptRequest;
use Nexus\ProcurementOperations\Exceptions\GoodsReceiptException;
use Nexus\ProcurementOperations\Exceptions\PurchaseOrderException;
use Nexus\ProcurementOperations\Rules\GoodsReceipt\GoodsReceiptRuleRegistry;
use Nexus\ProcurementOperations\Services\AccrualCalculationService;
use Nexus\Sequencing\Contracts\SequencingManagerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Coordinates the goods receipt workflow.
 *
 * This coordinator follows the Advanced Orchestrator Pattern v1.1:
 * - Acts as a "traffic cop", not a worker
 * - Delegates data fetching to GoodsReceiptContextProvider
 * - Delegates validation to GoodsReceiptRuleRegistry
 * - Delegates GL posting to AccrualCalculationService
 * - Dispatches events for side effects (inventory, notifications)
 *
 * Workflow:
 * 1. Validate PO is open for receipts
 * 2. Validate receipt data (quantities, quality, expiry)
 * 3. Record goods receipt in Procurement package
 * 4. Post GR-IR accrual journal entry
 * 5. Dispatch events for inventory update and notifications
 */
final readonly class GoodsReceiptCoordinator implements GoodsReceiptCoordinatorInterface
{
    public function __construct(
        private GoodsReceiptContextProvider $contextProvider,
        private GoodsReceiptRuleRegistry $ruleRegistry,
        private AccrualCalculationService $accrualService,
        private PurchaseOrderQueryInterface $purchaseOrderQuery,
        private GoodsReceiptPersistInterface $goodsReceiptPersist,
        private ?PurchaseOrderPersistInterface $purchaseOrderPersist = null,
        private ?SequencingManagerInterface $sequencing = null,
        private ?EventDispatcherInterface $eventDispatcher = null,
        private ?LoggerInterface $logger = null,
    ) {}

    /**
     * Get the logger instance, or a NullLogger if none was injected.
     */
    private function getLogger(): LoggerInterface
    {
        return $this->logger ?? new NullLogger();
    }

    /**
     * Record goods receipt against a purchase order.
     *
     * @inheritDoc
     */
    public function record(RecordGoodsReceiptRequest $request): GoodsReceiptResult
    {
        $this->getLogger()->info('Recording goods receipt', [
            'tenant_id' => $request->tenantId,
            'purchase_order_id' => $request->purchaseOrderId,
            'warehouse_id' => $request->warehouseId,
            'line_count' => count($request->lineItems),
        ]);

        try {
            // 1. Validate PO exists and is open for receipts
            $purchaseOrder = $this->purchaseOrderQuery->findById($request->purchaseOrderId);
            if ($purchaseOrder === null) {
                throw PurchaseOrderException::notFound($request->purchaseOrderId);
            }

            $this->validatePoIsOpenForReceipts($purchaseOrder);

            // 2. Get pre-receipt context for validation
            $context = $this->contextProvider->getPreReceiptContext(
                $request->tenantId,
                $request->purchaseOrderId,
                $request->warehouseId,
                $request->lineItems
            );

            // 3. Validate receipt data using rule registry
            $this->ruleRegistry->validateOrFail($context);

            // 4. Generate goods receipt number
            $grNumber = $this->generateGoodsReceiptNumber($request->tenantId);

            // 5. Record goods receipt
            $goodsReceiptId = $this->goodsReceiptPersist->create([
                'tenant_id' => $request->tenantId,
                'receipt_number' => $grNumber,
                'purchase_order_id' => $request->purchaseOrderId,
                'warehouse_id' => $request->warehouseId,
                'received_by' => $request->receivedBy,
                'receipt_date' => $request->receiptDate ?? new \DateTimeImmutable(),
                'delivery_note_number' => $request->deliveryNoteNumber,
                'carrier_name' => $request->carrierName,
                'notes' => $request->notes,
                'line_items' => $this->transformLineItems($request->lineItems),
                'metadata' => $request->metadata,
            ]);

            // 6. Calculate total received value and prepare for accrual
            $lineItems = $this->prepareLineItemsForAccrual($request->lineItems, $purchaseOrder);
            $totalValueCents = $this->calculateTotalValue($lineItems);

            // 7. Post GR-IR accrual journal entry
            $accrualJournalEntryId = null;
            try {
                $accrualJournalEntryId = $this->accrualService->postGoodsReceiptAccrual(
                    $request->tenantId,
                    $goodsReceiptId,
                    $request->purchaseOrderId,
                    $lineItems,
                    $request->receivedBy
                );
            } catch (\Throwable $e) {
                $this->getLogger()->warning('Failed to post GR-IR accrual, continuing', [
                    'goods_receipt_id' => $goodsReceiptId,
                    'error' => $e->getMessage(),
                ]);
            }

            // 8. Check if PO is fully received
            $outstandingQuantities = $this->contextProvider->getOutstandingQuantities(
                $request->tenantId,
                $request->purchaseOrderId
            );
            $poFullyReceived = $this->isPoFullyReceived($outstandingQuantities);
            $isPartialReceipt = !$poFullyReceived;

            // 9. Update PO status if fully received
            if ($poFullyReceived && $this->purchaseOrderPersist !== null) {
                $this->purchaseOrderPersist->updateStatus($request->purchaseOrderId, 'received');
            }

            // 10. Dispatch events for side effects
            $this->dispatchEvents($goodsReceiptId, $grNumber, $request, $poFullyReceived);

            $this->getLogger()->info('Goods receipt recorded successfully', [
                'goods_receipt_id' => $goodsReceiptId,
                'goods_receipt_number' => $grNumber,
                'po_fully_received' => $poFullyReceived,
                'accrual_journal_entry_id' => $accrualJournalEntryId,
            ]);

            return GoodsReceiptResult::success(
                goodsReceiptId: $goodsReceiptId,
                goodsReceiptNumber: $grNumber,
                status: 'confirmed',
                isPartialReceipt: $isPartialReceipt,
                poFullyReceived: $poFullyReceived,
                receivedValueCents: $totalValueCents,
                accrualJournalEntryId: $accrualJournalEntryId,
                lineStatus: $outstandingQuantities,
                message: $poFullyReceived
                    ? 'Goods receipt recorded. PO is now fully received.'
                    : 'Goods receipt recorded. PO has outstanding quantities.',
            );
        } catch (GoodsReceiptException|PurchaseOrderException $e) {
            $this->getLogger()->warning('Goods receipt failed', [
                'purchase_order_id' => $request->purchaseOrderId,
                'error' => $e->getMessage(),
            ]);

            return GoodsReceiptResult::failure($e->getMessage());
        } catch (\Throwable $e) {
            $this->getLogger()->error('Unexpected error during goods receipt', [
                'purchase_order_id' => $request->purchaseOrderId,
                'error' => $e->getMessage(),
                'error_code' => $e->getCode(),
            ]);

            return GoodsReceiptResult::failure('Unexpected error: ' . $e->getMessage());
        }
    }

    /**
     * Reverse a goods receipt.
     *
     * @inheritDoc
     */
    public function reverse(
        string $tenantId,
        string $goodsReceiptId,
        string $reversedBy,
        string $reason
    ): GoodsReceiptResult {
        $this->getLogger()->info('Reversing goods receipt', [
            'tenant_id' => $tenantId,
            'goods_receipt_id' => $goodsReceiptId,
            'reversed_by' => $reversedBy,
        ]);

        try {
            // 1. Get goods receipt context
            $context = $this->contextProvider->getContext($tenantId, $goodsReceiptId);

            // 2. Validate GR can be reversed
            $this->validateCanReverse($context);

            // 3. Create reversal record
            $this->goodsReceiptPersist->reverse($goodsReceiptId, $reversedBy, $reason);

            // 4. Reverse accrual journal entry if exists
            if ($context->accrualJournalEntryId !== null) {
                try {
                    $this->accrualService->reverseAccrualOnMatch(
                        $tenantId,
                        $goodsReceiptId,
                        [$goodsReceiptId],
                        $reversedBy
                    );
                } catch (\Throwable $e) {
                    $this->getLogger()->warning('Failed to reverse accrual, continuing', [
                        'goods_receipt_id' => $goodsReceiptId,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // 5. Dispatch reversal events
            $this->dispatchReversalEvents($goodsReceiptId, $context, $reversedBy, $reason);

            $this->getLogger()->info('Goods receipt reversed successfully', [
                'goods_receipt_id' => $goodsReceiptId,
            ]);

            return GoodsReceiptResult::success(
                goodsReceiptId: $goodsReceiptId,
                goodsReceiptNumber: $context->goodsReceiptNumber,
                status: 'reversed',
                isPartialReceipt: false,
                poFullyReceived: false,
                receivedValueCents: 0,
                message: 'Goods receipt reversed successfully.',
            );
        } catch (GoodsReceiptException $e) {
            $this->getLogger()->warning('Goods receipt reversal failed', [
                'goods_receipt_id' => $goodsReceiptId,
                'error' => $e->getMessage(),
            ]);

            return GoodsReceiptResult::failure($e->getMessage());
        }
    }

    /**
     * Get outstanding quantities for a purchase order.
     *
     * @inheritDoc
     */
    public function getOutstandingQuantities(string $tenantId, string $purchaseOrderId): array
    {
        return $this->contextProvider->getOutstandingQuantities($tenantId, $purchaseOrderId);
    }

    /**
     * Validate PO is open for receipts.
     */
    private function validatePoIsOpenForReceipts(object $purchaseOrder): void
    {
        $status = $purchaseOrder->getStatus()->value ?? $purchaseOrder->getStatus();
        $validStatuses = ['confirmed', 'sent', 'partial_received', 'open'];

        if (!in_array($status, $validStatuses, true)) {
            throw GoodsReceiptException::purchaseOrderNotOpen($purchaseOrder->getId(), $status);
        }
    }

    /**
     * Validate GR can be reversed.
     */
    private function validateCanReverse(GoodsReceiptContext $context): void
    {
        if ($context->status === 'reversed') {
            throw GoodsReceiptException::alreadyReversed($context->goodsReceiptId);
        }

        // Check if GR has matched invoices (would need additional data provider)
        // For now, we allow reversal if not already reversed
    }

    /**
     * Generate goods receipt number.
     */
    private function generateGoodsReceiptNumber(string $tenantId): string
    {
        if ($this->sequencing !== null) {
            return $this->sequencing->getNext('goods_receipt');
        }

        // Fallback: Generate a basic number
        return 'GR-' . date('Ymd') . '-' . substr(uniqid(), -6);
    }

    /**
     * Transform line items for persistence.
     */
    private function transformLineItems(array $lineItems): array
    {
        return array_map(fn(array $line) => [
            'po_line_id' => $line['poLineId'],
            'product_id' => $line['productId'],
            'quantity_received' => $line['quantityReceived'],
            'uom' => $line['uom'],
            'lot_number' => $line['lotNumber'] ?? null,
            'serial_numbers' => $line['serialNumbers'] ?? null,
            'expiry_date' => $line['expiryDate'] ?? null,
            'bin_location' => $line['binLocation'] ?? null,
            'quality_status' => $line['qualityStatus'] ?? 'pending',
            'notes' => $line['notes'] ?? null,
        ], $lineItems);
    }

    /**
     * Prepare line items for accrual posting.
     */
    private function prepareLineItemsForAccrual(array $lineItems, object $purchaseOrder): array
    {
        // Index PO lines by ID
        $poLines = [];
        foreach ($purchaseOrder->getLineItems() as $poLine) {
            $poLines[$poLine->getId()] = $poLine;
        }

        $accrualLines = [];
        foreach ($lineItems as $line) {
            $poLine = $poLines[$line['poLineId']] ?? null;
            $unitPriceCents = $poLine ? $poLine->getUnitPriceCents() : 0;
            $totalCents = (int) ($line['quantityReceived'] * $unitPriceCents);

            $accrualLines[] = [
                'productId' => $line['productId'],
                'quantity' => $line['quantityReceived'],
                'unitPriceCents' => $unitPriceCents,
                'totalCents' => $totalCents,
            ];
        }

        return $accrualLines;
    }

    /**
     * Calculate total value from line items.
     */
    private function calculateTotalValue(array $lineItems): int
    {
        return array_reduce(
            $lineItems,
            fn(int $carry, array $line) => $carry + ($line['totalCents'] ?? 0),
            0
        );
    }

    /**
     * Check if PO is fully received.
     */
    private function isPoFullyReceived(array $outstandingQuantities): bool
    {
        foreach ($outstandingQuantities as $line) {
            if ($line['outstandingQuantity'] > 0) {
                return false;
            }
        }
        return true;
    }

    /**
     * Dispatch events for goods receipt.
     */
    private function dispatchEvents(
        string $goodsReceiptId,
        string $grNumber,
        RecordGoodsReceiptRequest $request,
        bool $poFullyReceived
    ): void {
        if ($this->eventDispatcher === null) {
            return;
        }

        // Dispatch GoodsReceiptCreatedEvent
        $this->eventDispatcher->dispatch(new \Nexus\Procurement\Events\GoodsReceiptCreatedEvent(
            goodsReceiptId: $goodsReceiptId,
            goodsReceiptNumber: $grNumber,
            purchaseOrderId: $request->purchaseOrderId,
            warehouseId: $request->warehouseId,
            receivedBy: $request->receivedBy,
            lineItems: $request->lineItems,
            occurredAt: new \DateTimeImmutable(),
        ));

        // Dispatch GoodsReceiptCompletedEvent if PO fully received
        if ($poFullyReceived) {
            $this->eventDispatcher->dispatch(new \Nexus\Procurement\Events\GoodsReceiptCompletedEvent(
                purchaseOrderId: $request->purchaseOrderId,
                goodsReceiptId: $goodsReceiptId,
                occurredAt: new \DateTimeImmutable(),
            ));
        }
    }

    /**
     * Dispatch events for goods receipt reversal.
     */
    private function dispatchReversalEvents(
        string $goodsReceiptId,
        GoodsReceiptContext $context,
        string $reversedBy,
        string $reason
    ): void {
        if ($this->eventDispatcher === null) {
            return;
        }

        $this->eventDispatcher->dispatch(new \Nexus\Procurement\Events\GoodsReceiptReversedEvent(
            goodsReceiptId: $goodsReceiptId,
            purchaseOrderId: $context->purchaseOrderId,
            reversedBy: $reversedBy,
            reason: $reason,
            occurredAt: new \DateTimeImmutable(),
        ));
    }
}
