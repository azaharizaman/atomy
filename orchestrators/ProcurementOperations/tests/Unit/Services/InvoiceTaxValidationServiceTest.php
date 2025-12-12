<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Tests\Unit\Services;

use Nexus\Common\ValueObjects\Money;
use Nexus\ProcurementOperations\DTOs\Tax\TaxLineItem;
use Nexus\ProcurementOperations\DTOs\Tax\TaxValidationRequest;
use Nexus\ProcurementOperations\DTOs\Tax\TaxValidationResult;
use Nexus\ProcurementOperations\DTOs\Tax\WithholdingTaxCalculation;
use Nexus\ProcurementOperations\Services\InvoiceTaxValidationService;
use Nexus\ProcurementOperations\Services\TaxRateProviderInterface;
use Nexus\ProcurementOperations\Services\VendorTaxDataProviderInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

#[CoversClass(InvoiceTaxValidationService::class)]
final class InvoiceTaxValidationServiceTest extends TestCase
{
    private InvoiceTaxValidationService $service;
    private MockObject&TaxRateProviderInterface $taxRateProvider;
    private MockObject&VendorTaxDataProviderInterface $vendorDataProvider;

    protected function setUp(): void
    {
        $this->taxRateProvider = $this->createMock(TaxRateProviderInterface::class);
        $this->vendorDataProvider = $this->createMock(VendorTaxDataProviderInterface::class);

        $this->service = new InvoiceTaxValidationService(
            taxRateProvider: $this->taxRateProvider,
            vendorDataProvider: $this->vendorDataProvider,
            logger: new NullLogger(),
        );
    }

    #[Test]
    public function validateInvoiceTax_returns_valid_for_correct_tax(): void
    {
        $tenantId = 'tenant-123';
        $vendorId = 'vendor-001';

        $this->taxRateProvider
            ->method('getRate')
            ->willReturn(10.0); // 10% tax rate

        $this->vendorDataProvider
            ->method('getTaxRegistrationNumber')
            ->willReturn('TAX123456');

        $request = TaxValidationRequest::forDomesticPurchase(
            tenantId: $tenantId,
            invoiceId: 'inv-001',
            vendorId: $vendorId,
            invoiceDate: new \DateTimeImmutable(),
            lineItems: [
                TaxLineItem::standard(
                    lineNumber: 1,
                    description: 'Office supplies',
                    netAmount: Money::of(100, 'USD'),
                    taxCode: 'STD',
                    taxRate: 10.0,
                    taxAmount: Money::of(10, 'USD'),
                ),
            ],
            currency: 'USD',
        );

        $result = $this->service->validateInvoiceTax($request);

        $this->assertInstanceOf(TaxValidationResult::class, $result);
        $this->assertTrue($result->isValid);
    }

    #[Test]
    public function validateInvoiceTax_returns_invalid_for_wrong_tax_rate(): void
    {
        $tenantId = 'tenant-123';
        $vendorId = 'vendor-001';

        $this->taxRateProvider
            ->method('getRate')
            ->willReturn(10.0); // Expected 10%

        $this->vendorDataProvider
            ->method('getTaxRegistrationNumber')
            ->willReturn('TAX123456');

        $request = TaxValidationRequest::forDomesticPurchase(
            tenantId: $tenantId,
            invoiceId: 'inv-001',
            vendorId: $vendorId,
            invoiceDate: new \DateTimeImmutable(),
            lineItems: [
                TaxLineItem::standard(
                    lineNumber: 1,
                    description: 'Office supplies',
                    netAmount: Money::of(100, 'USD'),
                    taxCode: 'STD',
                    taxRate: 15.0, // Wrong rate
                    taxAmount: Money::of(15, 'USD'),
                ),
            ],
            currency: 'USD',
        );

        $result = $this->service->validateInvoiceTax($request);

        $this->assertFalse($result->isValid);
        $this->assertNotEmpty($result->errors);
    }

    #[Test]
    public function validateInvoiceTax_returns_invalid_for_calculation_mismatch(): void
    {
        $tenantId = 'tenant-123';
        $vendorId = 'vendor-001';

        $this->taxRateProvider
            ->method('getRate')
            ->willReturn(10.0);

        $this->vendorDataProvider
            ->method('getTaxRegistrationNumber')
            ->willReturn('TAX123456');

        $request = TaxValidationRequest::forDomesticPurchase(
            tenantId: $tenantId,
            invoiceId: 'inv-001',
            vendorId: $vendorId,
            invoiceDate: new \DateTimeImmutable(),
            lineItems: [
                TaxLineItem::standard(
                    lineNumber: 1,
                    description: 'Office supplies',
                    netAmount: Money::of(100, 'USD'),
                    taxCode: 'STD',
                    taxRate: 10.0,
                    taxAmount: Money::of(12, 'USD'), // Wrong calculation (should be 10)
                ),
            ],
            currency: 'USD',
        );

        $result = $this->service->validateInvoiceTax($request);

        $this->assertFalse($result->isValid);
    }

    #[Test]
    public function calculateWithholdingTax_returns_no_withholding_for_domestic(): void
    {
        $tenantId = 'tenant-123';
        $vendorId = 'vendor-001';

        $this->vendorDataProvider
            ->method('getVendorCountry')
            ->willReturn('MY');

        $this->vendorDataProvider
            ->method('isSubjectToWithholding')
            ->willReturn(false);

        $result = $this->service->calculateWithholdingTax(
            tenantId: $tenantId,
            vendorId: $vendorId,
            amount: Money::of(1000, 'MYR'),
            paymentType: 'goods',
        );

        $this->assertInstanceOf(WithholdingTaxCalculation::class, $result);
        $this->assertFalse($result->hasWithholding);
    }

    #[Test]
    public function calculateWithholdingTax_applies_withholding_for_services(): void
    {
        $tenantId = 'tenant-123';
        $vendorId = 'vendor-001';

        $this->vendorDataProvider
            ->method('getVendorCountry')
            ->willReturn('SG'); // Foreign vendor

        $this->vendorDataProvider
            ->method('isSubjectToWithholding')
            ->willReturn(true);

        $this->vendorDataProvider
            ->method('getWithholdingRate')
            ->willReturn(15.0);

        $this->vendorDataProvider
            ->method('hasTreatyBenefit')
            ->willReturn(false);

        $result = $this->service->calculateWithholdingTax(
            tenantId: $tenantId,
            vendorId: $vendorId,
            amount: Money::of(10000, 'MYR'),
            paymentType: 'service_fee',
        );

        $this->assertTrue($result->hasWithholding);
        $this->assertEquals(15.0, $result->effectiveRate);
        // 15% of 10000 = 1500
        $this->assertEquals(150000, $result->withholdingAmount->getAmountInCents());
    }

    #[Test]
    public function calculateWithholdingTax_applies_treaty_rate(): void
    {
        $tenantId = 'tenant-123';
        $vendorId = 'vendor-001';

        $this->vendorDataProvider
            ->method('getVendorCountry')
            ->willReturn('SG');

        $this->vendorDataProvider
            ->method('isSubjectToWithholding')
            ->willReturn(true);

        $this->vendorDataProvider
            ->method('getWithholdingRate')
            ->willReturn(15.0); // Standard rate

        $this->vendorDataProvider
            ->method('hasTreatyBenefit')
            ->willReturn(true);

        $this->vendorDataProvider
            ->method('getTreatyRate')
            ->willReturn(10.0); // Reduced treaty rate

        $result = $this->service->calculateWithholdingTax(
            tenantId: $tenantId,
            vendorId: $vendorId,
            amount: Money::of(10000, 'MYR'),
            paymentType: 'royalty',
        );

        $this->assertTrue($result->hasWithholding);
        $this->assertTrue($result->isTreatyRate);
        $this->assertEquals(10.0, $result->effectiveRate);
    }

    #[Test]
    public function validateTaxRegistration_returns_valid_for_registered_vendor(): void
    {
        $tenantId = 'tenant-123';
        $vendorId = 'vendor-001';

        $this->vendorDataProvider
            ->method('getTaxRegistrationNumber')
            ->willReturn('TAX123456789');

        $this->vendorDataProvider
            ->method('isTaxRegistrationValid')
            ->willReturn(true);

        $result = $this->service->validateTaxRegistration($tenantId, $vendorId);

        $this->assertTrue($result['valid']);
    }

    #[Test]
    public function validateTaxRegistration_returns_invalid_for_unregistered(): void
    {
        $tenantId = 'tenant-123';
        $vendorId = 'vendor-001';

        $this->vendorDataProvider
            ->method('getTaxRegistrationNumber')
            ->willReturn(null);

        $result = $this->service->validateTaxRegistration($tenantId, $vendorId);

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('missing', strtolower($result['reason']));
    }

    #[Test]
    public function isReverseChargeApplicable_returns_true_for_foreign_b2b(): void
    {
        $tenantId = 'tenant-123';
        $vendorId = 'vendor-001';

        $this->vendorDataProvider
            ->method('getVendorCountry')
            ->willReturn('SG'); // Foreign

        $this->vendorDataProvider
            ->method('isB2BTransaction')
            ->willReturn(true);

        $this->vendorDataProvider
            ->method('isTaxRegistered')
            ->willReturn(true);

        $result = $this->service->isReverseChargeApplicable($tenantId, $vendorId);

        $this->assertTrue($result);
    }

    #[Test]
    public function isReverseChargeApplicable_returns_false_for_domestic(): void
    {
        $tenantId = 'tenant-123';
        $vendorId = 'vendor-001';

        $this->vendorDataProvider
            ->method('getVendorCountry')
            ->willReturn('MY'); // Domestic

        $result = $this->service->isReverseChargeApplicable($tenantId, $vendorId);

        $this->assertFalse($result);
    }

    #[Test]
    #[DataProvider('exemptServiceProvider')]
    public function validateExemption_validates_exempt_items(
        string $exemptionType,
        bool $hasValidCertificate,
        bool $expectedValid,
    ): void {
        $tenantId = 'tenant-123';
        $vendorId = 'vendor-001';

        $this->vendorDataProvider
            ->method('hasExemptionCertificate')
            ->willReturn($hasValidCertificate);

        $this->vendorDataProvider
            ->method('isExemptionValid')
            ->willReturn($hasValidCertificate);

        $lineItem = TaxLineItem::exempt(
            lineNumber: 1,
            description: 'Exempt item',
            netAmount: Money::of(100, 'USD'),
            exemptionCode: $exemptionType,
            exemptionReason: 'Tax exempt supply',
        );

        $result = $this->service->validateExemption($tenantId, $vendorId, $lineItem);

        $this->assertEquals($expectedValid, $result['valid']);
    }

    /**
     * @return iterable<array{string, bool, bool}>
     */
    public static function exemptServiceProvider(): iterable
    {
        yield 'valid exemption with certificate' => ['EXEMPT', true, true];
        yield 'invalid exemption without certificate' => ['EXEMPT', false, false];
        yield 'zero-rated with certificate' => ['ZERO', true, true];
    }
}
