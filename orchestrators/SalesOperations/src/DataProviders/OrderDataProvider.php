<?php

declare(strict_types=1);

namespace Nexus\SalesOperations\DataProviders;

use Nexus\SalesOperations\Contracts\CreditManagerInterface;
use Nexus\SalesOperations\Contracts\CustomerProviderInterface;
use Nexus\SalesOperations\Contracts\SalesOrderProviderInterface;
use Nexus\SalesOperations\Contracts\StockReservationInterface;

final readonly class OrderDataProvider
{
    public function __construct(
        private SalesOrderProviderInterface $orderProvider,
        private CustomerProviderInterface $customerProvider,
        private CreditManagerInterface $creditManager,
        private ?StockReservationInterface $stockReservation = null,
    ) {}

    public function buildContext(string $tenantId, string $orderId): ?OrderContext
    {
        $order = $this->orderProvider->findById($tenantId, $orderId);

        if ($order === null) {
            return null;
        }

        $customer = $this->customerProvider->findById($tenantId, $order->getCustomerId());

        $creditInfo = $this->buildCreditInfo($tenantId, $order->getCustomerId());

        $stockInfo = $this->buildStockInfo($tenantId, $orderId);

        return new OrderContext(
            orderId: $order->getId(),
            orderNumber: $order->getOrderNumber(),
            tenantId: $tenantId,
            customerId: $order->getCustomerId(),
            customerName: $customer?->getName() ?? 'Unknown',
            customerCode: $customer?->getCode(),
            status: $order->getStatus(),
            subtotal: $order->getSubtotal(),
            taxAmount: $order->getTaxAmount(),
            total: $order->getTotal(),
            currencyCode: $order->getCurrencyCode(),
            paymentTerms: $order->getPaymentTerms(),
            shippingAddress: $order->getShippingAddress(),
            billingAddress: $order->getBillingAddress(),
            salespersonId: $order->getSalespersonId(),
            confirmedAt: $order->getConfirmedAt(),
            lines: $this->buildOrderLines($order->getLines()),
            credit: $creditInfo,
            stock: $stockInfo,
        );
    }

    public function getOrderSummary(string $tenantId, string $orderId): ?array
    {
        $order = $this->orderProvider->findById($tenantId, $orderId);

        if ($order === null) {
            return null;
        }

        return [
            'order_id' => $order->getId(),
            'order_number' => $order->getOrderNumber(),
            'customer_id' => $order->getCustomerId(),
            'status' => $order->getStatus(),
            'total' => $order->getTotal(),
            'currency' => $order->getCurrencyCode(),
            'line_count' => count($order->getLines()),
        ];
    }

    public function getOrdersByCustomer(string $tenantId, string $customerId): array
    {
        $orders = $this->orderProvider->findByCustomer($tenantId, $customerId);

        return array_map(fn($order) => [
            'order_id' => $order->getId(),
            'order_number' => $order->getOrderNumber(),
            'status' => $order->getStatus(),
            'total' => $order->getTotal(),
            'currency' => $order->getCurrencyCode(),
            'confirmed_at' => $order->getConfirmedAt()?->format('Y-m-d H:i:s'),
        ], $orders);
    }

    private function buildCreditInfo(string $tenantId, string $customerId): array
    {
        $creditLimit = $this->creditManager->getCreditLimit($tenantId, $customerId);
        $creditUsed = $this->creditManager->getCreditUsed($tenantId, $customerId);
        $isOnHold = $this->creditManager->isOnCreditHold($tenantId, $customerId);

        return [
            'limit' => $creditLimit,
            'used' => $creditUsed,
            'available' => $creditLimit - $creditUsed,
            'on_hold' => $isOnHold,
            'hold_reason' => $isOnHold 
                ? $this->creditManager->getCreditHoldReason($tenantId, $customerId) 
                : null,
        ];
    }

    private function buildStockInfo(string $tenantId, string $orderId): array
    {
        if ($this->stockReservation === null) {
            return ['reservations' => []];
        }

        $reservations = $this->stockReservation->getReservationsByOrder($tenantId, $orderId);

        return [
            'reservations' => $reservations,
            'total_reserved' => count($reservations),
        ];
    }

    private function buildOrderLines(array $lines): array
    {
        $result = [];

        foreach ($lines as $line) {
            $result[] = [
                'line_id' => $line->getId(),
                'product_variant_id' => $line->getProductVariantId(),
                'product_name' => $line->getProductName(),
                'quantity' => $line->getQuantity(),
                'quantity_shipped' => $line->getQuantityShipped(),
                'quantity_invoiced' => $line->getQuantityInvoiced(),
                'remaining_to_ship' => $line->getRemainingToShip(),
                'remaining_to_invoice' => $line->getRemainingToInvoice(),
                'unit_price' => $line->getUnitPrice(),
                'discount_percent' => $line->getDiscountPercent(),
                'line_total' => $line->getLineTotal(),
                'fully_shipped' => $line->isFullyShipped(),
                'fully_invoiced' => $line->isFullyInvoiced(),
            ];
        }

        return $result;
    }
}
