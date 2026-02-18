<?php

declare(strict_types=1);

namespace Nexus\SalesOperations\Tests\Unit\Rules\Order;

use Nexus\SalesOperations\Contracts\SalesOrderProviderInterface;
use Nexus\SalesOperations\DTOs\FulfillmentRequest;
use Nexus\SalesOperations\Rules\Order\OrderStatusRule;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(OrderStatusRule::class)]
final class OrderStatusRuleTest extends TestCase
{
    #[Test]
    public function check_passes_for_confirmed_status(): void
    {
        $order = new class {
            public function getStatus(): string { return 'confirmed'; }
        };

        $orderProvider = $this->createMock(SalesOrderProviderInterface::class);
        $orderProvider->method('findById')->willReturn($order);

        $rule = new OrderStatusRule($orderProvider);

        $context = new FulfillmentRequest(
            tenantId: 'tenant-1',
            orderId: 'order-1',
            warehouseId: 'wh-1',
            lines: []
        );

        $result = $rule->check($context);

        $this->assertTrue($result->passed());
    }

    #[Test]
    public function check_passes_for_processing_status(): void
    {
        $order = new class {
            public function getStatus(): string { return 'processing'; }
        };

        $orderProvider = $this->createMock(SalesOrderProviderInterface::class);
        $orderProvider->method('findById')->willReturn($order);

        $rule = new OrderStatusRule($orderProvider);

        $context = new FulfillmentRequest(
            tenantId: 'tenant-1',
            orderId: 'order-1',
            warehouseId: 'wh-1',
            lines: []
        );

        $result = $rule->check($context);

        $this->assertTrue($result->passed());
    }

    #[Test]
    public function check_fails_for_draft_status(): void
    {
        $order = new class {
            public function getStatus(): string { return 'draft'; }
        };

        $orderProvider = $this->createMock(SalesOrderProviderInterface::class);
        $orderProvider->method('findById')->willReturn($order);

        $rule = new OrderStatusRule($orderProvider);

        $context = new FulfillmentRequest(
            tenantId: 'tenant-1',
            orderId: 'order-1',
            warehouseId: 'wh-1',
            lines: []
        );

        $result = $rule->check($context);

        $this->assertTrue($result->failed());
        $this->assertStringContainsString('not in a fulfillable status', $result->message);
    }

    #[Test]
    public function check_fails_for_cancelled_status(): void
    {
        $order = new class {
            public function getStatus(): string { return 'cancelled'; }
        };

        $orderProvider = $this->createMock(SalesOrderProviderInterface::class);
        $orderProvider->method('findById')->willReturn($order);

        $rule = new OrderStatusRule($orderProvider);

        $context = new FulfillmentRequest(
            tenantId: 'tenant-1',
            orderId: 'order-1',
            warehouseId: 'wh-1',
            lines: []
        );

        $result = $rule->check($context);

        $this->assertTrue($result->failed());
    }

    #[Test]
    public function check_fails_for_missing_order(): void
    {
        $orderProvider = $this->createMock(SalesOrderProviderInterface::class);
        $orderProvider->method('findById')->willReturn(null);

        $rule = new OrderStatusRule($orderProvider);

        $context = new FulfillmentRequest(
            tenantId: 'tenant-1',
            orderId: 'order-1',
            warehouseId: 'wh-1',
            lines: []
        );

        $result = $rule->check($context);

        $this->assertTrue($result->failed());
        $this->assertStringContainsString('not found', $result->message);
    }
}
