<?php

declare(strict_types=1);

namespace Nexus\SalesOperations\Listeners;

use Nexus\SalesOperations\Contracts\StockReservationInterface;
use Nexus\SalesOperations\Events\OrderConfirmedEvent;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final readonly class ReserveStockListener
{
    public function __construct(
        private StockReservationInterface $stockReservation,
        private ?LoggerInterface $logger = null,
    ) {}

    private function getLogger(): LoggerInterface
    {
        return $this->logger ?? new NullLogger();
    }

    public function handle(OrderConfirmedEvent $event): void
    {
        $this->getLogger()->info('Reserving stock for confirmed order', [
            'order_id' => $event->orderId,
            'order_number' => $event->orderNumber,
            'line_count' => count($event->lines),
        ]);

        $reservedCount = 0;
        $failedCount = 0;

        foreach ($event->lines as $line) {
            $productId = $line['product_variant_id'] ?? $line->productVariantId ?? null;
            $quantity = $line['quantity'] ?? $line->quantity ?? 0;
            $warehouseId = $event->warehouseId ?? $line['warehouse_id'] ?? $line->warehouseId ?? 'default';

            if ($productId === null || $quantity <= 0) {
                continue;
            }

            try {
                $success = $this->stockReservation->reserve(
                    $event->tenantId,
                    $event->orderId,
                    $productId,
                    $warehouseId,
                    $quantity
                );

                if ($success) {
                    $reservedCount++;
                    $this->getLogger()->debug('Stock reserved', [
                        'order_id' => $event->orderId,
                        'product_id' => $productId,
                        'quantity' => $quantity,
                        'warehouse_id' => $warehouseId,
                    ]);
                } else {
                    $failedCount++;
                    $this->getLogger()->warning('Failed to reserve stock', [
                        'order_id' => $event->orderId,
                        'product_id' => $productId,
                        'quantity' => $quantity,
                    ]);
                }
            } catch (\Throwable $e) {
                $failedCount++;
                $this->getLogger()->error('Exception while reserving stock', [
                    'order_id' => $event->orderId,
                    'product_id' => $productId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->getLogger()->info('Stock reservation complete', [
            'order_id' => $event->orderId,
            'reserved_count' => $reservedCount,
            'failed_count' => $failedCount,
        ]);
    }

    public function __invoke(OrderConfirmedEvent $event): void
    {
        $this->handle($event);
    }
}
