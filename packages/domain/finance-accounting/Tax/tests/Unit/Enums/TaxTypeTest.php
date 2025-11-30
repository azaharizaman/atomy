<?php

declare(strict_types=1);

namespace Nexus\Tax\Tests\Unit\Enums;

use Nexus\Tax\Enums\TaxType;
use PHPUnit\Framework\TestCase;

final class TaxTypeTest extends TestCase
{
    public function test_it_has_expected_cases(): void
    {
        $this->assertSame('vat', TaxType::VAT->value);
        $this->assertSame('gst', TaxType::GST->value);
        $this->assertSame('sst', TaxType::SST->value);
        $this->assertSame('sales_tax', TaxType::SalesTax->value);
        $this->assertSame('excise', TaxType::Excise->value);
        $this->assertSame('withholding', TaxType::Withholding->value);
    }

    public function test_label_returns_human_readable_name(): void
    {
        $this->assertSame('Value Added Tax (VAT)', TaxType::VAT->label());
        $this->assertSame('Goods & Services Tax (GST)', TaxType::GST->label());
        $this->assertSame('Sales & Service Tax (SST)', TaxType::SST->label());
        $this->assertSame('Sales Tax', TaxType::SalesTax->label());
        $this->assertSame('Excise Duty', TaxType::Excise->label());
        $this->assertSame('Withholding Tax', TaxType::Withholding->label());
    }

    public function test_is_invoice_based_returns_true_for_vat_gst_sst(): void
    {
        $this->assertTrue(TaxType::VAT->isInvoiceBased());
        $this->assertTrue(TaxType::GST->isInvoiceBased());
        $this->assertTrue(TaxType::SST->isInvoiceBased());
    }

    public function test_is_invoice_based_returns_false_for_others(): void
    {
        $this->assertFalse(TaxType::SalesTax->isInvoiceBased());
        $this->assertFalse(TaxType::Excise->isInvoiceBased());
        $this->assertFalse(TaxType::Withholding->isInvoiceBased());
    }

    public function test_supports_reverse_charge_returns_true_for_vat_gst(): void
    {
        $this->assertTrue(TaxType::VAT->supportsReverseCharge());
        $this->assertTrue(TaxType::GST->supportsReverseCharge());
    }

    public function test_supports_reverse_charge_returns_false_for_others(): void
    {
        $this->assertFalse(TaxType::SST->supportsReverseCharge());
        $this->assertFalse(TaxType::SalesTax->supportsReverseCharge());
        $this->assertFalse(TaxType::Excise->supportsReverseCharge());
        $this->assertFalse(TaxType::Withholding->supportsReverseCharge());
    }
}
