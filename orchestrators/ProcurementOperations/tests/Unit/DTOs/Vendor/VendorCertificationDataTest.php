<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Tests\Unit\DTOs\Vendor;

use Nexus\ProcurementOperations\DTOs\Vendor\VendorCertificationData;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(VendorCertificationData::class)]
final class VendorCertificationDataTest extends TestCase
{
    #[Test]
    public function it_creates_iso9001_certification(): void
    {
        $issuedAt = new \DateTimeImmutable('2023-01-01');
        $expiresAt = new \DateTimeImmutable('2026-01-01');

        $cert = VendorCertificationData::iso9001(
            issuedBy: 'Bureau Veritas',
            issuedAt: $issuedAt,
            expiresAt: $expiresAt,
            certificateNumber: 'BV-ISO9001-2023-001',
        );

        $this->assertSame('ISO 9001', $cert->certificationType);
        $this->assertSame('Bureau Veritas', $cert->issuingBody);
        $this->assertSame('BV-ISO9001-2023-001', $cert->certificateNumber);
        $this->assertSame($issuedAt, $cert->issuedAt);
        $this->assertSame($expiresAt, $cert->expiresAt);
    }

    #[Test]
    public function it_creates_iso14001_certification(): void
    {
        $cert = VendorCertificationData::iso14001(
            issuedBy: 'SGS',
            issuedAt: new \DateTimeImmutable('2023-06-01'),
            expiresAt: new \DateTimeImmutable('2026-06-01'),
        );

        $this->assertSame('ISO 14001', $cert->certificationType);
        $this->assertSame('SGS', $cert->issuingBody);
    }

    #[Test]
    public function it_creates_iso27001_certification(): void
    {
        $cert = VendorCertificationData::iso27001(
            issuedBy: 'TUV SUD',
            issuedAt: new \DateTimeImmutable('2024-01-01'),
            expiresAt: new \DateTimeImmutable('2027-01-01'),
        );

        $this->assertSame('ISO 27001', $cert->certificationType);
    }

    #[Test]
    public function it_checks_valid_certification(): void
    {
        $validCert = VendorCertificationData::iso9001(
            issuedBy: 'Bureau Veritas',
            issuedAt: new \DateTimeImmutable('-1 year'),
            expiresAt: new \DateTimeImmutable('+2 years'),
        );

        $this->assertTrue($validCert->isValid());
    }

    #[Test]
    public function it_checks_expired_certification(): void
    {
        $expiredCert = VendorCertificationData::iso9001(
            issuedBy: 'Bureau Veritas',
            issuedAt: new \DateTimeImmutable('-4 years'),
            expiresAt: new \DateTimeImmutable('-1 year'),
        );

        $this->assertFalse($expiredCert->isValid());
        $this->assertTrue($expiredCert->isExpired());
    }

    #[Test]
    public function it_checks_expiring_soon(): void
    {
        $expiringSoon = VendorCertificationData::iso9001(
            issuedBy: 'Bureau Veritas',
            issuedAt: new \DateTimeImmutable('-2 years'),
            expiresAt: new \DateTimeImmutable('+45 days'),
        );

        $this->assertTrue($expiringSoon->isExpiringSoon(60));
        $this->assertFalse($expiringSoon->isExpiringSoon(30));
    }

    #[Test]
    public function it_calculates_days_until_expiry(): void
    {
        $cert = VendorCertificationData::iso9001(
            issuedBy: 'Bureau Veritas',
            issuedAt: new \DateTimeImmutable('-1 year'),
            expiresAt: new \DateTimeImmutable('+100 days'),
        );

        $daysUntilExpiry = $cert->getDaysUntilExpiry();

        $this->assertGreaterThan(95, $daysUntilExpiry);
        $this->assertLessThan(105, $daysUntilExpiry);
    }

    #[Test]
    public function it_converts_to_array(): void
    {
        $issuedAt = new \DateTimeImmutable('2023-01-01');
        $expiresAt = new \DateTimeImmutable('2026-01-01');

        $cert = VendorCertificationData::iso9001(
            issuedBy: 'Bureau Veritas',
            issuedAt: $issuedAt,
            expiresAt: $expiresAt,
            certificateNumber: 'BV-123',
        );

        $array = $cert->toArray();

        $this->assertIsArray($array);
        $this->assertSame('ISO 9001', $array['certification_type']);
        $this->assertSame('Bureau Veritas', $array['issuing_body']);
        $this->assertSame('BV-123', $array['certificate_number']);
        $this->assertSame('2023-01-01', $array['issued_at']);
        $this->assertSame('2026-01-01', $array['expires_at']);
    }

    #[Test]
    public function it_creates_custom_certification(): void
    {
        $cert = new VendorCertificationData(
            certificationType: 'HALAL',
            issuingBody: 'JAKIM',
            certificateNumber: 'HAL-2024-001',
            issuedAt: new \DateTimeImmutable('2024-01-01'),
            expiresAt: new \DateTimeImmutable('2026-01-01'),
            scope: 'Food Processing',
            verificationUrl: 'https://jakim.gov.my/verify/HAL-2024-001',
        );

        $this->assertSame('HALAL', $cert->certificationType);
        $this->assertSame('JAKIM', $cert->issuingBody);
        $this->assertSame('Food Processing', $cert->scope);
        $this->assertSame('https://jakim.gov.my/verify/HAL-2024-001', $cert->verificationUrl);
    }

    #[Test]
    #[DataProvider('certificationTypeProvider')]
    public function it_validates_certification_types(string $method, string $expectedType): void
    {
        $issuedAt = new \DateTimeImmutable('2023-01-01');
        $expiresAt = new \DateTimeImmutable('2026-01-01');

        $cert = VendorCertificationData::$method(
            issuedBy: 'Test Body',
            issuedAt: $issuedAt,
            expiresAt: $expiresAt,
        );

        $this->assertSame($expectedType, $cert->certificationType);
    }

    public static function certificationTypeProvider(): array
    {
        return [
            'iso9001' => ['iso9001', 'ISO 9001'],
            'iso14001' => ['iso14001', 'ISO 14001'],
            'iso27001' => ['iso27001', 'ISO 27001'],
        ];
    }
}
