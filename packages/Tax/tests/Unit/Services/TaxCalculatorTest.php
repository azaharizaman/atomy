<?php

declare(strict_types=1);

namespace Nexus\Tax\Tests\Unit\Services;

use Nexus\Currency\ValueObjects\Money;
use Nexus\Tax\Contracts\TaxJurisdictionResolverInterface;
use Nexus\Tax\Contracts\TaxRateRepositoryInterface;
use Nexus\Tax\Exceptions\TaxRateNotFoundException;
use Nexus\Tax\Services\TaxCalculator;
use Nexus\Tax\ValueObjects\ExemptionCertificate;
use Nexus\Tax\ValueObjects\TaxContext;
use Nexus\Tax\ValueObjects\TaxJurisdiction;
use Nexus\Tax\ValueObjects\TaxRate;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class TaxCalculatorTest extends TestCase
{
    private MockObject&TaxRateRepositoryInterface $rateRepository;
    private MockObject&TaxJurisdictionResolverInterface $jurisdictionResolver;
    private TaxCalculator $calculator;

    protected function setUp(): void
    {
        $this->rateRepository = $this->createMock(TaxRateRepositoryInterface::class);
        $this->jurisdictionResolver = $this->createMock(TaxJurisdictionResolverInterface::class);
        
        $this->calculator = new TaxCalculator(
            $this->rateRepository,
            $this->jurisdictionResolver,
        );
    }

    public function test_it_calculates_basic_tax(): void
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

        $jurisdiction = new TaxJurisdiction(
            code: 'US-CA',
            name: 'California',
            level: 'state',
            countryCode: 'US',
            stateCode: 'CA',
        );

        $rate = new TaxRate(
            taxCode: 'US-CA-SALES',
            rate: '7.25',
            type: 'sales_tax',
            level: 'state',
            jurisdictionCode: 'US-CA',
            effectiveFrom: new \DateTimeImmutable('2024-01-01'),
        );

        $this->jurisdictionResolver
            ->expects($this->once())
            ->method('resolve')
            ->with($context)
            ->willReturn($jurisdiction);

        $this->rateRepository
            ->expects($this->once())
            ->method('findByCode')
            ->with('US-CA-SALES', $context->transactionDate)
            ->willReturn($rate);

        $amount = Money::of('100.00', 'USD');
        $breakdown = $this->calculator->calculate($context, $amount);

        $this->assertSame('100.0000', $breakdown->netAmount->getAmount());
        $this->assertSame('7.2500', $breakdown->totalTaxAmount->getAmount());
        $this->assertSame('107.2500', $breakdown->grossAmount->getAmount());
        $this->assertCount(1, $breakdown->taxLines);
    }

    public function test_it_applies_exemption_to_reduce_tax(): void
    {
        $certificate = new ExemptionCertificate(
            certificateId: 'CERT-001',
            customerId: 'CUST-001',
            reason: 'agricultural',
            exemptionPercentage: 50.0,
            issueDate: new \DateTimeImmutable('2024-01-01'),
            expirationDate: new \DateTimeImmutable('2025-01-01'),
        );

        $context = new TaxContext(
            transactionId: 'TXN-001',
            transactionDate: new \DateTimeImmutable('2024-01-15'),
            taxCode: 'US-CA-SALES',
            taxType: 'sales_tax',
            customerId: 'CUST-001',
            billingAddress: ['country' => 'US', 'state' => 'CA'],
            shippingAddress: ['country' => 'US', 'state' => 'CA'],
            exemptionCertificate: $certificate,
        );

        $jurisdiction = new TaxJurisdiction(
            code: 'US-CA',
            name: 'California',
            level: 'state',
            countryCode: 'US',
            stateCode: 'CA',
        );

        $rate = new TaxRate(
            taxCode: 'US-CA-SALES',
            rate: '10.00',
            type: 'sales_tax',
            level: 'state',
            jurisdictionCode: 'US-CA',
            effectiveFrom: new \DateTimeImmutable('2024-01-01'),
        );

        $this->jurisdictionResolver
            ->method('resolve')
            ->willReturn($jurisdiction);

        $this->rateRepository
            ->method('findByCode')
            ->willReturn($rate);

        $amount = Money::of('100.00', 'USD');
        $breakdown = $this->calculator->calculate($context, $amount);

        // 50% exempt, so tax on $50 at 10% = $5
        $this->assertSame('5.0000', $breakdown->totalTaxAmount->getAmount());
    }

    public function test_it_throws_exception_when_rate_not_found(): void
    {
        $context = new TaxContext(
            transactionId: 'TXN-001',
            transactionDate: new \DateTimeImmutable('2024-01-15'),
            taxCode: 'INVALID-CODE',
            taxType: 'sales_tax',
            customerId: 'CUST-001',
            billingAddress: ['country' => 'US'],
            shippingAddress: ['country' => 'US'],
        );

        $jurisdiction = new TaxJurisdiction(
            code: 'US',
            name: 'United States',
            level: 'federal',
            countryCode: 'US',
        );

        $this->jurisdictionResolver
            ->method('resolve')
            ->willReturn($jurisdiction);

        $this->rateRepository
            ->method('findByCode')
            ->willThrowException(new TaxRateNotFoundException('INVALID-CODE', $context->transactionDate));

        $this->expectException(TaxRateNotFoundException::class);

        $amount = Money::of('100.00', 'USD');
        $this->calculator->calculate($context, $amount);
    }

    public function test_it_handles_reverse_charge_correctly(): void
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

        $jurisdiction = new TaxJurisdiction(
            code: 'FR',
            name: 'France',
            level: 'federal',
            countryCode: 'FR',
        );

        $rate = new TaxRate(
            taxCode: 'EU-VAT',
            rate: '20.00',
            type: 'vat',
            level: 'federal',
            jurisdictionCode: 'FR',
            effectiveFrom: new \DateTimeImmutable('2024-01-01'),
        );

        $this->jurisdictionResolver
            ->method('resolve')
            ->willReturn($jurisdiction);

        $this->rateRepository
            ->method('findByCode')
            ->willReturn($rate);

        $amount = Money::of('100.00', 'EUR');
        $breakdown = $this->calculator->calculate($context, $amount);

        $this->assertSame('0.0000', $breakdown->totalTaxAmount->getAmount());
        $this->assertTrue($breakdown->isReverseCharge);
    }

    public function test_calculate_adjustment_handles_full_reversal(): void
    {
        $originalContext = new TaxContext(
            transactionId: 'TXN-001',
            transactionDate: new \DateTimeImmutable('2024-01-15'),
            taxCode: 'US-CA-SALES',
            taxType: 'sales_tax',
            customerId: 'CUST-001',
            billingAddress: ['country' => 'US', 'state' => 'CA'],
            shippingAddress: ['country' => 'US', 'state' => 'CA'],
        );

        $jurisdiction = new TaxJurisdiction(
            code: 'US-CA',
            name: 'California',
            level: 'state',
            countryCode: 'US',
            stateCode: 'CA',
        );

        $rate = new TaxRate(
            taxCode: 'US-CA-SALES',
            rate: '7.25',
            type: 'sales_tax',
            level: 'state',
            jurisdictionCode: 'US-CA',
            effectiveFrom: new \DateTimeImmutable('2024-01-01'),
        );

        $this->jurisdictionResolver
            ->method('resolve')
            ->willReturn($jurisdiction);

        $this->rateRepository
            ->method('findByCode')
            ->willReturn($rate);

        $originalAmount = Money::of('100.00', 'USD');
        $originalBreakdown = $this->calculator->calculate($originalContext, $originalAmount);

        // Full reversal
        $reversal = $this->calculator->calculateAdjustment(
            $originalBreakdown,
            Money::of('-100.00', 'USD')
        );

        $this->assertSame('-100.0000', $reversal->netAmount->getAmount());
        $this->assertSame('-7.2500', $reversal->totalTaxAmount->getAmount());
    }

    public function test_preview_without_exemption_shows_full_tax(): void
    {
        $certificate = new ExemptionCertificate(
            certificateId: 'CERT-001',
            customerId: 'CUST-001',
            reason: 'agricultural',
            exemptionPercentage: 50.0,
            issueDate: new \DateTimeImmutable('2024-01-01'),
            expirationDate: new \DateTimeImmutable('2025-01-01'),
        );

        $context = new TaxContext(
            transactionId: 'TXN-001',
            transactionDate: new \DateTimeImmutable('2024-01-15'),
            taxCode: 'US-CA-SALES',
            taxType: 'sales_tax',
            customerId: 'CUST-001',
            billingAddress: ['country' => 'US', 'state' => 'CA'],
            shippingAddress: ['country' => 'US', 'state' => 'CA'],
            exemptionCertificate: $certificate,
        );

        $jurisdiction = new TaxJurisdiction(
            code: 'US-CA',
            name: 'California',
            level: 'state',
            countryCode: 'US',
            stateCode: 'CA',
        );

        $rate = new TaxRate(
            taxCode: 'US-CA-SALES',
            rate: '10.00',
            type: 'sales_tax',
            level: 'state',
            jurisdictionCode: 'US-CA',
            effectiveFrom: new \DateTimeImmutable('2024-01-01'),
        );

        $this->jurisdictionResolver
            ->method('resolve')
            ->willReturn($jurisdiction);

        $this->rateRepository
            ->method('findByCode')
            ->willReturn($rate);

        $amount = Money::of('100.00', 'USD');
        $preview = $this->calculator->previewWithoutExemption($context, $amount);

        // Without exemption, tax should be full 10% of $100 = $10
        $this->assertSame('10.0000', $preview->totalTaxAmount->getAmount());
    }
}
