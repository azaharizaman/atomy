<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Coordinators;

use Nexus\ProcurementOperations\Contracts\ReturnToVendorCoordinatorInterface;
use Nexus\ProcurementOperations\DTOs\ReturnToVendorRequest;
use Nexus\ProcurementOperations\DTOs\ReturnToVendorResult;
use Nexus\Procurement\Contracts\GoodsReceiptQueryInterface;
use Nexus\Procurement\Contracts\GoodsReceiptPersistInterface;
use Nexus\Inventory\Contracts\InventoryManagerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Coordinates the Return to Vendor (RTV) process.
 */
final readonly class ReturnToVendorCoordinator implements ReturnToVendorCoordinatorInterface
{
    public function __construct(
        private GoodsReceiptQueryInterface $grQuery,
        private GoodsReceiptPersistInterface $grPersist,
        private InventoryManagerInterface $inventoryManager,
        private LoggerInterface $logger = new NullLogger()
    ) {}

    /**
     * @inheritDoc
     */
    public function initiateReturn(ReturnToVendorRequest $request): ReturnToVendorResult
    {
        $this->logger->info('Initiating return to vendor', [
            'tenant_id' => $request->tenantId,
            'goods_receipt_id' => $request->goodsReceiptId,
        ]);

        try {
            $gr = $this->grQuery->findById($request->goodsReceiptId);
            if (!$gr) {
                return ReturnToVendorResult::failure('Goods receipt not found.');
            }

            // 1. Record the return in Procurement package
            // Note: Implementation assumed to exist in atomic package persist layer
            $returnId = $this->grPersist->createReturn($request->goodsReceiptId, $request->lineItems, [
                'initiated_by' => $request->initiatedBy,
                'notes' => $request->notes,
                'metadata' => $request->metadata,
            ]);

            // 2. Adjust inventory to reflect the return (reduce stock)
            foreach ($request->lineItems as $item) {
                $this->inventoryManager->adjustStock(
                    tenantId: $request->tenantId,
                    productId: $item['productId'] ?? '', // Fallback or fetch from GR line
                    quantity: -$item['quantity'],
                    reason: 'Return to Vendor (RTV): ' . ($item['reason'] ?? 'Defective'),
                    referenceType: 'rtv',
                    referenceId: $returnId
                );
            }

            return ReturnToVendorResult::success(
                returnId: $returnId,
                returnNumber: 'RTV-' . date('Ymd') . '-' . substr($returnId, -4),
                status: 'initiated',
                message: 'Return to vendor initiated and inventory adjusted.'
            );

        } catch (\Throwable $e) {
            $this->logger->error('Failed to initiate return to vendor', [
                'error' => $e->getMessage(),
            ]);
            return ReturnToVendorResult::failure($e->getMessage());
        }
    }

    /**
     * @inheritDoc
     */
    public function confirmShipment(string $tenantId, string $returnId, string $shippedBy, ?string $trackingNumber = null): ReturnToVendorResult
    {
        try {
            // Update status to shipped
            $this->grPersist->updateReturnStatus($returnId, 'shipped', [
                'shipped_by' => $shippedBy,
                'tracking_number' => $trackingNumber,
                'shipped_at' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
            ]);

            return ReturnToVendorResult::success($returnId, 'RTV-' . $returnId, 'shipped', 'Return shipment confirmed.');
        } catch (\Throwable $e) {
            return ReturnToVendorResult::failure($e->getMessage());
        }
    }

    /**
     * @inheritDoc
     */
    public function recordCreditMemo(string $tenantId, string $returnId, string $creditMemoId): ReturnToVendorResult
    {
        try {
            // Close the return cycle
            $this->grPersist->updateReturnStatus($returnId, 'completed', [
                'credit_memo_id' => $creditMemoId,
                'completed_at' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
            ]);

            return ReturnToVendorResult::success($returnId, 'RTV-' . $returnId, 'completed', 'Credit memo recorded and return closed.');
        } catch (\Throwable $e) {
            return ReturnToVendorResult::failure($e->getMessage());
        }
    }
}
