<?php

declare(strict_types=1);

namespace Nexus\SalesOperations\Listeners;

use Nexus\SalesOperations\Contracts\StockReservationInterface;
use Nexus\SalesOperations\Events\ShipmentCreatedEvent;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final readonly class UpdateInventoryListener
{
    public function __construct(
        private StockReservationInterface $stockReservation,
        private ?LoggerInterface $logger = null,
    ) {}

    private function getLogger(): LoggerInterface
    {
        return $this->logger ?? new NullLogger();
    }

    public function handle(ShipmentCreatedEvent $event): void
    {
        $this->getLogger()->info('Updating inventory for shipment', [
            'shipment_id' => $event->shipmentId,
            'order_id' => $event->orderId,
            'warehouse_id' => $event->warehouseId,
        ]);

        try {
            $this->stockReservation->convertToAllocated(
                $event->tenantId,
                $event->orderId
            );

            $this->getLogger()->info('Inventory updated - reservations converted to allocations', [
                'shipment_id' => $event->shipmentId,
                'order_id' => $event->orderId,
                'line_count' => count($event->lines),
            ]);

            foreach ($event->lines as $line) {
                $productId = $line['product_variant_id'] ?? $line->productVariantId ?? null;
                $quantity = $line['quantity_shipped'] ?? $line['quantity'] ?? $line->quantityShipped ?? 0;

                if ($productId === null) {
                    continue;
                }

                $this->getLogger()->debug('Inventory deduction recorded', [
                    'shipment_id' => $event->shipmentId,
                    'product_id' => $productId,
                    'quantity' => $quantity,
                    'warehouse_id' => $event->warehouseId,
                ]);
            }
        } catch (\Throwable $e) {
            $this->getLogger()->error('Failed to update inventory', [
                'shipment_id' => $event->shipmentId,
                'order_id' => $event->orderId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function __invoke(ShipmentCreatedEvent $event): void
    {
        $this->handle($event);
    }
}
