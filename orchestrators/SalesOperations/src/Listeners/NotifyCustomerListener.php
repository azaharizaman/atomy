<?php

declare(strict_types=1);

namespace Nexus\SalesOperations\Listeners;

use Nexus\SalesOperations\Contracts\NotificationInterface;
use Nexus\SalesOperations\Events\OrderConfirmedEvent;
use Nexus\SalesOperations\Events\ShipmentCreatedEvent;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final readonly class NotifyCustomerListener
{
    public function __construct(
        private NotificationInterface $notifier,
        private ?LoggerInterface $logger = null,
    ) {}

    private function getLogger(): LoggerInterface
    {
        return $this->logger ?? new NullLogger();
    }

    public function handleOrderConfirmed(OrderConfirmedEvent $event): void
    {
        $this->getLogger()->info('Sending order confirmation notification', [
            'order_id' => $event->orderId,
            'customer_id' => $event->customerId,
        ]);

        try {
            $this->notifier->notify(
                recipient: $event->customerId,
                subject: "Order Confirmed: {$event->orderNumber}",
                message: $this->buildOrderConfirmedMessage($event),
                data: [
                    'order_id' => $event->orderId,
                    'order_number' => $event->orderNumber,
                    'total_amount' => $event->totalAmount,
                    'currency' => $event->currencyCode,
                ]
            );

            $this->getLogger()->info('Order confirmation notification sent', [
                'order_id' => $event->orderId,
                'customer_id' => $event->customerId,
            ]);
        } catch (\Throwable $e) {
            $this->getLogger()->error('Failed to send order confirmation notification', [
                'order_id' => $event->orderId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function handleShipmentCreated(ShipmentCreatedEvent $event): void
    {
        $this->getLogger()->info('Sending shipment notification', [
            'shipment_id' => $event->shipmentId,
            'order_id' => $event->orderId,
        ]);

        try {
            $trackingInfo = $event->trackingNumber 
                ? "Tracking Number: {$event->trackingNumber}" 
                : '';

            $this->notifier->notify(
                recipient: $event->orderId,
                subject: "Your Order Has Shipped: {$event->shipmentNumber}",
                message: $this->buildShipmentMessage($event),
                data: [
                    'shipment_id' => $event->shipmentId,
                    'shipment_number' => $event->shipmentNumber,
                    'order_id' => $event->orderId,
                    'tracking_number' => $event->trackingNumber,
                ]
            );

            $this->getLogger()->info('Shipment notification sent', [
                'shipment_id' => $event->shipmentId,
                'order_id' => $event->orderId,
            ]);
        } catch (\Throwable $e) {
            $this->getLogger()->error('Failed to send shipment notification', [
                'shipment_id' => $event->shipmentId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function buildOrderConfirmedMessage(OrderConfirmedEvent $event): string
    {
        return sprintf(
            "Your order %s has been confirmed.\n\nTotal: %s %.2f\n\nThank you for your business!",
            $event->orderNumber,
            $event->currencyCode,
            $event->totalAmount
        );
    }

    private function buildShipmentMessage(ShipmentCreatedEvent $event): string
    {
        $tracking = $event->trackingNumber 
            ? "\n\nTracking Number: {$event->trackingNumber}" 
            : '';

        return sprintf(
            "Your shipment %s is on its way!%s\n\nItems: %d",
            $event->shipmentNumber,
            $tracking,
            count($event->lines)
        );
    }
}
