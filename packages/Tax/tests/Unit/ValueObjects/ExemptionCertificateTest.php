<?php

declare(strict_types=1);

namespace Nexus\Tax\Tests\Unit\ValueObjects;

use Nexus\Currency\ValueObjects\Money;
use Nexus\Tax\ValueObjects\ExemptionCertificate;
use PHPUnit\Framework\TestCase;

final class ExemptionCertificateTest extends TestCase
{
    public function test_it_can_be_created_with_valid_data(): void
    {
        $certificate = new ExemptionCertificate(
            certificateId: 'CERT-001',
            customerId: 'CUST-001',
            reason: 'resale',
            exemptionPercentage: 100.0,
            issueDate: new \DateTimeImmutable('2024-01-01'),
            expirationDate: new \DateTimeImmutable('2025-01-01'),
        );

        $this->assertSame('CERT-001', $certificate->certificateId);
        $this->assertSame('CUST-001', $certificate->customerId);
        $this->assertSame(100.0, $certificate->exemptionPercentage);
    }

    public function test_it_rejects_negative_percentage(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Exemption percentage must be between 0 and 100');

        new ExemptionCertificate(
            certificateId: 'CERT-001',
            customerId: 'CUST-001',
            reason: 'resale',
            exemptionPercentage: -10.0,
            issueDate: new \DateTimeImmutable('2024-01-01'),
        );
    }

    public function test_it_rejects_percentage_over_100(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Exemption percentage must be between 0 and 100');

        new ExemptionCertificate(
            certificateId: 'CERT-001',
            customerId: 'CUST-001',
            reason: 'resale',
            exemptionPercentage: 150.0,
            issueDate: new \DateTimeImmutable('2024-01-01'),
        );
    }

    public function test_it_rejects_expiration_before_issue(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Expiration date must be after issue date');

        new ExemptionCertificate(
            certificateId: 'CERT-001',
            customerId: 'CUST-001',
            reason: 'resale',
            exemptionPercentage: 100.0,
            issueDate: new \DateTimeImmutable('2024-06-01'),
            expirationDate: new \DateTimeImmutable('2024-01-01'),
        );
    }

    public function test_is_valid_on_returns_true_when_no_expiration(): void
    {
        $certificate = new ExemptionCertificate(
            certificateId: 'CERT-001',
            customerId: 'CUST-001',
            reason: 'resale',
            exemptionPercentage: 100.0,
            issueDate: new \DateTimeImmutable('2024-01-01'),
        );

        $this->assertTrue($certificate->isValidOn(new \DateTimeImmutable('2025-12-31')));
    }

    public function test_is_valid_on_returns_true_when_date_in_range(): void
    {
        $certificate = new ExemptionCertificate(
            certificateId: 'CERT-001',
            customerId: 'CUST-001',
            reason: 'resale',
            exemptionPercentage: 100.0,
            issueDate: new \DateTimeImmutable('2024-01-01'),
            expirationDate: new \DateTimeImmutable('2025-01-01'),
        );

        $this->assertTrue($certificate->isValidOn(new \DateTimeImmutable('2024-06-15')));
    }

    public function test_is_valid_on_returns_false_when_before_issue(): void
    {
        $certificate = new ExemptionCertificate(
            certificateId: 'CERT-001',
            customerId: 'CUST-001',
            reason: 'resale',
            exemptionPercentage: 100.0,
            issueDate: new \DateTimeImmutable('2024-01-01'),
        );

        $this->assertFalse($certificate->isValidOn(new \DateTimeImmutable('2023-12-31')));
    }

    public function test_is_valid_on_returns_false_when_after_expiration(): void
    {
        $certificate = new ExemptionCertificate(
            certificateId: 'CERT-001',
            customerId: 'CUST-001',
            reason: 'resale',
            exemptionPercentage: 100.0,
            issueDate: new \DateTimeImmutable('2024-01-01'),
            expirationDate: new \DateTimeImmutable('2025-01-01'),
        );

        $this->assertFalse($certificate->isValidOn(new \DateTimeImmutable('2025-01-02')));
    }

    public function test_is_full_exemption_returns_true_for_100_percent(): void
    {
        $certificate = new ExemptionCertificate(
            certificateId: 'CERT-001',
            customerId: 'CUST-001',
            reason: 'resale',
            exemptionPercentage: 100.0,
            issueDate: new \DateTimeImmutable('2024-01-01'),
        );

        $this->assertTrue($certificate->isFullExemption());
    }

    public function test_is_full_exemption_returns_false_for_partial(): void
    {
        $certificate = new ExemptionCertificate(
            certificateId: 'CERT-001',
            customerId: 'CUST-001',
            reason: 'agricultural',
            exemptionPercentage: 50.0,
            issueDate: new \DateTimeImmutable('2024-01-01'),
        );

        $this->assertFalse($certificate->isFullExemption());
    }

    public function test_get_taxable_multiplier_returns_correct_value(): void
    {
        $certificate = new ExemptionCertificate(
            certificateId: 'CERT-001',
            customerId: 'CUST-001',
            reason: 'agricultural',
            exemptionPercentage: 40.0, // 40% exempt
            issueDate: new \DateTimeImmutable('2024-01-01'),
        );

        $this->assertSame('0.6000', $certificate->getTaxableMultiplier()); // 60% taxable
    }

    public function test_apply_to_amount_uses_bcmath_precision(): void
    {
        $certificate = new ExemptionCertificate(
            certificateId: 'CERT-001',
            customerId: 'CUST-001',
            reason: 'agricultural',
            exemptionPercentage: 50.0,
            issueDate: new \DateTimeImmutable('2024-01-01'),
        );

        $amount = Money::of('100.00', 'USD');
        $taxableAmount = $certificate->applyToAmount($amount);

        $this->assertSame('50.0000', $taxableAmount->getAmount()); // 50% of $100
        $this->assertSame('USD', $taxableAmount->getCurrency());
    }

    public function test_get_days_until_expiration_returns_correct_days(): void
    {
        $certificate = new ExemptionCertificate(
            certificateId: 'CERT-001',
            customerId: 'CUST-001',
            reason: 'resale',
            exemptionPercentage: 100.0,
            issueDate: new \DateTimeImmutable('2024-01-01'),
            expirationDate: new \DateTimeImmutable('2024-12-31'),
        );

        $fromDate = new \DateTimeImmutable('2024-12-01');
        $days = $certificate->getDaysUntilExpiration($fromDate);

        $this->assertSame(30, $days);
    }

    public function test_get_days_until_expiration_returns_null_when_no_expiration(): void
    {
        $certificate = new ExemptionCertificate(
            certificateId: 'CERT-001',
            customerId: 'CUST-001',
            reason: 'resale',
            exemptionPercentage: 100.0,
            issueDate: new \DateTimeImmutable('2024-01-01'),
        );

        $this->assertNull($certificate->getDaysUntilExpiration());
    }
}
