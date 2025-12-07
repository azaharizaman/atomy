<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Listeners;

use Nexus\Common\ValueObjects\Money;
use Nexus\Inventory\Contracts\LotManagerInterface;
use Nexus\Inventory\Contracts\SerialNumberManagerInterface;
use Nexus\Inventory\Contracts\StockManagerInterface;
use Nexus\Procurement\Events\GoodsReceiptCreatedEvent;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Updates inventory stock levels when goods are received.
 *
 * This listener handles:
 * - Stock receipt posting to inventory
 * - Lot tracking for batch-managed products
 * - Serial number registration for serialized products
 * - Warehouse bin location assignment
 *
 * Domain Events Published:
 * - StockReceivedEvent (from Inventory package)
 * - LotCreatedEvent (from Inventory package)
 * - SerialRegisteredEvent (from Inventory package)
 */
final readonly class UpdateInventoryOnGoodsReceipt
{
    public function __construct(
        private StockManagerInterface $stockManager,
        private ?LotManagerInterface $lotManager = null,
        private ?SerialNumberManagerInterface $serialNumberManager = null,
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
     * Handle the goods receipt created event.
     */
    public function handle(GoodsReceiptCreatedEvent $event): void
    {
        $this->getLogger()->info('Processing goods receipt for inventory update', [
            'goods_receipt_id' => $event->goodsReceiptId,
            'goods_receipt_number' => $event->goodsReceiptNumber,
            'purchase_order_id' => $event->purchaseOrderId,
            'warehouse_id' => $event->warehouseId,
            'line_count' => count($event->lineItems),
        ]);

        $processedCount = 0;
        $errorCount = 0;

        foreach ($event->lineItems as $lineItem) {
            try {
                $this->processLineItem($event, $lineItem);
                $processedCount++;
            } catch (\Throwable $e) {
                $errorCount++;
                $this->getLogger()->error('Failed to process inventory update for line item', [
                    'goods_receipt_id' => $event->goodsReceiptId,
                    'line_id' => $lineItem['lineId'],
                    'product_id' => $lineItem['productId'],
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->getLogger()->info('Completed inventory update for goods receipt', [
            'goods_receipt_id' => $event->goodsReceiptId,
            'processed_count' => $processedCount,
            'error_count' => $errorCount,
        ]);
    }

    /**
     * Process a single line item for inventory update.
     *
     * @param GoodsReceiptCreatedEvent $event The goods receipt event
     * @param array{
     *     lineId: string,
     *     poLineId: string,
     *     productId: string,
     *     description: string,
     *     quantityReceived: float,
     *     quantityOrdered: float,
     *     unitOfMeasure: string,
     *     unitCostCents: int,
     *     currency: string,
     *     lotNumber: string|null,
     *     serialNumbers: array<string>,
     *     expiryDate: string|null,
     *     binLocation: string|null
     * } $lineItem The line item data
     */
    private function processLineItem(GoodsReceiptCreatedEvent $event, array $lineItem): void
    {
        // Create lot if lot tracking is enabled and lot number provided
        if ($lineItem['lotNumber'] !== null && $this->lotManager !== null) {
            $this->createLot($event, $lineItem);
        }

        // Register serial numbers if serialized product
        if (!empty($lineItem['serialNumbers']) && $this->serialNumberManager !== null) {
            $this->registerSerialNumbers($event, $lineItem);
        }

        // Post stock receipt
        $this->postStockReceipt($event, $lineItem);
    }

    /**
     * Create a lot for batch-managed inventory.
     *
     * @param GoodsReceiptCreatedEvent $event The goods receipt event
     * @param array{
     *     lineId: string,
     *     productId: string,
     *     quantityReceived: float,
     *     lotNumber: string|null,
     *     expiryDate: string|null
     * } $lineItem The line item data
     */
    private function createLot(GoodsReceiptCreatedEvent $event, array $lineItem): void
    {
        if ($this->lotManager === null || $lineItem['lotNumber'] === null) {
            return;
        }

        $expiryDate = $lineItem['expiryDate'] !== null
            ? new \DateTimeImmutable($lineItem['expiryDate'])
            : null;

        $this->lotManager->createLot(
            tenantId: $event->tenantId,
            productId: $lineItem['productId'],
            lotNumber: $lineItem['lotNumber'],
            quantity: $lineItem['quantityReceived'],
            expiryDate: $expiryDate,
        );

        $this->getLogger()->debug('Created lot for received goods', [
            'goods_receipt_id' => $event->goodsReceiptId,
            'product_id' => $lineItem['productId'],
            'lot_number' => $lineItem['lotNumber'],
            'quantity' => $lineItem['quantityReceived'],
            'expiry_date' => $lineItem['expiryDate'],
        ]);
    }

    /**
     * Register serial numbers for serialized inventory.
     *
     * @param GoodsReceiptCreatedEvent $event The goods receipt event
     * @param array{
     *     lineId: string,
     *     productId: string,
     *     serialNumbers: array<string>
     * } $lineItem The line item data
     */
    private function registerSerialNumbers(GoodsReceiptCreatedEvent $event, array $lineItem): void
    {
        if ($this->serialNumberManager === null || empty($lineItem['serialNumbers'])) {
            return;
        }

        foreach ($lineItem['serialNumbers'] as $serialNumber) {
            $this->serialNumberManager->register(
                tenantId: $event->tenantId,
                productId: $lineItem['productId'],
                serialNumber: $serialNumber,
                reference: $event->goodsReceiptNumber,
            );
        }

        $this->getLogger()->debug('Registered serial numbers for received goods', [
            'goods_receipt_id' => $event->goodsReceiptId,
            'product_id' => $lineItem['productId'],
            'serial_count' => count($lineItem['serialNumbers']),
        ]);
    }

    /**
     * Post stock receipt to inventory.
     *
     * @param GoodsReceiptCreatedEvent $event The goods receipt event
     * @param array{
     *     lineId: string,
     *     productId: string,
     *     quantityReceived: float,
     *     unitCostCents: int,
     *     currency: string,
     *     lotNumber: string|null,
     *     expiryDate: string|null,
     *     binLocation: string|null
     * } $lineItem The line item data
     */
    private function postStockReceipt(GoodsReceiptCreatedEvent $event, array $lineItem): void
    {
        $unitCost = Money::fromCents($lineItem['unitCostCents'], $lineItem['currency']);

        $expiryDate = $lineItem['expiryDate'] !== null
            ? new \DateTimeImmutable($lineItem['expiryDate'])
            : null;

        $this->stockManager->receiveStock(
            tenantId: $event->tenantId,
            productId: $lineItem['productId'],
            warehouseId: $event->warehouseId,
            quantity: $lineItem['quantityReceived'],
            unitCost: $unitCost,
            lotNumber: $lineItem['lotNumber'],
            expiryDate: $expiryDate,
        );

        $this->getLogger()->debug('Posted stock receipt to inventory', [
            'goods_receipt_id' => $event->goodsReceiptId,
            'line_id' => $lineItem['lineId'],
            'product_id' => $lineItem['productId'],
            'warehouse_id' => $event->warehouseId,
            'quantity' => $lineItem['quantityReceived'],
            'unit_cost_cents' => $lineItem['unitCostCents'],
            'currency' => $lineItem['currency'],
        ]);
    }
}
