<?php

declare(strict_types=1);

namespace Nexus\SalesOperations\DataProviders;

use Nexus\SalesOperations\Contracts\InvoiceProviderInterface;
use Nexus\SalesOperations\Contracts\SalesOrderProviderInterface;

final readonly class InvoiceDataProvider
{
    public function __construct(
        private InvoiceProviderInterface $invoiceProvider,
        private SalesOrderProviderInterface $orderProvider,
    ) {}

    public function getInvoiceContext(string $tenantId, string $orderId): ?InvoiceContext
    {
        $order = $this->orderProvider->findById($tenantId, $orderId);

        if ($order === null) {
            return null;
        }

        $invoice = $this->invoiceProvider->findByOrder($tenantId, $orderId);

        $invoiceInfo = $invoice !== null ? [
            'invoice_id' => $invoice->getId(),
            'invoice_number' => $invoice->getInvoiceNumber(),
            'status' => $invoice->getStatus(),
            'subtotal' => $invoice->getSubtotal(),
            'tax_amount' => $invoice->getTaxAmount(),
            'total' => $invoice->getTotal(),
            'balance_due' => $invoice->getBalanceDue(),
            'currency' => $invoice->getCurrencyCode(),
            'invoice_date' => $invoice->getInvoiceDate()->format('Y-m-d'),
            'due_date' => $invoice->getDueDate()->format('Y-m-d'),
            'is_paid' => $invoice->isPaid(),
            'is_overdue' => $invoice->isOverdue(),
        ] : null;

        $invoiceStatus = $this->calculateInvoiceStatus($order->getLines(), $invoice);

        return new InvoiceContext(
            orderId: $order->getId(),
            orderNumber: $order->getOrderNumber(),
            tenantId: $tenantId,
            customerId: $order->getCustomerId(),
            orderStatus: $order->getStatus(),
            orderTotal: $order->getTotal(),
            currencyCode: $order->getCurrencyCode(),
            paymentTerms: $order->getPaymentTerms(),
            lines: $this->buildInvoiceLines($order->getLines()),
            invoice: $invoiceInfo,
            invoiceStatus: $invoiceStatus,
        );
    }

    public function getPendingInvoices(string $tenantId): array
    {
        $orders = $this->orderProvider->findByStatus($tenantId, 'confirmed');
        $partialShipped = $this->orderProvider->findByStatus($tenantId, 'partially_shipped');
        $fullyShipped = $this->orderProvider->findByStatus($tenantId, 'fully_shipped');

        $allOrders = array_merge($orders, $partialShipped, $fullyShipped);
        $result = [];

        foreach ($allOrders as $order) {
            $invoice = $this->invoiceProvider->findByOrder($tenantId, $order->getId());

            $linesToInvoice = [];
            foreach ($order->getLines() as $line) {
                $remaining = $line->getRemainingToInvoice();
                if ($remaining > 0) {
                    $linesToInvoice[] = [
                        'line_id' => $line->getId(),
                        'product_variant_id' => $line->getProductVariantId(),
                        'product_name' => $line->getProductName(),
                        'quantity' => $remaining,
                        'unit_price' => $line->getUnitPrice(),
                    ];
                }
            }

            if (!empty($linesToInvoice) || $invoice === null) {
                $result[] = [
                    'order_id' => $order->getId(),
                    'order_number' => $order->getOrderNumber(),
                    'customer_id' => $order->getCustomerId(),
                    'has_invoice' => $invoice !== null,
                    'invoice_number' => $invoice?->getInvoiceNumber(),
                    'balance_due' => $invoice?->getBalanceDue(),
                    'lines_to_invoice' => $linesToInvoice,
                ];
            }
        }

        return $result;
    }

    public function getOverdueInvoices(string $tenantId): array
    {
        $orders = $this->orderProvider->findByStatus($tenantId, 'invoiced');
        $result = [];

        foreach ($orders as $order) {
            $invoice = $this->invoiceProvider->findByOrder($tenantId, $order->getId());

            if ($invoice !== null && $invoice->isOverdue()) {
                $result[] = [
                    'invoice_id' => $invoice->getId(),
                    'invoice_number' => $invoice->getInvoiceNumber(),
                    'order_id' => $order->getId(),
                    'order_number' => $order->getOrderNumber(),
                    'customer_id' => $order->getCustomerId(),
                    'total' => $invoice->getTotal(),
                    'balance_due' => $invoice->getBalanceDue(),
                    'currency' => $invoice->getCurrencyCode(),
                    'due_date' => $invoice->getDueDate()->format('Y-m-d'),
                    'days_overdue' => $this->calculateDaysOverdue($invoice->getDueDate()),
                ];
            }
        }

        return $result;
    }

    public function calculateOrderTotals(string $tenantId, string $orderId): array
    {
        $order = $this->orderProvider->findById($tenantId, $orderId);

        if ($order === null) {
            return ['found' => false];
        }

        $subtotal = 0.0;
        $taxAmount = 0.0;
        $lineCount = 0;

        foreach ($order->getLines() as $line) {
            $lineTotal = $line->getLineTotal();
            $subtotal += $lineTotal;
            $lineCount++;
        }

        $taxRate = 0.10; // Default 10% tax
        $taxAmount = $subtotal * $taxRate;
        $total = $subtotal + $taxAmount;

        return [
            'found' => true,
            'order_id' => $orderId,
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'tax_rate' => $taxRate,
            'total' => $total,
            'currency' => $order->getCurrencyCode(),
            'line_count' => $lineCount,
        ];
    }

    private function calculateInvoiceStatus(array $lines, ?object $invoice): array
    {
        $totalQuantity = 0;
        $invoicedQuantity = 0;

        foreach ($lines as $line) {
            $totalQuantity += $line->getQuantity();
            $invoicedQuantity += $line->getQuantityInvoiced();
        }

        $percentComplete = $totalQuantity > 0 ? ($invoicedQuantity / $totalQuantity) * 100 : 0;

        return [
            'total_quantity' => $totalQuantity,
            'invoiced_quantity' => $invoicedQuantity,
            'remaining_quantity' => $totalQuantity - $invoicedQuantity,
            'percent_complete' => round($percentComplete, 2),
            'has_invoice' => $invoice !== null,
            'is_fully_invoiced' => $invoicedQuantity >= $totalQuantity && $totalQuantity > 0,
            'is_paid' => $invoice?->isPaid() ?? false,
            'balance_due' => $invoice?->getBalanceDue() ?? 0,
        ];
    }

    private function calculateDaysOverdue(\DateTimeImmutable $dueDate): int
    {
        $today = new \DateTimeImmutable('today');
        $diff = $today->diff($dueDate);

        return $diff->invert ? $diff->days : 0;
    }

    private function buildInvoiceLines(array $lines): array
    {
        $result = [];

        foreach ($lines as $line) {
            $result[] = [
                'line_id' => $line->getId(),
                'product_variant_id' => $line->getProductVariantId(),
                'product_name' => $line->getProductName(),
                'quantity_ordered' => $line->getQuantity(),
                'quantity_invoiced' => $line->getQuantityInvoiced(),
                'remaining_to_invoice' => $line->getRemainingToInvoice(),
                'unit_price' => $line->getUnitPrice(),
                'discount_percent' => $line->getDiscountPercent(),
                'line_total' => $line->getLineTotal(),
            ];
        }

        return $result;
    }
}
