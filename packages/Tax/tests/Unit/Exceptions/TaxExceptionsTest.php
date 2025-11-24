<?php

declare(strict_types=1);

namespace Nexus\Tax\Tests\Unit\Exceptions;

use Nexus\Tax\Exceptions\ExemptionCertificateExpiredException;
use Nexus\Tax\Exceptions\InvalidExemptionPercentageException;
use Nexus\Tax\Exceptions\InvalidTaxCodeException;
use Nexus\Tax\Exceptions\InvalidTaxContextException;
use Nexus\Tax\Exceptions\JurisdictionNotResolvedException;
use Nexus\Tax\Exceptions\NoNexusInJurisdictionException;
use Nexus\Tax\Exceptions\ReverseChargeNotAllowedException;
use Nexus\Tax\Exceptions\TaxCalculationException;
use Nexus\Tax\Exceptions\TaxRateNotFoundException;
use PHPUnit\Framework\TestCase;

final class TaxExceptionsTest extends TestCase
{
    public function test_tax_rate_not_found_exception(): void
    {
        $exception = new TaxRateNotFoundException(
            taxCode: 'US-CA-SALES',
            effectiveDate: new \DateTimeImmutable('2024-01-15')
        );

        $this->assertStringContainsString("Tax rate 'US-CA-SALES' not found", $exception->getMessage());
        $this->assertStringContainsString('2024-01-15', $exception->getMessage());
        $this->assertSame('US-CA-SALES', $exception->getTaxCode());
        $this->assertInstanceOf(\DateTimeInterface::class, $exception->getEffectiveDate());
        
        $context = $exception->getContext();
        $this->assertArrayHasKey('tax_code', $context);
        $this->assertArrayHasKey('effective_date', $context);
    }

    public function test_no_nexus_in_jurisdiction_exception(): void
    {
        $exception = new NoNexusInJurisdictionException(
            jurisdictionCode: 'US-CA',
            message: 'No economic nexus in California'
        );

        $this->assertStringContainsString('No economic nexus in California', $exception->getMessage());
        $this->assertSame('US-CA', $exception->getJurisdictionCode());
    }

    public function test_exemption_certificate_expired_exception(): void
    {
        $exception = new ExemptionCertificateExpiredException(
            certificateId: 'CERT-001',
            expirationDate: new \DateTimeImmutable('2023-12-31'),
            attemptedUseDate: new \DateTimeImmutable('2024-01-15')
        );

        $this->assertStringContainsString("Exemption certificate 'CERT-001' expired", $exception->getMessage());
        $this->assertStringContainsString('2023-12-31', $exception->getMessage());
        $this->assertStringContainsString('2024-01-15', $exception->getMessage());
        $this->assertSame('CERT-001', $exception->getCertificateId());
        
        $context = $exception->getContext();
        $this->assertArrayHasKey('certificate_id', $context);
        $this->assertArrayHasKey('expiration_date', $context);
        $this->assertArrayHasKey('attempted_use_date', $context);
    }

    public function test_invalid_exemption_percentage_exception(): void
    {
        $exception = new InvalidExemptionPercentageException(
            percentage: 150.0 // Invalid - over 100%
        );

        $this->assertStringContainsString('Exemption percentage must be between 0 and 100', $exception->getMessage());
        $this->assertStringContainsString('150', $exception->getMessage());
        $this->assertSame(150.0, $exception->getPercentage());
    }

    public function test_jurisdiction_not_resolved_exception(): void
    {
        $address = ['city' => 'Unknown City'];
        
        $exception = new JurisdictionNotResolvedException(
            address: $address,
            reason: 'Country code missing'
        );

        $this->assertStringContainsString('Could not resolve jurisdiction', $exception->getMessage());
        $this->assertStringContainsString('Country code missing', $exception->getMessage());
        $this->assertSame($address, $exception->getAddress());
        
        $context = $exception->getContext();
        $this->assertArrayHasKey('address', $context);
        $this->assertArrayHasKey('reason', $context);
    }

    public function test_invalid_tax_code_exception(): void
    {
        $exception = new InvalidTaxCodeException(
            taxCode: 'INVALID',
            reason: 'Tax code format is invalid'
        );

        $this->assertStringContainsString("Invalid tax code 'INVALID'", $exception->getMessage());
        $this->assertStringContainsString('Tax code format is invalid', $exception->getMessage());
        $this->assertSame('INVALID', $exception->getTaxCode());
    }

    public function test_invalid_tax_context_exception(): void
    {
        $exception = new InvalidTaxContextException(
            message: 'billingAddress must contain country'
        );

        $this->assertStringContainsString('billingAddress must contain country', $exception->getMessage());
    }

    public function test_reverse_charge_not_allowed_exception(): void
    {
        $exception = new ReverseChargeNotAllowedException(
            reason: 'Reverse charge only allowed for B2B transactions'
        );

        $this->assertStringContainsString('Reverse charge not allowed', $exception->getMessage());
        $this->assertStringContainsString('B2B transactions', $exception->getMessage());
    }

    public function test_tax_calculation_exception(): void
    {
        $exception = new TaxCalculationException(
            message: 'Tax calculation failed due to missing rate',
            context: [
                'transaction_id' => 'TXN-001',
                'tax_code' => 'US-CA-SALES',
            ]
        );

        $this->assertStringContainsString('Tax calculation failed', $exception->getMessage());
        
        $context = $exception->getContext();
        $this->assertArrayHasKey('transaction_id', $context);
        $this->assertArrayHasKey('tax_code', $context);
        $this->assertSame('TXN-001', $context['transaction_id']);
    }
}
