<?php

declare(strict_types=1);

namespace Nexus\Tax\Tests\Unit\Enums;

use Nexus\Tax\Enums\TaxCalculationMethod;
use PHPUnit\Framework\TestCase;

final class TaxCalculationMethodTest extends TestCase
{
    public function test_it_has_expected_cases(): void
    {
        $this->assertSame('standard', TaxCalculationMethod::Standard->value);
        $this->assertSame('reverse_charge', TaxCalculationMethod::ReverseCharge->value);
        $this->assertSame('inclusive', TaxCalculationMethod::Inclusive->value);
        $this->assertSame('exclusive', TaxCalculationMethod::Exclusive->value);
    }

    public function test_label_returns_human_readable_name(): void
    {
        $this->assertSame('Standard (Tax Added)', TaxCalculationMethod::Standard->label());
        $this->assertSame('Reverse Charge (Buyer Self-Assesses)', TaxCalculationMethod::ReverseCharge->label());
        $this->assertSame('Tax Inclusive (Tax in Price)', TaxCalculationMethod::Inclusive->label());
        $this->assertSame('Tax Exclusive (Tax Added to Price)', TaxCalculationMethod::Exclusive->label());
    }

    public function test_collects_tax_returns_true_for_standard_inclusive_exclusive(): void
    {
        $this->assertTrue(TaxCalculationMethod::Standard->collectsTax());
        $this->assertTrue(TaxCalculationMethod::Inclusive->collectsTax());
        $this->assertTrue(TaxCalculationMethod::Exclusive->collectsTax());
    }

    public function test_collects_tax_returns_false_for_reverse_charge(): void
    {
        $this->assertFalse(TaxCalculationMethod::ReverseCharge->collectsTax());
    }

    public function test_tax_in_price_logic(): void
    {
        $this->assertTrue(TaxCalculationMethod::Inclusive->isTaxInPrice());
        
        $this->assertFalse(TaxCalculationMethod::Standard->isTaxInPrice());
        $this->assertFalse(TaxCalculationMethod::ReverseCharge->isTaxInPrice());
        $this->assertFalse(TaxCalculationMethod::Exclusive->isTaxInPrice());
    }
}
