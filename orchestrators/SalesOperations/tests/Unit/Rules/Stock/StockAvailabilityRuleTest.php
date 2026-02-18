<?php

declare(strict_types=1);

namespace Nexus\SalesOperations\Tests\Unit\Rules\Stock;

use Nexus\SalesOperations\Contracts\StockAvailabilityInterface;
use Nexus\SalesOperations\DTOs\FulfillmentRequest;
use Nexus\SalesOperations\Rules\Stock\StockAvailabilityRule;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(StockAvailabilityRule::class)]
final class StockAvailabilityRuleTest extends TestCase
{
    #[Test]
    public function check_passes_when_stock_available(): void
    {
        $availabilityResult = new class implements \Nexus\SalesOperations\Contracts\AvailabilityResultInterface {
            public function isAvailable(): bool { return true; }
            public function getAvailableQuantity(): float { return 100.0; }
            public function getRequestedQuantity(): float { return 50.0; }
            public function getShortageQuantity(): float { return 0.0; }
            public function getWarehouseId(): string { return 'wh-1'; }
        };

        $stockAvailability = $this->createMock(StockAvailabilityInterface::class);
        $stockAvailability->method('checkAvailability')->willReturn($availabilityResult);

        $rule = new StockAvailabilityRule($stockAvailability);

        $context = new FulfillmentRequest(
            tenantId: 'tenant-1',
            orderId: 'order-1',
            warehouseId: 'wh-1',
            lines: [
                ['product_variant_id' => 'prod-1', 'quantity' => 50],
            ]
        );

        $result = $rule->check($context);

        $this->assertTrue($result->passed());
    }

    #[Test]
    public function check_fails_when_stock_insufficient(): void
    {
        $availabilityResult = new class implements \Nexus\SalesOperations\Contracts\AvailabilityResultInterface {
            public function isAvailable(): bool { return false; }
            public function getAvailableQuantity(): float { return 30.0; }
            public function getRequestedQuantity(): float { return 50.0; }
            public function getShortageQuantity(): float { return 20.0; }
            public function getWarehouseId(): string { return 'wh-1'; }
        };

        $stockAvailability = $this->createMock(StockAvailabilityInterface::class);
        $stockAvailability->method('checkAvailability')->willReturn($availabilityResult);

        $rule = new StockAvailabilityRule($stockAvailability);

        $context = new FulfillmentRequest(
            tenantId: 'tenant-1',
            orderId: 'order-1',
            warehouseId: 'wh-1',
            lines: [
                ['product_variant_id' => 'prod-1', 'quantity' => 50],
            ]
        );

        $result = $rule->check($context);

        $this->assertTrue($result->failed());
        $this->assertStringContainsString('Insufficient stock', $result->message);
    }

    #[Test]
    public function check_reports_multiple_shortages(): void
    {
        $unavailable = new class implements \Nexus\SalesOperations\Contracts\AvailabilityResultInterface {
            public function isAvailable(): bool { return false; }
            public function getAvailableQuantity(): float { return 10.0; }
            public function getRequestedQuantity(): float { return 50.0; }
            public function getShortageQuantity(): float { return 40.0; }
            public function getWarehouseId(): string { return 'wh-1'; }
        };

        $stockAvailability = $this->createMock(StockAvailabilityInterface::class);
        $stockAvailability->method('checkAvailability')->willReturn($unavailable);

        $rule = new StockAvailabilityRule($stockAvailability);

        $context = new FulfillmentRequest(
            tenantId: 'tenant-1',
            orderId: 'order-1',
            warehouseId: 'wh-1',
            lines: [
                ['product_variant_id' => 'prod-1', 'quantity' => 50],
                ['product_variant_id' => 'prod-2', 'quantity' => 50],
            ]
        );

        $result = $rule->check($context);

        $this->assertTrue($result->failed());
        $this->assertCount(2, $result->context['shortages']);
    }

    #[Test]
    public function get_name_returns_stock_availability(): void
    {
        $stockAvailability = $this->createMock(StockAvailabilityInterface::class);
        $rule = new StockAvailabilityRule($stockAvailability);

        $this->assertSame('stock_availability', $rule->getName());
    }
}
