<?php

declare(strict_types=1);

namespace Nexus\Tax\Tests\Unit\ValueObjects;

use Nexus\Tax\Exceptions\InvalidTaxContextException;
use Nexus\Tax\ValueObjects\ExemptionCertificate;
use Nexus\Tax\ValueObjects\TaxContext;
use PHPUnit\Framework\TestCase;

final class TaxContextTest extends TestCase
{
    public function test_it_can_be_created_with_valid_data(): void
    {
        $context = new TaxContext(
            transactionId: 'TXN-001',
            transactionDate: new \DateTimeImmutable('2024-01-15'),
            taxCode: 'US-CA-SALES',
            taxType: 'sales_tax',
            customerId: 'CUST-001',
            billingAddress: ['country' => 'US', 'state' => 'CA', 'city' => 'San Francisco'],
            shippingAddress: ['country' => 'US', 'state' => 'CA', 'city' => 'San Francisco'],
        );

        $this->assertSame('TXN-001', $context->transactionId);
        $this->assertSame('US-CA-SALES', $context->taxCode);
        $this->assertSame('CUST-001', $context->customerId);
    }

    public function test_it_validates_country_code_is_required(): void
    {
        $this->expectException(InvalidTaxContextException::class);
        $this->expectExceptionMessage('billingAddress must contain country');

        new TaxContext(
            transactionId: 'TXN-001',
            transactionDate: new \DateTimeImmutable('2024-01-15'),
            taxCode: 'US-CA-SALES',
            taxType: 'sales_tax',
            customerId: 'CUST-001',
            billingAddress: ['state' => 'CA'], // Missing country
            shippingAddress: ['country' => 'US'],
        );
    }

    public function test_it_validates_country_code_format(): void
    {
        $this->expectException(InvalidTaxContextException::class);
        $this->expectExceptionMessage('billingAddress country must be 2-letter ISO code');

        new TaxContext(
            transactionId: 'TXN-001',
            transactionDate: new \DateTimeImmutable('2024-01-15'),
            taxCode: 'US-CA-SALES',
            taxType: 'sales_tax',
            customerId: 'CUST-001',
            billingAddress: ['country' => 'USA'], // Should be 'US'
            shippingAddress: ['country' => 'US'],
        );
    }

    public function test_it_validates_exemption_customer_match(): void
    {
        $certificate = new ExemptionCertificate(
            certificateId: 'CERT-001',
            customerId: 'CUST-002', // Different customer
            reason: 'resale',
            exemptionPercentage: 100.0,
            issueDate: new \DateTimeImmutable('2024-01-01'),
            expirationDate: new \DateTimeImmutable('2025-01-01'),
        );

        $this->expectException(InvalidTaxContextException::class);
        $this->expectExceptionMessage('Certificate customer CUST-002 does not match transaction customer CUST-001');

        new TaxContext(
            transactionId: 'TXN-001',
            transactionDate: new \DateTimeImmutable('2024-01-15'),
            taxCode: 'US-CA-SALES',
            taxType: 'sales_tax',
            customerId: 'CUST-001',
            billingAddress: ['country' => 'US'],
            shippingAddress: ['country' => 'US'],
            exemptionCertificate: $certificate,
        );
    }

    public function test_has_exemption_returns_true_when_certificate_present(): void
    {
        $certificate = new ExemptionCertificate(
            certificateId: 'CERT-001',
            customerId: 'CUST-001',
            reason: 'resale',
            exemptionPercentage: 100.0,
            issueDate: new \DateTimeImmutable('2024-01-01'),
            expirationDate: new \DateTimeImmutable('2025-01-01'),
        );

        $context = new TaxContext(
            transactionId: 'TXN-001',
            transactionDate: new \DateTimeImmutable('2024-01-15'),
            taxCode: 'US-CA-SALES',
            taxType: 'sales_tax',
            customerId: 'CUST-001',
            billingAddress: ['country' => 'US'],
            shippingAddress: ['country' => 'US'],
            exemptionCertificate: $certificate,
        );

        $this->assertTrue($context->hasExemption());
    }

    public function test_has_exemption_returns_false_when_no_certificate(): void
    {
        $context = new TaxContext(
            transactionId: 'TXN-001',
            transactionDate: new \DateTimeImmutable('2024-01-15'),
            taxCode: 'US-CA-SALES',
            taxType: 'sales_tax',
            customerId: 'CUST-001',
            billingAddress: ['country' => 'US'],
            shippingAddress: ['country' => 'US'],
        );

        $this->assertFalse($context->hasExemption());
    }

    public function test_is_reverse_charge_returns_true_for_reverse_charge_method(): void
    {
        $context = new TaxContext(
            transactionId: 'TXN-001',
            transactionDate: new \DateTimeImmutable('2024-01-15'),
            taxCode: 'EU-VAT',
            taxType: 'vat',
            customerId: 'CUST-001',
            billingAddress: ['country' => 'DE'],
            shippingAddress: ['country' => 'FR'],
            calculationMethod: 'reverse_charge',
        );

        $this->assertTrue($context->isReverseCharge());
    }

    public function test_is_cross_border_returns_true_when_billing_shipping_countries_differ(): void
    {
        $context = new TaxContext(
            transactionId: 'TXN-001',
            transactionDate: new \DateTimeImmutable('2024-01-15'),
            taxCode: 'EU-VAT',
            taxType: 'vat',
            customerId: 'CUST-001',
            billingAddress: ['country' => 'DE'],
            shippingAddress: ['country' => 'FR'],
        );

        $this->assertTrue($context->isCrossBorder());
    }

    public function test_is_cross_border_returns_false_when_same_country(): void
    {
        $context = new TaxContext(
            transactionId: 'TXN-001',
            transactionDate: new \DateTimeImmutable('2024-01-15'),
            taxCode: 'US-CA-SALES',
            taxType: 'sales_tax',
            customerId: 'CUST-001',
            billingAddress: ['country' => 'US', 'state' => 'CA'],
            shippingAddress: ['country' => 'US', 'state' => 'NY'],
        );

        $this->assertFalse($context->isCrossBorder());
    }

    public function test_get_effective_tax_percentage_returns_reduced_percentage_with_exemption(): void
    {
        $certificate = new ExemptionCertificate(
            certificateId: 'CERT-001',
            customerId: 'CUST-001',
            reason: 'agricultural',
            exemptionPercentage: 50.0, // 50% exempt
            issueDate: new \DateTimeImmutable('2024-01-01'),
            expirationDate: new \DateTimeImmutable('2025-01-01'),
        );

        $context = new TaxContext(
            transactionId: 'TXN-001',
            transactionDate: new \DateTimeImmutable('2024-01-15'),
            taxCode: 'US-CA-SALES',
            taxType: 'sales_tax',
            customerId: 'CUST-001',
            billingAddress: ['country' => 'US'],
            shippingAddress: ['country' => 'US'],
            exemptionCertificate: $certificate,
        );

        $effectiveRate = $context->getEffectiveTaxPercentage(10.0); // 10% base rate
        $this->assertSame(5.0, $effectiveRate); // 50% of 10% = 5%
    }

    public function test_with_calculation_method_creates_new_instance(): void
    {
        $original = new TaxContext(
            transactionId: 'TXN-001',
            transactionDate: new \DateTimeImmutable('2024-01-15'),
            taxCode: 'US-CA-SALES',
            taxType: 'sales_tax',
            customerId: 'CUST-001',
            billingAddress: ['country' => 'US'],
            shippingAddress: ['country' => 'US'],
        );

        $modified = $original->withCalculationMethod('inclusive');

        $this->assertNotSame($original, $modified);
        $this->assertNull($original->calculationMethod);
        $this->assertSame('inclusive', $modified->calculationMethod);
    }

    public function test_to_array_returns_complete_data(): void
    {
        $context = new TaxContext(
            transactionId: 'TXN-001',
            transactionDate: new \DateTimeImmutable('2024-01-15'),
            taxCode: 'US-CA-SALES',
            taxType: 'sales_tax',
            customerId: 'CUST-001',
            billingAddress: ['country' => 'US', 'state' => 'CA'],
            shippingAddress: ['country' => 'US', 'state' => 'CA'],
        );

        $array = $context->toArray();

        $this->assertArrayHasKey('transaction_id', $array);
        $this->assertArrayHasKey('transaction_date', $array);
        $this->assertArrayHasKey('tax_code', $array);
        $this->assertArrayHasKey('customer_id', $array);
        $this->assertArrayHasKey('billing_address', $array);
        $this->assertSame('TXN-001', $array['transaction_id']);
        $this->assertSame('2024-01-15', $array['transaction_date']);
    }
}
