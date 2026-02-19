<?php

declare(strict_types=1);

namespace Nexus\Laravel\Sales\Adapters;

use Nexus\Sales\Contracts\InvoiceManagerInterface;
use Nexus\Sales\Contracts\SalesOrderRepositoryInterface;
use Nexus\Sales\Exceptions\SalesOrderNotFoundException;
use Nexus\Receivable\Contracts\ReceivableManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Invoice manager adapter that integrates with Nexus\Receivable package.
 *
 * This adapter implements the Sales package's InvoiceManagerInterface by
 * delegating invoice generation to the Receivable package's ReceivableManager.
 *
 * This adapter belongs in the Laravel adapter layer because it requires
 * the Receivable package as a concrete dependency, which would violate
 * the atomic package independence rule if placed in the Sales package.
 */
final readonly class ReceivableInvoiceManagerAdapter implements InvoiceManagerInterface
{
    public function __construct(
        private SalesOrderRepositoryInterface $salesOrderRepository,
        private ReceivableManagerInterface $receivableManager,
        private LoggerInterface $logger
    ) {}

    /**
     * {@inheritDoc}
     */
    public function generateInvoiceFromOrder(string $salesOrderId): string
    {
        $this->logger->info('Generating invoice from sales order via Receivable adapter', [
            'sales_order_id' => $salesOrderId,
        ]);

        $order = $this->getSalesOrder($salesOrderId);

        // Create invoice from the sales order
        // The ReceivableManager will snapshot prices, taxes, and terms from the order
        $invoice = $this->receivableManager->createInvoiceFromOrder(
            tenantId: $order->getTenantId(),
            salesOrderId: $salesOrderId,
            overrides: $this->buildInvoiceOverrides($order)
        );

        $invoiceId = $invoice->getId();

        $this->logger->info('Invoice generated from sales order', [
            'sales_order_id' => $salesOrderId,
            'invoice_id' => $invoiceId,
        ]);

        return $invoiceId;
    }

    /**
     * Build invoice override data from sales order.
     *
     * @param \Nexus\Sales\Contracts\SalesOrderInterface $order
     * @return array<string, mixed>
     */
    private function buildInvoiceOverrides(\Nexus\Sales\Contracts\SalesOrderInterface $order): array
    {
        $overrides = [
            'customer_po_number' => $order->getCustomerPurchaseOrder(),
            'salesperson_id' => $order->getSalespersonId(),
            'notes' => $order->getNotes(),
        ];

        // Only add shipping/billing addresses if they're set
        if ($order->getShippingAddress() !== null) {
            $overrides['shipping_address'] = $order->getShippingAddress();
        }

        if ($order->getBillingAddress() !== null) {
            $overrides['billing_address'] = $order->getBillingAddress();
        }

        return $overrides;
    }

    /**
     * Get sales order by ID.
     *
     * @throws SalesOrderNotFoundException
     */
    private function getSalesOrder(string $salesOrderId): \Nexus\Sales\Contracts\SalesOrderInterface
    {
        try {
            return $this->salesOrderRepository->findById($salesOrderId);
        } catch (SalesOrderNotFoundException $e) {
            throw $e;
        }
    }
}