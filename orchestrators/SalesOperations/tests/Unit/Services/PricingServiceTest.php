<?php

declare(strict_types=1);

namespace Nexus\SalesOperations\Tests\Unit\Services;

use Nexus\SalesOperations\Services\PricingService;
use Nexus\SalesOperations\Services\PricingInput;
use Nexus\SalesOperations\Services\PricingResult;
use Nexus\SalesOperations\Services\PriceOverrideValidation;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(PricingService::class)]
#[CoversClass(PricingInput::class)]
#[CoversClass(PricingResult::class)]
#[CoversClass(PriceOverrideValidation::class)]
final class PricingServiceTest extends TestCase
{
    private PricingService $service;

    protected function setUp(): void
    {
        $this->service = new PricingService(
            defaultVolumeThreshold: 100,
            defaultVolumeDiscount: 5.0,
            maxDiscountPercent: 30.0
        );
    }

    #[Test]
    public function calculate_price_without_discounts(): void
    {
        $input = new PricingInput(
            productId: 'prod-1',
            customerId: 'cust-1',
            listPrice: 100.0,
            quantity: 10,
            currencyCode: 'MYR'
        );

        $result = $this->service->calculatePrice($input);

        $this->assertSame(100.0, $result->listPrice);
        $this->assertSame(0.0, $result->totalDiscountPercent);
        $this->assertSame(100.0, $result->finalPrice);
        $this->assertSame(1000.0, $result->lineTotal);
    }

    #[Test]
    public function calculate_price_with_volume_discount(): void
    {
        $input = new PricingInput(
            productId: 'prod-1',
            customerId: 'cust-1',
            listPrice: 100.0,
            quantity: 100,
            currencyCode: 'MYR'
        );

        $result = $this->service->calculatePrice($input);

        $this->assertArrayHasKey('volume', $result->discounts);
        $this->assertSame(5.0, $result->discounts['volume']);
        $this->assertSame(95.0, $result->finalPrice);
    }

    #[Test]
    public function calculate_price_with_customer_discount(): void
    {
        $input = new PricingInput(
            productId: 'prod-1',
            customerId: 'cust-1',
            listPrice: 100.0,
            quantity: 10,
            currencyCode: 'MYR',
            customerDiscountPercent: 10.0
        );

        $result = $this->service->calculatePrice($input);

        $this->assertArrayHasKey('customer', $result->discounts);
        $this->assertSame(10.0, $result->discounts['customer']);
        $this->assertSame(90.0, $result->finalPrice);
    }

    #[Test]
    public function calculate_price_with_promo_discount(): void
    {
        $input = new PricingInput(
            productId: 'prod-1',
            customerId: 'cust-1',
            listPrice: 100.0,
            quantity: 10,
            currencyCode: 'MYR',
            promoDiscountPercent: 15.0
        );

        $result = $this->service->calculatePrice($input);

        $this->assertArrayHasKey('promo', $result->discounts);
        $this->assertSame(15.0, $result->discounts['promo']);
        $this->assertSame(85.0, $result->finalPrice);
    }

    #[Test]
    public function calculate_price_caps_at_max_discount(): void
    {
        $input = new PricingInput(
            productId: 'prod-1',
            customerId: 'cust-1',
            listPrice: 100.0,
            quantity: 100,
            currencyCode: 'MYR',
            customerDiscountPercent: 20.0,
            promoDiscountPercent: 15.0
        );

        $result = $this->service->calculatePrice($input);

        $this->assertSame(30.0, $result->totalDiscountPercent);
        $this->assertSame(70.0, $result->finalPrice);
    }

    #[Test]
    public function validate_price_override_within_limit(): void
    {
        $validation = $this->service->validatePriceOverride(
            100.0,
            90.0,
            'Customer loyalty discount'
        );

        $this->assertTrue($validation->isValid);
        $this->assertFalse($validation->requiresApproval);
        $this->assertSame(10.0, $validation->discountPercent);
    }

    #[Test]
    public function validate_price_override_requires_approval(): void
    {
        $validation = $this->service->validatePriceOverride(
            100.0,
            85.0,
            'Special pricing'
        );

        $this->assertTrue($validation->isValid);
        $this->assertTrue($validation->requiresApproval);
        $this->assertFalse($validation->isApproved());
    }

    #[Test]
    public function validate_price_override_with_approver(): void
    {
        $validation = $this->service->validatePriceOverride(
            100.0,
            85.0,
            'Special pricing',
            'manager-1'
        );

        $this->assertTrue($validation->requiresApproval);
        $this->assertTrue($validation->isApproved());
    }

    #[Test]
    public function validate_price_override_exceeds_max(): void
    {
        $validation = $this->service->validatePriceOverride(
            100.0,
            60.0,
            'Below cost'
        );

        $this->assertFalse($validation->isValid);
        $this->assertSame(40.0, $validation->discountPercent);
    }

    #[Test]
    public function calculate_tiered_pricing(): void
    {
        $tiers = [
            ['minQuantity' => 1, 'maxQuantity' => 10, 'price' => 100.0],
            ['minQuantity' => 11, 'maxQuantity' => 50, 'price' => 90.0],
            ['minQuantity' => 51, 'maxQuantity' => null, 'price' => 80.0],
        ];

        $result = $this->service->calculateTieredPricing(100.0, $tiers);

        $this->assertSame(3, $result->getTierCount());
        $this->assertSame(100.0, $result->basePrice);
    }

    #[Test]
    public function get_quantity_tier(): void
    {
        $tiers = [
            ['minQuantity' => 1, 'price' => 100.0],
            ['minQuantity' => 11, 'price' => 90.0],
            ['minQuantity' => 51, 'price' => 80.0],
        ];

        $result = $this->service->getQuantityTier(100.0, $tiers, 25);

        $this->assertSame(90.0, $result['price']);
    }

    #[Test]
    public function pricing_result_has_discount(): void
    {
        $result = new PricingResult(
            productId: 'prod-1',
            customerId: 'cust-1',
            listPrice: 100.0,
            discounts: ['volume' => 5.0],
            totalDiscountPercent: 5.0,
            discountAmount: 5.0,
            finalPrice: 95.0,
            currencyCode: 'MYR',
            quantity: 10,
            lineTotal: 950.0
        );

        $this->assertTrue($result->hasDiscount());
        $this->assertSame(5.0, $result->savingsPercent());
    }
}
