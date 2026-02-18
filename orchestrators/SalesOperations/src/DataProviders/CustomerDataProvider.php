<?php

declare(strict_types=1);

namespace Nexus\SalesOperations\DataProviders;

use Nexus\SalesOperations\Contracts\CreditManagerInterface;
use Nexus\SalesOperations\Contracts\CustomerProviderInterface;
use Nexus\SalesOperations\Contracts\SalesOrderProviderInterface;

final readonly class CustomerDataProvider
{
    public function __construct(
        private CustomerProviderInterface $customerProvider,
        private SalesOrderProviderInterface $orderProvider,
        private CreditManagerInterface $creditManager,
    ) {}

    public function getCustomerContext(string $tenantId, string $customerId): ?CustomerContext
    {
        $customer = $this->customerProvider->findById($tenantId, $customerId);

        if ($customer === null) {
            return null;
        }

        $creditInfo = $this->buildCreditInfo($tenantId, $customerId);
        $orderSummary = $this->buildOrderSummary($tenantId, $customerId);

        return new CustomerContext(
            customerId: $customer->getId(),
            tenantId: $tenantId,
            name: $customer->getName(),
            code: $customer->getCode(),
            currencyCode: $customer->getCurrencyCode(),
            paymentTerms: $customer->getPaymentTerms(),
            pricingGroupId: $customer->getPricingGroupId(),
            salespersonId: $customer->getSalespersonId(),
            isActive: $customer->isActive(),
            creditLimit: $customer->getCreditLimit(),
            availableCredit: $customer->getAvailableCredit(),
            credit: $creditInfo,
            orders: $orderSummary,
        );
    }

    public function getCustomerCreditStatus(string $tenantId, string $customerId): array
    {
        $customer = $this->customerProvider->findById($tenantId, $customerId);

        if ($customer === null) {
            return [
                'found' => false,
                'customer_id' => $customerId,
            ];
        }

        $creditLimit = $this->creditManager->getCreditLimit($tenantId, $customerId);
        $creditUsed = $this->creditManager->getCreditUsed($tenantId, $customerId);
        $isOnHold = $this->creditManager->isOnCreditHold($tenantId, $customerId);

        return [
            'found' => true,
            'customer_id' => $customerId,
            'customer_name' => $customer->getName(),
            'credit_limit' => $creditLimit,
            'credit_used' => $creditUsed,
            'available_credit' => $creditLimit - $creditUsed,
            'on_hold' => $isOnHold,
            'hold_reason' => $isOnHold 
                ? $this->creditManager->getCreditHoldReason($tenantId, $customerId) 
                : null,
            'payment_terms' => $customer->getPaymentTerms(),
        ];
    }

    public function getCustomerOrderHistory(string $tenantId, string $customerId, int $limit = 10): array
    {
        $orders = $this->orderProvider->findByCustomer($tenantId, $customerId);

        $result = [];
        $count = 0;

        foreach ($orders as $order) {
            if ($count >= $limit) {
                break;
            }

            $result[] = [
                'order_id' => $order->getId(),
                'order_number' => $order->getOrderNumber(),
                'status' => $order->getStatus(),
                'total' => $order->getTotal(),
                'currency' => $order->getCurrencyCode(),
                'confirmed_at' => $order->getConfirmedAt()?->format('Y-m-d'),
            ];

            $count++;
        }

        return $result;
    }

    public function calculateCustomerMetrics(string $tenantId, string $customerId): array
    {
        $orders = $this->orderProvider->findByCustomer($tenantId, $customerId);

        $totalOrders = count($orders);
        $totalValue = 0.0;
        $confirmedOrders = 0;
        $cancelledOrders = 0;
        $currency = 'MYR';

        foreach ($orders as $order) {
            $totalValue += $order->getTotal();
            $currency = $order->getCurrencyCode();

            if ($order->isConfirmed()) {
                $confirmedOrders++;
            }

            if ($order->isCancelled()) {
                $cancelledOrders++;
            }
        }

        return [
            'customer_id' => $customerId,
            'total_orders' => $totalOrders,
            'confirmed_orders' => $confirmedOrders,
            'cancelled_orders' => $cancelledOrders,
            'total_value' => $totalValue,
            'average_order_value' => $totalOrders > 0 ? $totalValue / $totalOrders : 0,
            'currency' => $currency,
        ];
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
            'utilization_percent' => $creditLimit > 0 ? ($creditUsed / $creditLimit) * 100 : 0,
            'on_hold' => $isOnHold,
            'hold_reason' => $isOnHold 
                ? $this->creditManager->getCreditHoldReason($tenantId, $customerId) 
                : null,
        ];
    }

    private function buildOrderSummary(string $tenantId, string $customerId): array
    {
        $orders = $this->orderProvider->findByCustomer($tenantId, $customerId);

        $total = 0.0;
        $pending = 0;
        $confirmed = 0;
        $shipped = 0;

        foreach ($orders as $order) {
            $total += $order->getTotal();
            $status = $order->getStatus();

            if (in_array($status, ['draft', 'pending_credit'], true)) {
                $pending++;
            } elseif (in_array($status, ['confirmed', 'processing'], true)) {
                $confirmed++;
            } elseif (in_array($status, ['partially_shipped', 'fully_shipped'], true)) {
                $shipped++;
            }
        }

        return [
            'total_count' => count($orders),
            'total_value' => $total,
            'pending' => $pending,
            'confirmed' => $confirmed,
            'shipped' => $shipped,
        ];
    }
}
