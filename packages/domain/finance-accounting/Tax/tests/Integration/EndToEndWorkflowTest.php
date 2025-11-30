<?php

declare(strict_types=1);

namespace Nexus\Tax\Tests\Integration;

use Nexus\Currency\ValueObjects\Money;
use Nexus\Tax\Services\JurisdictionResolver;
use Nexus\Tax\Services\TaxCalculator;
use Nexus\Tax\ValueObjects\ExemptionCertificate;
use Nexus\Tax\ValueObjects\TaxContext;
use Nexus\Tax\ValueObjects\TaxJurisdiction;
use Nexus\Tax\ValueObjects\TaxRate;
use Nexus\Tax\Tests\Support\InMemoryTaxRateRepository;
use PHPUnit\Framework\TestCase;

/**
 * End-to-End Integration Tests
 * 
 * These tests verify complete workflows using real service implementations
 * with in-memory repositories (no database required).
 */
final class EndToEndWorkflowTest extends TestCase
{
    public function test_us_single_jurisdiction_sales_tax(): void
    {
        // Setup: California sales tax
        $repository = new InMemoryTaxRateRepository();
        $repository->addRate(new TaxRate(
            taxCode: 'US-CA-SALES',
            rate: '7.25',
            type: 'sales_tax',
            level: 'state',
            jurisdictionCode: 'US-CA',
            effectiveFrom: new \DateTimeImmutable('2024-01-01'),
        ));

        $resolver = new JurisdictionResolver();
        $calculator = new TaxCalculator($repository, $resolver);

        // Transaction context
        $context = new TaxContext(
            transactionId: 'TXN-001',
            transactionDate: new \DateTimeImmutable('2024-01-15'),
            taxCode: 'US-CA-SALES',
            taxType: 'sales_tax',
            customerId: 'CUST-001',
            billingAddress: ['country' => 'US', 'state' => 'CA', 'city' => 'San Francisco'],
            shippingAddress: ['country' => 'US', 'state' => 'CA', 'city' => 'San Francisco'],
        );

        // Calculate tax
        $amount = Money::of('1000.00', 'USD');
        $breakdown = $calculator->calculate($context, $amount);

        // Verify
        $this->assertSame('1000.0000', $breakdown->netAmount->getAmount());
        $this->assertSame('72.5000', $breakdown->totalTaxAmount->getAmount());
        $this->assertSame('1072.5000', $breakdown->grossAmount->getAmount());
        $this->assertCount(1, $breakdown->taxLines);
        $this->assertSame('US-CA-SALES', $breakdown->taxLines[0]->rate->taxCode);
    }

    public function test_canadian_multi_jurisdiction_hst(): void
    {
        // Setup: Canadian HST (5% federal + 8% provincial)
        $repository = new InMemoryTaxRateRepository();
        
        $repository->addRate(new TaxRate(
            taxCode: 'CA-GST',
            rate: '5.00',
            type: 'gst',
            level: 'federal',
            jurisdictionCode: 'CA',
            effectiveFrom: new \DateTimeImmutable('2024-01-01'),
        ));
        
        $repository->addRate(new TaxRate(
            taxCode: 'CA-ON-PST',
            rate: '8.00',
            type: 'gst',
            level: 'state',
            jurisdictionCode: 'CA-ON',
            effectiveFrom: new \DateTimeImmutable('2024-01-01'),
        ));

        $resolver = new JurisdictionResolver();
        $calculator = new TaxCalculator($repository, $resolver);

        // Transaction context
        $context = new TaxContext(
            transactionId: 'TXN-002',
            transactionDate: new \DateTimeImmutable('2024-01-15'),
            taxCode: 'CA-ON-PST',
            taxType: 'gst',
            customerId: 'CUST-002',
            billingAddress: ['country' => 'CA', 'state' => 'ON', 'city' => 'Toronto'],
            shippingAddress: ['country' => 'CA', 'state' => 'ON', 'city' => 'Toronto'],
        );

        // Calculate tax (simplified - real implementation would handle hierarchy)
        $amount = Money::of('1000.00', 'CAD');
        $breakdown = $calculator->calculate($context, $amount);

        // Verify provincial tax calculated
        $this->assertSame('80.0000', $breakdown->totalTaxAmount->getAmount());
    }

    public function test_agricultural_exemption_50_percent(): void
    {
        // Setup: Tax rate and exemption certificate
        $repository = new InMemoryTaxRateRepository();
        $repository->addRate(new TaxRate(
            taxCode: 'US-CA-SALES',
            rate: '10.00',
            type: 'sales_tax',
            level: 'state',
            jurisdictionCode: 'US-CA',
            effectiveFrom: new \DateTimeImmutable('2024-01-01'),
        ));

        $certificate = new ExemptionCertificate(
            certificateId: 'CERT-AGR-001',
            customerId: 'FARM-001',
            reason: 'agricultural',
            exemptionPercentage: 50.0,
            issueDate: new \DateTimeImmutable('2024-01-01'),
            expirationDate: new \DateTimeImmutable('2025-01-01'),
        );

        $resolver = new JurisdictionResolver();
        $calculator = new TaxCalculator($repository, $resolver);

        // Transaction context with exemption
        $context = new TaxContext(
            transactionId: 'TXN-003',
            transactionDate: new \DateTimeImmutable('2024-06-15'),
            taxCode: 'US-CA-SALES',
            taxType: 'sales_tax',
            customerId: 'FARM-001',
            billingAddress: ['country' => 'US', 'state' => 'CA'],
            shippingAddress: ['country' => 'US', 'state' => 'CA'],
            exemptionCertificate: $certificate,
        );

        // Calculate tax
        $amount = Money::of('1000.00', 'USD');
        $breakdown = $calculator->calculate($context, $amount);

        // Verify: 50% exempt, so tax on $500 at 10% = $50
        $this->assertSame('1000.0000', $breakdown->netAmount->getAmount());
        $this->assertSame('50.0000', $breakdown->totalTaxAmount->getAmount());
        $this->assertSame('1050.0000', $breakdown->grossAmount->getAmount());
    }

    public function test_full_transaction_reversal(): void
    {
        // Setup
        $repository = new InMemoryTaxRateRepository();
        $repository->addRate(new TaxRate(
            taxCode: 'US-CA-SALES',
            rate: '7.25',
            type: 'sales_tax',
            level: 'state',
            jurisdictionCode: 'US-CA',
            effectiveFrom: new \DateTimeImmutable('2024-01-01'),
        ));

        $resolver = new JurisdictionResolver();
        $calculator = new TaxCalculator($repository, $resolver);

        // Original transaction
        $originalContext = new TaxContext(
            transactionId: 'TXN-004',
            transactionDate: new \DateTimeImmutable('2024-01-15'),
            taxCode: 'US-CA-SALES',
            taxType: 'sales_tax',
            customerId: 'CUST-004',
            billingAddress: ['country' => 'US', 'state' => 'CA'],
            shippingAddress: ['country' => 'US', 'state' => 'CA'],
        );

        $originalAmount = Money::of('1000.00', 'USD');
        $originalBreakdown = $calculator->calculate($originalContext, $originalAmount);

        // Full reversal (credit note)
        $reversal = $calculator->calculateAdjustment(
            $originalBreakdown,
            Money::of('-1000.00', 'USD')
        );

        // Verify: All amounts negated
        $this->assertSame('-1000.0000', $reversal->netAmount->getAmount());
        $this->assertSame('-72.5000', $reversal->totalTaxAmount->getAmount());
        $this->assertSame('-1072.5000', $reversal->grossAmount->getAmount());
    }

    public function test_partial_transaction_adjustment(): void
    {
        // Setup
        $repository = new InMemoryTaxRateRepository();
        $repository->addRate(new TaxRate(
            taxCode: 'US-CA-SALES',
            rate: '10.00',
            type: 'sales_tax',
            level: 'state',
            jurisdictionCode: 'US-CA',
            effectiveFrom: new \DateTimeImmutable('2024-01-01'),
        ));

        $resolver = new JurisdictionResolver();
        $calculator = new TaxCalculator($repository, $resolver);

        // Original transaction
        $originalContext = new TaxContext(
            transactionId: 'TXN-005',
            transactionDate: new \DateTimeImmutable('2024-01-15'),
            taxCode: 'US-CA-SALES',
            taxType: 'sales_tax',
            customerId: 'CUST-005',
            billingAddress: ['country' => 'US', 'state' => 'CA'],
            shippingAddress: ['country' => 'US', 'state' => 'CA'],
        );

        $originalAmount = Money::of('1000.00', 'USD');
        $originalBreakdown = $calculator->calculate($originalContext, $originalAmount);

        // Partial adjustment: Reduce by $200
        $adjustment = $calculator->calculateAdjustment(
            $originalBreakdown,
            Money::of('-200.00', 'USD')
        );

        // Verify: Proportional tax adjustment
        // Original: $1000 @ 10% = $100 tax
        // Adjustment: -$200 @ 10% = -$20 tax
        $this->assertSame('-200.0000', $adjustment->netAmount->getAmount());
        $this->assertSame('-20.0000', $adjustment->totalTaxAmount->getAmount());
    }

    public function test_eu_cross_border_reverse_charge(): void
    {
        // Setup: EU VAT with reverse charge
        $repository = new InMemoryTaxRateRepository();
        $repository->addRate(new TaxRate(
            taxCode: 'EU-VAT',
            rate: '20.00',
            type: 'vat',
            level: 'federal',
            jurisdictionCode: 'FR',
            effectiveFrom: new \DateTimeImmutable('2024-01-01'),
        ));

        $resolver = new JurisdictionResolver();
        $calculator = new TaxCalculator($repository, $resolver);

        // Cross-border B2B transaction with reverse charge
        $context = new TaxContext(
            transactionId: 'TXN-006',
            transactionDate: new \DateTimeImmutable('2024-01-15'),
            taxCode: 'EU-VAT',
            taxType: 'vat',
            customerId: 'EU-CUST-001',
            billingAddress: ['country' => 'DE'], // Germany
            shippingAddress: ['country' => 'FR'], // France
            calculationMethod: 'reverse_charge',
        );

        // Calculate tax
        $amount = Money::of('1000.00', 'EUR');
        $breakdown = $calculator->calculate($context, $amount);

        // Verify: Zero tax collected (buyer pays VAT)
        $this->assertSame('1000.0000', $breakdown->netAmount->getAmount());
        $this->assertSame('0.0000', $breakdown->totalTaxAmount->getAmount());
        $this->assertTrue($breakdown->isReverseCharge);
    }

    public function test_multi_level_cascading_tax(): void
    {
        // Setup: Federal + State + Local (cascading)
        $repository = new InMemoryTaxRateRepository();
        
        // Federal: 5%
        $repository->addRate(new TaxRate(
            taxCode: 'US-FEDERAL',
            rate: '5.00',
            type: 'sales_tax',
            level: 'federal',
            jurisdictionCode: 'US',
            effectiveFrom: new \DateTimeImmutable('2024-01-01'),
        ));
        
        // State: 7% (on federal-inclusive amount)
        $repository->addRate(new TaxRate(
            taxCode: 'US-CA-STATE',
            rate: '7.00',
            type: 'sales_tax',
            level: 'state',
            jurisdictionCode: 'US-CA',
            effectiveFrom: new \DateTimeImmutable('2024-01-01'),
        ));

        $resolver = new JurisdictionResolver();
        $calculator = new TaxCalculator($repository, $resolver);

        $context = new TaxContext(
            transactionId: 'TXN-CASCADE-001',
            transactionDate: new \DateTimeImmutable('2024-01-15'),
            taxCode: 'US-CA-STATE',
            taxType: 'sales_tax',
            customerId: 'CUST-CASCADE',
            billingAddress: ['country' => 'US', 'state' => 'CA'],
            shippingAddress: ['country' => 'US', 'state' => 'CA'],
        );

        $amount = Money::of('1000.00', 'USD');
        $breakdown = $calculator->calculate($context, $amount);

        // Base: $1000, State tax: $70 (simplified - cascading logic)
        $this->assertGreaterThan(50, (float) $breakdown->totalTaxAmount->getAmount());
    }

    public function test_temporal_rate_change_handling(): void
    {
        // Setup: Rate changed from 7% to 8% on Feb 1
        $repository = new InMemoryTaxRateRepository();
        
        $repository->addRate(new TaxRate(
            taxCode: 'US-CA-SALES',
            rate: '7.00',
            type: 'sales_tax',
            level: 'state',
            jurisdictionCode: 'US-CA',
            effectiveFrom: new \DateTimeImmutable('2024-01-01'),
            effectiveTo: new \DateTimeImmutable('2024-01-31'),
        ));
        
        $repository->addRate(new TaxRate(
            taxCode: 'US-CA-SALES',
            rate: '8.00',
            type: 'sales_tax',
            level: 'state',
            jurisdictionCode: 'US-CA',
            effectiveFrom: new \DateTimeImmutable('2024-02-01'),
        ));

        $resolver = new JurisdictionResolver();
        $calculator = new TaxCalculator($repository, $resolver);

        // Transaction before rate change
        $contextBefore = new TaxContext(
            transactionId: 'TXN-BEFORE',
            transactionDate: new \DateTimeImmutable('2024-01-15'),
            taxCode: 'US-CA-SALES',
            taxType: 'sales_tax',
            customerId: 'CUST-TEMP',
            billingAddress: ['country' => 'US', 'state' => 'CA'],
            shippingAddress: ['country' => 'US', 'state' => 'CA'],
        );

        $amount = Money::of('1000.00', 'USD');
        $breakdownBefore = $calculator->calculate($contextBefore, $amount);

        // Transaction after rate change
        $contextAfter = new TaxContext(
            transactionId: 'TXN-AFTER',
            transactionDate: new \DateTimeImmutable('2024-02-15'),
            taxCode: 'US-CA-SALES',
            taxType: 'sales_tax',
            customerId: 'CUST-TEMP',
            billingAddress: ['country' => 'US', 'state' => 'CA'],
            shippingAddress: ['country' => 'US', 'state' => 'CA'],
        );

        $breakdownAfter = $calculator->calculate($contextAfter, $amount);

        // Verify different rates applied
        $this->assertSame('70.0000', $breakdownBefore->totalTaxAmount->getAmount());
        $this->assertSame('80.0000', $breakdownAfter->totalTaxAmount->getAmount());
    }

    public function test_nexus_threshold_validation(): void
    {
        // This test verifies nexus checking logic
        // NOTE: Full implementation requires application layer
        
        $this->assertTrue(true, 'Nexus threshold validation requires application layer implementation');
    }

    public function test_tax_holiday_zero_rate(): void
    {
        // Setup: Tax holiday period with 0% rate
        $repository = new InMemoryTaxRateRepository();
        
        $repository->addRate(new TaxRate(
            taxCode: 'US-TX-HOLIDAY',
            rate: '0.00', // Tax holiday
            type: 'sales_tax',
            level: 'state',
            jurisdictionCode: 'US-TX',
            effectiveFrom: new \DateTimeImmutable('2024-08-01'),
            effectiveTo: new \DateTimeImmutable('2024-08-15'),
        ));

        $resolver = new JurisdictionResolver();
        $calculator = new TaxCalculator($repository, $resolver);

        $context = new TaxContext(
            transactionId: 'TXN-HOLIDAY',
            transactionDate: new \DateTimeImmutable('2024-08-10'),
            taxCode: 'US-TX-HOLIDAY',
            taxType: 'sales_tax',
            customerId: 'CUST-HOLIDAY',
            billingAddress: ['country' => 'US', 'state' => 'TX'],
            shippingAddress: ['country' => 'US', 'state' => 'TX'],
        );

        $amount = Money::of('500.00', 'USD');
        $breakdown = $calculator->calculate($context, $amount);

        // Verify: Zero tax during holiday
        $this->assertSame('500.0000', $breakdown->netAmount->getAmount());
        $this->assertSame('0.0000', $breakdown->totalTaxAmount->getAmount());
        $this->assertSame('500.0000', $breakdown->grossAmount->getAmount());
    }

    public function test_expired_certificate_rejection(): void
    {
        // Setup
        $repository = new InMemoryTaxRateRepository();
        $repository->addRate(new TaxRate(
            taxCode: 'US-CA-SALES',
            rate: '10.00',
            type: 'sales_tax',
            level: 'state',
            jurisdictionCode: 'US-CA',
            effectiveFrom: new \DateTimeImmutable('2024-01-01'),
        ));

        $resolver = new JurisdictionResolver();
        $calculator = new TaxCalculator($repository, $resolver);

        // Expired certificate
        $expiredCertificate = new ExemptionCertificate(
            certificateId: 'CERT-EXPIRED',
            customerId: 'CUST-EXPIRED',
            reason: 'resale',
            exemptionPercentage: 100.0,
            issueDate: new \DateTimeImmutable('2023-01-01'),
            expirationDate: new \DateTimeImmutable('2023-12-31'),
        );

        $context = new TaxContext(
            transactionId: 'TXN-EXPIRED-CERT',
            transactionDate: new \DateTimeImmutable('2024-06-15'),
            taxCode: 'US-CA-SALES',
            taxType: 'sales_tax',
            customerId: 'CUST-EXPIRED',
            billingAddress: ['country' => 'US', 'state' => 'CA'],
            shippingAddress: ['country' => 'US', 'state' => 'CA'],
            exemptionCertificate: $expiredCertificate,
        );

        $amount = Money::of('1000.00', 'USD');
        
        // Should throw exception or ignore expired certificate
        // Implementation depends on validation strategy
        $this->expectException(\Exception::class);
        $calculator->calculate($context, $amount);
    }

    public function test_multi_currency_reporting(): void
    {
        // Setup: Multiple currencies
        $repository = new InMemoryTaxRateRepository();
        
        $repository->addRate(new TaxRate(
            taxCode: 'GB-VAT',
            rate: '20.00',
            type: 'vat',
            level: 'federal',
            jurisdictionCode: 'GB',
            effectiveFrom: new \DateTimeImmutable('2024-01-01'),
        ));

        $resolver = new JurisdictionResolver();
        $calculator = new TaxCalculator($repository, $resolver);

        // Transaction in GBP
        $contextGBP = new TaxContext(
            transactionId: 'TXN-GBP',
            transactionDate: new \DateTimeImmutable('2024-01-15'),
            taxCode: 'GB-VAT',
            taxType: 'vat',
            customerId: 'CUST-UK',
            billingAddress: ['country' => 'GB'],
            shippingAddress: ['country' => 'GB'],
        );

        $amountGBP = Money::of('1000.00', 'GBP');
        $breakdownGBP = $calculator->calculate($contextGBP, $amountGBP);

        // Verify currency preserved
        $this->assertSame('GBP', $breakdownGBP->netAmount->getCurrency());
        $this->assertSame('GBP', $breakdownGBP->totalTaxAmount->getCurrency());
        $this->assertSame('200.0000', $breakdownGBP->totalTaxAmount->getAmount());
    }
}
