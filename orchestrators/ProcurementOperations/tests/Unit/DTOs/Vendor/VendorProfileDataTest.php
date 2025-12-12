<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Tests\Unit\DTOs\Vendor;

use Nexus\ProcurementOperations\DTOs\Vendor\VendorAddressData;
use Nexus\ProcurementOperations\DTOs\Vendor\VendorCertificationData;
use Nexus\ProcurementOperations\DTOs\Vendor\VendorContactData;
use Nexus\ProcurementOperations\DTOs\Vendor\VendorProfileData;
use Nexus\ProcurementOperations\DTOs\Vendor\VendorRatingData;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(VendorProfileData::class)]
final class VendorProfileDataTest extends TestCase
{
    #[Test]
    public function it_creates_full_vendor_profile(): void
    {
        $addresses = [
            VendorAddressData::billing(
                streetLine1: '123 Main St',
                city: 'Kuala Lumpur',
                stateProvince: 'Wilayah Persekutuan',
                postalCode: '50000',
                countryCode: 'MY',
            ),
        ];

        $contacts = [
            VendorContactData::primary(
                name: 'John Doe',
                email: 'john@acme.com',
                phone: '+60123456789',
            ),
        ];

        $certifications = [
            VendorCertificationData::iso9001(
                issuedBy: 'Bureau Veritas',
                issuedAt: new \DateTimeImmutable('2023-01-01'),
                expiresAt: new \DateTimeImmutable('2026-01-01'),
            ),
        ];

        $rating = VendorRatingData::excellent();

        $profile = new VendorProfileData(
            vendorId: 'VND-MY-123',
            vendorName: 'Acme Corp Sdn Bhd',
            legalName: 'Acme Corporation Sdn Bhd',
            taxId: 'TAX123456',
            registrationNumber: 'REG789',
            countryCode: 'MY',
            tier: 'premium',
            status: 'active',
            addresses: $addresses,
            contacts: $contacts,
            certifications: $certifications,
            rating: $rating,
            onboardedAt: new \DateTimeImmutable('2024-01-15'),
        );

        $this->assertSame('VND-MY-123', $profile->vendorId);
        $this->assertSame('Acme Corp Sdn Bhd', $profile->vendorName);
        $this->assertSame('premium', $profile->tier);
        $this->assertSame('active', $profile->status);
        $this->assertCount(1, $profile->addresses);
        $this->assertCount(1, $profile->contacts);
        $this->assertCount(1, $profile->certifications);
        $this->assertNotNull($profile->rating);
    }

    #[Test]
    public function it_creates_minimal_vendor_profile(): void
    {
        $profile = VendorProfileData::minimal(
            vendorId: 'VND-MY-456',
            vendorName: 'Small Vendor',
            taxId: 'TAX789',
            countryCode: 'MY',
        );

        $this->assertSame('VND-MY-456', $profile->vendorId);
        $this->assertSame('Small Vendor', $profile->vendorName);
        $this->assertSame('basic', $profile->tier);
        $this->assertSame('pending', $profile->status);
        $this->assertEmpty($profile->addresses);
        $this->assertEmpty($profile->contacts);
        $this->assertEmpty($profile->certifications);
        $this->assertNull($profile->rating);
    }

    #[Test]
    public function it_checks_if_active(): void
    {
        $activeProfile = VendorProfileData::minimal('v1', 'Active', 'T1', 'MY');
        $reflector = new \ReflectionClass($activeProfile);
        
        // Create profile with active status
        $profile = new VendorProfileData(
            vendorId: 'v1',
            vendorName: 'Test',
            legalName: null,
            taxId: 'T1',
            registrationNumber: null,
            countryCode: 'MY',
            tier: 'basic',
            status: 'active',
            addresses: [],
            contacts: [],
            certifications: [],
            rating: null,
            onboardedAt: null,
        );

        $this->assertTrue($profile->isActive());
    }

    #[Test]
    public function it_checks_if_suspended(): void
    {
        $profile = new VendorProfileData(
            vendorId: 'v1',
            vendorName: 'Test',
            legalName: null,
            taxId: 'T1',
            registrationNumber: null,
            countryCode: 'MY',
            tier: 'basic',
            status: 'suspended',
            addresses: [],
            contacts: [],
            certifications: [],
            rating: null,
            onboardedAt: null,
        );

        $this->assertTrue($profile->isSuspended());
        $this->assertFalse($profile->isActive());
    }

    #[Test]
    public function it_gets_primary_address(): void
    {
        $billingAddress = VendorAddressData::billing(
            streetLine1: '123 Main St',
            city: 'KL',
            stateProvince: 'WP',
            postalCode: '50000',
            countryCode: 'MY',
        );

        $shippingAddress = VendorAddressData::shipping(
            streetLine1: '456 Other St',
            city: 'PJ',
            stateProvince: 'Selangor',
            postalCode: '46000',
            countryCode: 'MY',
        );

        $profile = new VendorProfileData(
            vendorId: 'v1',
            vendorName: 'Test',
            legalName: null,
            taxId: 'T1',
            registrationNumber: null,
            countryCode: 'MY',
            tier: 'basic',
            status: 'active',
            addresses: [$billingAddress, $shippingAddress],
            contacts: [],
            certifications: [],
            rating: null,
            onboardedAt: null,
        );

        $primary = $profile->getPrimaryAddress();

        $this->assertNotNull($primary);
        $this->assertSame('123 Main St', $primary->streetLine1);
    }

    #[Test]
    public function it_gets_primary_contact(): void
    {
        $primaryContact = VendorContactData::primary(
            name: 'John Doe',
            email: 'john@test.com',
            phone: '+60123456789',
        );

        $techContact = VendorContactData::technical(
            name: 'Tech Support',
            email: 'tech@test.com',
        );

        $profile = new VendorProfileData(
            vendorId: 'v1',
            vendorName: 'Test',
            legalName: null,
            taxId: 'T1',
            registrationNumber: null,
            countryCode: 'MY',
            tier: 'basic',
            status: 'active',
            addresses: [],
            contacts: [$techContact, $primaryContact],
            certifications: [],
            rating: null,
            onboardedAt: null,
        );

        $primary = $profile->getPrimaryContact();

        $this->assertNotNull($primary);
        $this->assertSame('John Doe', $primary->name);
    }

    #[Test]
    public function it_converts_to_array(): void
    {
        $profile = VendorProfileData::minimal('v1', 'Test Vendor', 'TAX123', 'MY');

        $array = $profile->toArray();

        $this->assertIsArray($array);
        $this->assertSame('v1', $array['vendor_id']);
        $this->assertSame('Test Vendor', $array['vendor_name']);
        $this->assertSame('TAX123', $array['tax_id']);
        $this->assertSame('MY', $array['country_code']);
        $this->assertSame('basic', $array['tier']);
        $this->assertSame('pending', $array['status']);
    }

    #[Test]
    public function it_checks_certification_validity(): void
    {
        $validCert = VendorCertificationData::iso9001(
            issuedBy: 'Bureau Veritas',
            issuedAt: new \DateTimeImmutable('-1 year'),
            expiresAt: new \DateTimeImmutable('+2 years'),
        );

        $expiredCert = VendorCertificationData::iso14001(
            issuedBy: 'SGS',
            issuedAt: new \DateTimeImmutable('-4 years'),
            expiresAt: new \DateTimeImmutable('-1 year'),
        );

        $profile = new VendorProfileData(
            vendorId: 'v1',
            vendorName: 'Test',
            legalName: null,
            taxId: 'T1',
            registrationNumber: null,
            countryCode: 'MY',
            tier: 'premium',
            status: 'active',
            addresses: [],
            contacts: [],
            certifications: [$validCert, $expiredCert],
            rating: null,
            onboardedAt: null,
        );

        $this->assertTrue($profile->hasValidCertification('ISO 9001'));
        $this->assertFalse($profile->hasValidCertification('ISO 14001'));
        $this->assertFalse($profile->hasValidCertification('ISO 27001'));
    }
}
