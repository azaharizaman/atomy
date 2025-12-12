<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Tests\Unit\DTOs\Tax;

use Nexus\Common\ValueObjects\Money;
use Nexus\ProcurementOperations\DTOs\Tax\TaxLineItem;
use Nexus\ProcurementOperations\DTOs\Tax\TaxValidationRequest;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(TaxValidationRequest::class)]
final class TaxValidationRequestTest extends TestCase
{
    #[Test]
    public function forDomesticPurchase_creates_domestic_request(): void
    {
        $lineItems = [
            TaxLineItem::standard(
                lineNumber: 1,
                description: 'Office supplies',
                netAmount: Money::of(100, 'MYR'),
                taxCode: 'STD',
                taxRate: 6.0,
                taxAmount: Money::of(6, 'MYR'),
            ),
        ];

        $request = TaxValidationRequest::forDomesticPurchase(
            tenantId: 'tenant-123',
            invoiceId: 'inv-001',
            vendorId: 'vendor-001',
            invoiceDate: new \DateTimeImmutable('2024-01-15'),
            lineItems: $lineItems,
            currency: 'MYR',
        );

        $this->assertFalse($request->isCrossBorder);
        $this->assertEquals('tenant-123', $request->tenantId);
        $this->assertEquals('inv-001', $request->invoiceId);
        $this->assertCount(1, $request->lineItems);
        $this->assertEquals('MYR', $request->currency);
    }

    #[Test]
    public function forCrossBorderPurchase_creates_cross_border_request(): void
    {
        $lineItems = [
            TaxLineItem::reverseCharge(
                lineNumber: 1,
                description: 'Software license',
                netAmount: Money::of(1000, 'USD'),
                taxCode: 'RC',
                taxRate: 6.0,
                taxAmount: Money::of(60, 'USD'),
            ),
        ];

        $request = TaxValidationRequest::forCrossBorderPurchase(
            tenantId: 'tenant-123',
            invoiceId: 'inv-001',
            vendorId: 'vendor-001',
            invoiceDate: new \DateTimeImmutable('2024-01-15'),
            lineItems: $lineItems,
            currency: 'USD',
            vendorCountry: 'US',
            buyerCountry: 'MY',
        );

        $this->assertTrue($request->isCrossBorder);
        $this->assertEquals('US', $request->vendorCountry);
        $this->assertEquals('MY', $request->buyerCountry);
    }

    #[Test]
    public function getTotalNetAmount_sums_line_items(): void
    {
        $lineItems = [
            TaxLineItem::standard(
                lineNumber: 1,
                description: 'Item 1',
                netAmount: Money::of(100, 'MYR'),
                taxCode: 'STD',
                taxRate: 6.0,
                taxAmount: Money::of(6, 'MYR'),
            ),
            TaxLineItem::standard(
                lineNumber: 2,
                description: 'Item 2',
                netAmount: Money::of(200, 'MYR'),
                taxCode: 'STD',
                taxRate: 6.0,
                taxAmount: Money::of(12, 'MYR'),
            ),
        ];

        $request = TaxValidationRequest::forDomesticPurchase(
            tenantId: 'tenant-123',
            invoiceId: 'inv-001',
            vendorId: 'vendor-001',
            invoiceDate: new \DateTimeImmutable('2024-01-15'),
            lineItems: $lineItems,
            currency: 'MYR',
        );

        $totalNet = $request->getTotalNetAmount();

        $this->assertEquals(300_00, $totalNet->getAmountInCents());
    }

    #[Test]
    public function getTotalTaxAmount_sums_tax_amounts(): void
    {
        $lineItems = [
            TaxLineItem::standard(
                lineNumber: 1,
                description: 'Item 1',
                netAmount: Money::of(100, 'MYR'),
                taxCode: 'STD',
                taxRate: 6.0,
                taxAmount: Money::of(6, 'MYR'),
            ),
            TaxLineItem::standard(
                lineNumber: 2,
                description: 'Item 2',
                netAmount: Money::of(200, 'MYR'),
                taxCode: 'STD',
                taxRate: 6.0,
                taxAmount: Money::of(12, 'MYR'),
            ),
        ];

        $request = TaxValidationRequest::forDomesticPurchase(
            tenantId: 'tenant-123',
            invoiceId: 'inv-001',
            vendorId: 'vendor-001',
            invoiceDate: new \DateTimeImmutable('2024-01-15'),
            lineItems: $lineItems,
            currency: 'MYR',
        );

        $totalTax = $request->getTotalTaxAmount();

        $this->assertEquals(18_00, $totalTax->getAmountInCents());
    }

    #[Test]
    public function getGrossAmount_returns_net_plus_tax(): void
    {
        $lineItems = [
            TaxLineItem::standard(
                lineNumber: 1,
                description: 'Item 1',
                netAmount: Money::of(100, 'MYR'),
                taxCode: 'STD',
                taxRate: 6.0,
                taxAmount: Money::of(6, 'MYR'),
            ),
        ];

        $request = TaxValidationRequest::forDomesticPurchase(
            tenantId: 'tenant-123',
            invoiceId: 'inv-001',
            vendorId: 'vendor-001',
            invoiceDate: new \DateTimeImmutable('2024-01-15'),
            lineItems: $lineItems,
            currency: 'MYR',
        );

        $gross = $request->getGrossAmount();

        $this->assertEquals(106_00, $gross->getAmountInCents());
    }

    #[Test]
    public function hasExemptItems_returns_true_when_exempt_exists(): void
    {
        $lineItems = [
            TaxLineItem::standard(
                lineNumber: 1,
                description: 'Taxable item',
                netAmount: Money::of(100, 'MYR'),
                taxCode: 'STD',
                taxRate: 6.0,
                taxAmount: Money::of(6, 'MYR'),
            ),
            TaxLineItem::exempt(
                lineNumber: 2,
                description: 'Exempt item',
                netAmount: Money::of(50, 'MYR'),
                exemptionCode: 'EXEMPT',
                exemptionReason: 'Medical supplies',
            ),
        ];

        $request = TaxValidationRequest::forDomesticPurchase(
            tenantId: 'tenant-123',
            invoiceId: 'inv-001',
            vendorId: 'vendor-001',
            invoiceDate: new \DateTimeImmutable('2024-01-15'),
            lineItems: $lineItems,
            currency: 'MYR',
        );

        $this->assertTrue($request->hasExemptItems());
    }

    #[Test]
    public function hasReverseChargeItems_returns_true_when_rc_exists(): void
    {
        $lineItems = [
            TaxLineItem::reverseCharge(
                lineNumber: 1,
                description: 'Software license',
                netAmount: Money::of(1000, 'USD'),
                taxCode: 'RC',
                taxRate: 6.0,
                taxAmount: Money::of(60, 'USD'),
            ),
        ];

        $request = TaxValidationRequest::forCrossBorderPurchase(
            tenantId: 'tenant-123',
            invoiceId: 'inv-001',
            vendorId: 'vendor-001',
            invoiceDate: new \DateTimeImmutable('2024-01-15'),
            lineItems: $lineItems,
            currency: 'USD',
            vendorCountry: 'US',
            buyerCountry: 'MY',
        );

        $this->assertTrue($request->hasReverseChargeItems());
    }
}
