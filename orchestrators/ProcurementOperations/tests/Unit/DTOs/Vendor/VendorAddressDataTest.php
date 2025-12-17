<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Tests\Unit\DTOs\Vendor;

use Nexus\ProcurementOperations\DTOs\Vendor\VendorAddressData;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(VendorAddressData::class)]
final class VendorAddressDataTest extends TestCase
{
    #[Test]
    public function it_creates_billing_address(): void
    {
        $address = VendorAddressData::billing(
            streetLine1: '123 Main Street',
            city: 'Kuala Lumpur',
            stateProvince: 'Wilayah Persekutuan',
            postalCode: '50000',
            countryCode: 'MY',
            streetLine2: 'Suite 100',
        );

        $this->assertSame('123 Main Street', $address->streetLine1);
        $this->assertSame('Suite 100', $address->streetLine2);
        $this->assertSame('Kuala Lumpur', $address->city);
        $this->assertSame('Wilayah Persekutuan', $address->stateProvince);
        $this->assertSame('50000', $address->postalCode);
        $this->assertSame('MY', $address->countryCode);
        $this->assertSame('billing', $address->addressType);
        $this->assertTrue($address->isPrimary);
    }

    #[Test]
    public function it_creates_shipping_address(): void
    {
        $address = VendorAddressData::shipping(
            streetLine1: '456 Industrial Way',
            city: 'Petaling Jaya',
            stateProvince: 'Selangor',
            postalCode: '46000',
            countryCode: 'MY',
        );

        $this->assertSame('shipping', $address->addressType);
        $this->assertFalse($address->isPrimary);
    }

    #[Test]
    public function it_creates_registered_address(): void
    {
        $address = VendorAddressData::registered(
            streetLine1: '789 Corporate Blvd',
            city: 'Singapore',
            stateProvince: 'Singapore',
            postalCode: '018935',
            countryCode: 'SG',
        );

        $this->assertSame('registered', $address->addressType);
        $this->assertTrue($address->isPrimary);
    }

    #[Test]
    public function it_converts_to_array(): void
    {
        $address = VendorAddressData::billing(
            streetLine1: '123 Main St',
            city: 'KL',
            stateProvince: 'WP',
            postalCode: '50000',
            countryCode: 'MY',
        );

        $array = $address->toArray();

        $this->assertIsArray($array);
        $this->assertSame('123 Main St', $array['street_line_1']);
        $this->assertSame('KL', $array['city']);
        $this->assertSame('billing', $array['address_type']);
        $this->assertTrue($array['is_primary']);
    }

    #[Test]
    public function it_formats_single_line_address(): void
    {
        $address = VendorAddressData::billing(
            streetLine1: '123 Main St',
            city: 'Kuala Lumpur',
            stateProvince: 'WP',
            postalCode: '50000',
            countryCode: 'MY',
            streetLine2: 'Floor 10',
        );

        $formatted = $address->formatSingleLine();

        $this->assertStringContainsString('123 Main St', $formatted);
        $this->assertStringContainsString('Floor 10', $formatted);
        $this->assertStringContainsString('Kuala Lumpur', $formatted);
        $this->assertStringContainsString('50000', $formatted);
    }

    #[Test]
    public function it_checks_if_domestic(): void
    {
        $domesticAddress = VendorAddressData::billing(
            streetLine1: '123 Main St',
            city: 'KL',
            stateProvince: 'WP',
            postalCode: '50000',
            countryCode: 'MY',
        );

        $foreignAddress = VendorAddressData::billing(
            streetLine1: '456 Other St',
            city: 'Singapore',
            stateProvince: 'Singapore',
            postalCode: '018935',
            countryCode: 'SG',
        );

        $this->assertTrue($domesticAddress->isDomestic('MY'));
        $this->assertFalse($foreignAddress->isDomestic('MY'));
    }
}
