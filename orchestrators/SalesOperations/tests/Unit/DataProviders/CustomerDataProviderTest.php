<?php

declare(strict_types=1);

namespace Nexus\SalesOperations\Tests\Unit\DataProviders;

use Nexus\SalesOperations\Contracts\CreditManagerInterface;
use Nexus\SalesOperations\Contracts\CustomerProviderInterface;
use Nexus\SalesOperations\Contracts\SalesOrderProviderInterface;
use Nexus\SalesOperations\DataProviders\CustomerDataProvider;
use Nexus\SalesOperations\DataProviders\CustomerContext;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(CustomerDataProvider::class)]
#[CoversClass(CustomerContext::class)]
final class CustomerDataProviderTest extends TestCase
{
    private CustomerDataProvider $provider;
    private $customerProvider;
    private $orderProvider;
    private $creditManager;

    protected function setUp(): void
    {
        $this->customerProvider = $this->createMock(CustomerProviderInterface::class);
        $this->orderProvider = $this->createMock(SalesOrderProviderInterface::class);
        $this->creditManager = $this->createMock(CreditManagerInterface::class);

        $this->provider = new CustomerDataProvider(
            $this->customerProvider,
            $this->orderProvider,
            $this->creditManager
        );
    }

    #[Test]
    public function get_customer_context_returns_null_for_missing_customer(): void
    {
        $this->customerProvider->method('findById')->willReturn(null);

        $result = $this->provider->getCustomerContext('tenant-1', 'cust-1');

        $this->assertNull($result);
    }

    #[Test]
    public function get_customer_credit_status_returns_found_false_for_missing(): void
    {
        $this->customerProvider->method('findById')->willReturn(null);

        $result = $this->provider->getCustomerCreditStatus('tenant-1', 'cust-1');

        $this->assertFalse($result['found']);
    }

    #[Test]
    public function get_customer_order_history_returns_orders(): void
    {
        $order = new class {
            public function getId(): string { return 'order-1'; }
            public function getOrderNumber(): string { return 'SO-001'; }
            public function getStatus(): string { return 'confirmed'; }
            public function getTotal(): float { return 1000.0; }
            public function getCurrencyCode(): string { return 'MYR'; }
            public function getConfirmedAt(): ?\DateTimeImmutable { return new \DateTimeImmutable(); }
        };

        $this->orderProvider->method('findByCustomer')->willReturn([$order]);

        $result = $this->provider->getCustomerOrderHistory('tenant-1', 'cust-1', 5);

        $this->assertCount(1, $result);
        $this->assertSame('order-1', $result[0]['order_id']);
    }

    #[Test]
    public function calculate_customer_metrics(): void
    {
        $order1 = new class {
            public function getTotal(): float { return 1000.0; }
            public function getCurrencyCode(): string { return 'MYR'; }
            public function isConfirmed(): bool { return true; }
            public function isCancelled(): bool { return false; }
        };

        $order2 = new class {
            public function getTotal(): float { return 500.0; }
            public function getCurrencyCode(): string { return 'MYR'; }
            public function isConfirmed(): bool { return false; }
            public function isCancelled(): bool { return true; }
        };

        $this->orderProvider->method('findByCustomer')->willReturn([$order1, $order2]);

        $result = $this->provider->calculateCustomerMetrics('tenant-1', 'cust-1');

        $this->assertSame(2, $result['total_orders']);
        $this->assertSame(1500.0, $result['total_value']);
        $this->assertSame(750.0, $result['average_order_value']);
        $this->assertSame(1, $result['confirmed_orders']);
        $this->assertSame(1, $result['cancelled_orders']);
    }
}
