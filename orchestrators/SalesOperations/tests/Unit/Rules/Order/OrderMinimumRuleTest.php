<?php

declare(strict_types=1);

namespace Nexus\SalesOperations\Tests\Unit\Rules\Order;

use Nexus\SalesOperations\DTOs\CreateOrderRequest;
use Nexus\SalesOperations\Rules\Order\OrderMinimumRule;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(OrderMinimumRule::class)]
final class OrderMinimumRuleTest extends TestCase
{
    #[Test]
    public function check_passes_when_above_minimum(): void
    {
        $rule = new OrderMinimumRule(100.0);

        $context = new CreateOrderRequest(
            tenantId: 'tenant-1',
            customerId: 'cust-1',
            lines: [
                ['quantity' => 10, 'unit_price' => 20],
            ]
        );

        $result = $rule->check($context);

        $this->assertTrue($result->passed());
    }

    #[Test]
    public function check_fails_when_below_minimum(): void
    {
        $rule = new OrderMinimumRule(500.0);

        $context = new CreateOrderRequest(
            tenantId: 'tenant-1',
            customerId: 'cust-1',
            lines: [
                ['quantity' => 10, 'unit_price' => 20],
            ]
        );

        $result = $rule->check($context);

        $this->assertTrue($result->failed());
        $this->assertStringContainsString('below minimum', $result->message);
    }

    #[Test]
    public function check_calculates_total_with_discount(): void
    {
        $rule = new OrderMinimumRule(150.0);

        $context = new CreateOrderRequest(
            tenantId: 'tenant-1',
            customerId: 'cust-1',
            lines: [
                ['quantity' => 10, 'unit_price' => 20, 'discount_percent' => 10],
            ]
        );

        $result = $rule->check($context);

        $this->assertTrue($result->failed());
    }

    #[Test]
    public function check_with_zero_minimum_always_passes(): void
    {
        $rule = new OrderMinimumRule(0.0);

        $context = new CreateOrderRequest(
            tenantId: 'tenant-1',
            customerId: 'cust-1',
            lines: []
        );

        $result = $rule->check($context);

        $this->assertTrue($result->passed());
    }

    #[Test]
    public function get_name_returns_order_minimum(): void
    {
        $rule = new OrderMinimumRule(100.0);

        $this->assertSame('order_minimum', $rule->getName());
    }
}
