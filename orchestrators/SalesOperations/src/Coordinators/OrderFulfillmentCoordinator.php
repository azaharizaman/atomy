<?php

declare(strict_types=1);

namespace Nexus\SalesOperations\Coordinators;

use Nexus\SalesOperations\Contracts\SalesOrderProviderInterface;
use Nexus\SalesOperations\Contracts\SalesOrderInterface;
use Nexus\SalesOperations\Contracts\StockReservationInterface;
use Nexus\SalesOperations\Contracts\StockAvailabilityInterface;
use Nexus\SalesOperations\Contracts\ShipmentProviderInterface;
use Nexus\SalesOperations\Contracts\InvoiceProviderInterface;
use Nexus\SalesOperations\Contracts\AuditLoggerInterface;
use Nexus\SalesOperations\DTOs\FulfillmentRequest;
use Nexus\SalesOperations\DTOs\FulfillmentResult;
use Nexus\SalesOperations\Exceptions\InsufficientStockException;
use Psr\Log\LoggerInterface;

final readonly class OrderFulfillmentCoordinator
{
    public function __construct(
        private SalesOrderProviderInterface $orderProvider,
        private StockAvailabilityInterface $stockAvailability,
        private StockReservationInterface $stockReservation,
        private ShipmentProviderInterface $shipmentProvider,
        private InvoiceProviderInterface $invoiceProvider,
        private AuditLoggerInterface $auditLogger,
        private LoggerInterface $logger
    ) {}

    public function fulfill(FulfillmentRequest $request): FulfillmentResult
    {
        $order = $this->orderProvider->findById($request->tenantId, $request->orderId);

        if ($order === null) {
            return new FulfillmentResult(
                success: false,
                message: "Order {$request->orderId} not found"
            );
        }

        if (!$order->isConfirmed()) {
            return new FulfillmentResult(
                success: false,
                message: "Order {$request->orderId} is not confirmed"
            );
        }

        $availabilityIssues = $this->checkAvailability($request);
        if (!empty($availabilityIssues)) {
            return new FulfillmentResult(
                success: false,
                message: "Insufficient stock for some items",
                issues: $availabilityIssues
            );
        }

        $shipmentData = [
            'order_id' => $request->orderId,
            'warehouse_id' => $request->warehouseId,
            'lines' => $request->lines,
            'shipped_by' => $request->shippedBy,
            'tracking_number' => $request->trackingNumber,
            'carrier_code' => $request->carrierCode,
        ];

        $shipment = $this->shipmentProvider->create($request->tenantId, $shipmentData);

        $this->stockReservation->convertToAllocated($request->tenantId, $request->orderId);

        $invoice = $this->invoiceProvider->findByOrder($request->tenantId, $request->orderId);
        if ($invoice === null) {
            $invoice = $this->createInvoiceFromShipment($request->tenantId, $order, $shipment);
        }

        $this->auditLogger->log(
            logName: 'sales_order_fulfilled',
            description: "Order {$order->getOrderNumber()} fulfilled via Shipment {$shipment->getShipmentNumber()}"
        );

        $this->logger->info("Order {$request->orderId} fulfilled successfully");

        return new FulfillmentResult(
            success: true,
            shipmentId: $shipment->getId(),
            invoiceId: $invoice?->getId()
        );
    }

    private function checkAvailability(FulfillmentRequest $request): array
    {
        $issues = [];

        foreach ($request->lines as $line) {
            $availability = $this->stockAvailability->checkAvailability(
                $request->tenantId,
                $line['product_variant_id'],
                $request->warehouseId,
                $line['quantity']
            );

            if (!$availability->isAvailable()) {
                $issues[] = [
                    'product_id' => $line['product_variant_id'],
                    'requested' => $line['quantity'],
                    'available' => $availability->getAvailableQuantity(),
                    'shortage' => $availability->getShortageQuantity(),
                ];
            }
        }

        return $issues;
    }

    private function createInvoiceFromShipment(
        string $tenantId,
        SalesOrderInterface $order,
        $shipment
    ) {
        $invoiceData = [
            'order_id' => $order->getId(),
            'customer_id' => $order->getCustomerId(),
            'lines' => $shipment->getLines(),
        ];

        return $this->invoiceProvider->create($tenantId, $invoiceData);
    }
}
