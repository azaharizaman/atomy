<?php

declare(strict_types=1);

namespace Nexus\Tax\Tests\Unit\Enums;

use Nexus\Tax\Enums\ServiceClassification;
use PHPUnit\Framework\TestCase;

final class ServiceClassificationTest extends TestCase
{
    public function test_it_has_expected_cases(): void
    {
        $this->assertSame('digital_service', ServiceClassification::DigitalService->value);
        $this->assertSame('telecom_service', ServiceClassification::TelecomService->value);
        $this->assertSame('consulting_service', ServiceClassification::ConsultingService->value);
        $this->assertSame('physical_goods', ServiceClassification::PhysicalGoods->value);
        $this->assertSame('other', ServiceClassification::Other->value);
    }

    public function test_label_returns_human_readable_name(): void
    {
        $this->assertSame('Digital Service', ServiceClassification::DigitalService->label());
        $this->assertSame('Telecommunications Service', ServiceClassification::TelecomService->label());
        $this->assertSame('Consulting/Professional Service', ServiceClassification::ConsultingService->label());
        $this->assertSame('Physical Goods', ServiceClassification::PhysicalGoods->label());
        $this->assertSame('Other/Unclassified', ServiceClassification::Other->label());
    }

    public function test_requires_place_of_supply_logic(): void
    {
        // Digital and telecom require place-of-supply logic for cross-border EU VAT
        $this->assertTrue(ServiceClassification::DigitalService->requiresPlaceOfSupplyLogic());
        $this->assertTrue(ServiceClassification::TelecomService->requiresPlaceOfSupplyLogic());

        // Physical goods and consulting have simpler rules
        $this->assertFalse(ServiceClassification::PhysicalGoods->requiresPlaceOfSupplyLogic());
        $this->assertFalse(ServiceClassification::ConsultingService->requiresPlaceOfSupplyLogic());
        $this->assertFalse(ServiceClassification::Other->requiresPlaceOfSupplyLogic());
    }
}
