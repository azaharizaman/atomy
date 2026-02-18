<?php

declare(strict_types=1);

namespace Nexus\SalesOperations\Listeners;

use Nexus\SalesOperations\Contracts\InvoiceProviderInterface;
use Nexus\SalesOperations\Contracts\SalesOrderProviderInterface;
use Nexus\SalesOperations\Events\ShipmentCreatedEvent;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final readonly class GenerateInvoiceListener
{
    public function __construct(
        private InvoiceProviderInterface $invoiceProvider,
        private SalesOrderProviderInterface $orderProvider,
        private EventDispatcherInterface $eventDispatcher,
        private ?LoggerInterface $logger = null,
    ) {}

    private function getLogger(): LoggerInterface
    {
        return $this->logger ?? new NullLogger();
    }

    public function handle(ShipmentCreatedEvent $event): void
    {
        $this->getLogger()->info('Generating invoice for shipment', [
            'shipment_id' => $event->shipmentId,
            'order_id' => $event->orderId,
        ]);

        try {
            $existingInvoice = $this->invoiceProvider->findByOrder(
                $event->tenantId,
                $event->orderId
            );

            if ($existingInvoice !== null) {
                $this->getLogger()->info('Invoice already exists for order', [
                    'order_id' => $event->orderId,
                    'invoice_id' => $existingInvoice->getId(),
                ]);
                return;
            }

            $order = $this->orderProvider->findById(
                $event->tenantId,
                $event->orderId
            );

            if ($order === null) {
                $this->getLogger()->warning('Order not found for invoice generation', [
                    'order_id' => $event->orderId,
                ]);
                return;
            }

            $invoice = $this->invoiceProvider->create($event->tenantId, [
                'order_id' => $event->orderId,
                'customer_id' => $order->getCustomerId(),
                'lines' => $order->getLines(),
            ]);

            $this->getLogger()->info('Invoice generated successfully', [
                'shipment_id' => $event->shipmentId,
                'order_id' => $event->orderId,
                'invoice_id' => $invoice->getId(),
                'invoice_number' => $invoice->getInvoiceNumber(),
                'total' => $invoice->getTotal(),
            ]);
        } catch (\Throwable $e) {
            $this->getLogger()->error('Failed to generate invoice', [
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
