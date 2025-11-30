<?php

declare(strict_types=1);

namespace Nexus\Tax\Tests\Unit\Services;

use Nexus\Tax\Enums\TaxExemptionReason;
use Nexus\Tax\Exceptions\ExemptionCertificateExpiredException;
use Nexus\Tax\Services\ExemptionManager;
use Nexus\Tax\ValueObjects\ExemptionCertificate;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class ExemptionManagerTest extends TestCase
{
    private ExemptionManager $manager;
    private LoggerInterface $logger;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->manager = new ExemptionManager($this->logger);
    }

    public function test_it_validates_active_certificate(): void
    {
        $certificate = new ExemptionCertificate(
            certificateId: 'CERT-001',
            customerId: 'CUST-001',
            reason: TaxExemptionReason::Resale,
            exemptionPercentage: 100.0,
            issueDate: new \DateTimeImmutable('2024-01-01'),
            expirationDate: new \DateTimeImmutable('2025-12-31'),
        );

        $useDate = new \DateTimeImmutable('2024-06-15');

        $this->assertTrue($this->manager->isValid($certificate, $useDate));
    }

    public function test_it_rejects_expired_certificate(): void
    {
        $this->expectException(ExemptionCertificateExpiredException::class);

        $certificate = new ExemptionCertificate(
            certificateId: 'CERT-002',
            customerId: 'CUST-002',
            reason: TaxExemptionReason::Government,
            exemptionPercentage: 100.0,
            issueDate: new \DateTimeImmutable('2023-01-01'),
            expirationDate: new \DateTimeImmutable('2023-12-31'), // Expired
        );

        $useDate = new \DateTimeImmutable('2024-06-15');

        $this->manager->isValid($certificate, $useDate);
    }

    public function test_it_rejects_not_yet_valid_certificate(): void
    {
        $this->expectException(ExemptionCertificateExpiredException::class);

        $certificate = new ExemptionCertificate(
            certificateId: 'CERT-003',
            customerId: 'CUST-003',
            reason: TaxExemptionReason::Nonprofit,
            exemptionPercentage: 100.0,
            issueDate: new \DateTimeImmutable('2025-01-01'), // Future
            expirationDate: new \DateTimeImmutable('2026-12-31'),
        );

        $useDate = new \DateTimeImmutable('2024-06-15');

        $this->manager->isValid($certificate, $useDate);
    }

    public function test_it_validates_jurisdiction_match(): void
    {
        $certificate = new ExemptionCertificate(
            certificateId: 'CERT-004',
            customerId: 'CUST-004',
            reason: TaxExemptionReason::Export,
            exemptionPercentage: 100.0,
            issueDate: new \DateTimeImmutable('2024-01-01'),
            expirationDate: new \DateTimeImmutable('2025-12-31'),
            jurisdictionCode: 'US-CA',
        );

        $useDate = new \DateTimeImmutable('2024-06-15');

        // Valid for California
        $this->assertTrue($this->manager->isValid($certificate, $useDate, 'US-CA'));
        
        // Not valid for Texas
        $this->assertFalse($this->manager->isValid($certificate, $useDate, 'US-TX'));
    }

    public function test_it_accepts_certificate_without_jurisdiction_for_any_jurisdiction(): void
    {
        $certificate = new ExemptionCertificate(
            certificateId: 'CERT-005',
            customerId: 'CUST-005',
            reason: TaxExemptionReason::Diplomatic,
            exemptionPercentage: 100.0,
            issueDate: new \DateTimeImmutable('2024-01-01'),
            expirationDate: new \DateTimeImmutable('2025-12-31'),
            jurisdictionCode: null, // Valid anywhere
        );

        $useDate = new \DateTimeImmutable('2024-06-15');

        // Should be valid for any jurisdiction
        $this->assertTrue($this->manager->isValid($certificate, $useDate, 'US-CA'));
        $this->assertTrue($this->manager->isValid($certificate, $useDate, 'US-TX'));
        $this->assertTrue($this->manager->isValid($certificate, $useDate, 'GB'));
    }

    public function test_it_logs_validation_attempts(): void
    {
        $certificate = new ExemptionCertificate(
            certificateId: 'CERT-006',
            customerId: 'CUST-006',
            reason: TaxExemptionReason::Agricultural,
            exemptionPercentage: 50.0,
            issueDate: new \DateTimeImmutable('2024-01-01'),
            expirationDate: new \DateTimeImmutable('2025-12-31'),
        );

        $this->logger
            ->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Validating exemption certificate', $this->anything()],
                ['Certificate validated successfully', $this->anything()]
            );

        $this->manager->isValid($certificate, new \DateTimeImmutable('2024-06-15'));
    }
}
