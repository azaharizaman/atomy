<?php

declare(strict_types=1);

namespace Nexus\SalesOperations\DataProviders;

use Nexus\SalesOperations\Contracts\SalesOrderProviderInterface;
use Nexus\SalesOperations\Contracts\ShipmentProviderInterface;
use Nexus\SalesOperations\Contracts\StockAvailabilityInterface;
use Nexus\SalesOperations\Contracts\StockReservationInterface;

final readonly class FulfillmentDataProvider
{
    public function __construct(
        private SalesOrderProviderInterface $orderProvider,
        private ShipmentProviderInterface $shipmentProvider,
        private ?StockAvailabilityInterface $stockAvailability = null,
        private ?StockReservationInterface $stockReservation = null,
    ) {}

    public function getFulfillmentContext(string $tenantId, string $orderId): ?FulfillmentContext
    {
        $order = $this->orderProvider->findById($tenantId, $orderId);

        if ($order === null) {
            return null;
        }

        $shipments = $this->shipmentProvider->findByOrder($tenantId, $orderId);
        $stockAvailability = $this->buildStockAvailability($tenantId, $order->getLines());
        $fulfillmentStatus = $this->calculateFulfillmentStatus($order->getLines(), $shipments);

        return new FulfillmentContext(
            orderId: $order->getId(),
            orderNumber: $order->getOrderNumber(),
            tenantId: $tenantId,
            customerId: $order->getCustomerId(),
            orderStatus: $order->getStatus(),
            lines: $this->buildFulfillmentLines($order->getLines()),
            shipments: $this->buildShipments($shipments),
            stockAvailability: $stockAvailability,
            fulfillmentStatus: $fulfillmentStatus,
        );
    }

    public function getPendingShipments(string $tenantId): array
    {
        $confirmedOrders = $this->orderProvider->findByStatus($tenantId, 'confirmed');
        $partialOrders = $this->orderProvider->findByStatus($tenantId, 'partially_shipped');

        $allOrders = array_merge($confirmedOrders, $partialOrders);
        $result = [];

        foreach ($allOrders as $order) {
            $linesToShip = [];

            foreach ($order->getLines() as $line) {
                $remaining = $line->getRemainingToShip();
                if ($remaining > 0) {
                    $linesToShip[] = [
                        'line_id' => $line->getId(),
                        'product_variant_id' => $line->getProductVariantId(),
                        'product_name' => $line->getProductName(),
                        'quantity' => $remaining,
                    ];
                }
            }

            if (!empty($linesToShip)) {
                $result[] = [
                    'order_id' => $order->getId(),
                    'order_number' => $order->getOrderNumber(),
                    'customer_id' => $order->getCustomerId(),
                    'lines_to_ship' => $linesToShip,
                ];
            }
        }

        return $result;
    }

    public function checkStockForOrder(string $tenantId, string $orderId, ?string $warehouseId = null): array
    {
        $order = $this->orderProvider->findById($tenantId, $orderId);

        if ($order === null) {
            return ['found' => false];
        }

        if ($this->stockAvailability === null) {
            return ['found' => true, 'stock_check' => 'unavailable'];
        }

        $warehouse = $warehouseId ?? 'default';
        $stockStatus = [];
        $allAvailable = true;

        foreach ($order->getLines() as $line) {
            $productId = $line->getProductVariantId();
            $quantity = $line->getRemainingToShip();

            if ($quantity <= 0) {
                continue;
            }

            $available = $this->stockAvailability->getAvailableQuantity(
                $tenantId,
                $productId,
                $warehouse
            );

            $isAvailable = $available >= $quantity;

            $stockStatus[] = [
                'product_id' => $productId,
                'required' => $quantity,
                'available' => $available,
                'is_available' => $isAvailable,
            ];

            if (!$isAvailable) {
                $allAvailable = false;
            }
        }

        return [
            'found' => true,
            'order_id' => $orderId,
            'warehouse_id' => $warehouse,
            'all_available' => $allAvailable,
            'stock_status' => $stockStatus,
        ];
    }

    public function getShipmentHistory(string $tenantId, string $orderId): array
    {
        $shipments = $this->shipmentProvider->findByOrder($tenantId, $orderId);

        return array_map(fn($shipment) => [
            'shipment_id' => $shipment->getId(),
            'shipment_number' => $shipment->getShipmentNumber(),
            'status' => $shipment->getStatus(),
            'warehouse_id' => $shipment->getWarehouseId(),
            'tracking_number' => $shipment->getTrackingNumber(),
            'shipped_at' => $shipment->getShippedAt()?->format('Y-m-d H:i:s'),
            'line_count' => count($shipment->getLines()),
        ], $shipments);
    }

    private function buildStockAvailability(string $tenantId, array $lines): array
    {
        if ($this->stockAvailability === null) {
            return ['available' => true, 'details' => []];
        }

        $details = [];
        $allAvailable = true;

        foreach ($lines as $line) {
            $productId = $line->getProductVariantId();
            $totalAvailable = $this->stockAvailability->getTotalAvailableQuantity($tenantId, $productId);

            $details[$productId] = [
                'product_id' => $productId,
                'required' => $line->getQuantity(),
                'available' => $totalAvailable,
                'sufficient' => $totalAvailable >= $line->getQuantity(),
            ];

            if (!$details[$productId]['sufficient']) {
                $allAvailable = false;
            }
        }

        return [
            'available' => $allAvailable,
            'details' => $details,
        ];
    }

    private function calculateFulfillmentStatus(array $lines, array $shipments): array
    {
        $totalQuantity = 0;
        $shippedQuantity = 0;

        foreach ($lines as $line) {
            $totalQuantity += $line->getQuantity();
            $shippedQuantity += $line->getQuantityShipped();
        }

        $percentComplete = $totalQuantity > 0 ? ($shippedQuantity / $totalQuantity) * 100 : 0;

        return [
            'total_quantity' => $totalQuantity,
            'shipped_quantity' => $shippedQuantity,
            'remaining_quantity' => $totalQuantity - $shippedQuantity,
            'percent_complete' => round($percentComplete, 2),
            'shipment_count' => count($shipments),
            'is_complete' => $shippedQuantity >= $totalQuantity && $totalQuantity > 0,
        ];
    }

    private function buildFulfillmentLines(array $lines): array
    {
        $result = [];

        foreach ($lines as $line) {
            $result[] = [
                'line_id' => $line->getId(),
                'product_variant_id' => $line->getProductVariantId(),
                'product_name' => $line->getProductName(),
                'quantity_ordered' => $line->getQuantity(),
                'quantity_shipped' => $line->getQuantityShipped(),
                'remaining_to_ship' => $line->getRemainingToShip(),
                'unit_price' => $line->getUnitPrice(),
            ];
        }

        return $result;
    }

    private function buildShipments(array $shipments): array
    {
        $result = [];

        foreach ($shipments as $shipment) {
            $lines = [];

            foreach ($shipment->getLines() as $line) {
                $lines[] = [
                    'line_id' => $line->getId(),
                    'product_variant_id' => $line->getProductVariantId(),
                    'quantity_shipped' => $line->getQuantityShipped(),
                ];
            }

            $result[] = [
                'shipment_id' => $shipment->getId(),
                'shipment_number' => $shipment->getShipmentNumber(),
                'status' => $shipment->getStatus(),
                'warehouse_id' => $shipment->getWarehouseId(),
                'tracking_number' => $shipment->getTrackingNumber(),
                'shipped_at' => $shipment->getShippedAt(),
                'is_shipped' => $shipment->isShipped(),
                'lines' => $lines,
            ];
        }

        return $result;
    }
}
