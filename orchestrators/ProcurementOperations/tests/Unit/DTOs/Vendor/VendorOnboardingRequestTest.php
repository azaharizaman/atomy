<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Tests\Unit\DTOs\Vendor;

use Nexus\ProcurementOperations\DTOs\Vendor\VendorOnboardingRequest;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(VendorOnboardingRequest::class)]
final class VendorOnboardingRequestTest extends TestCase
{
    #[Test]
    public function it_creates_request_with_all_parameters(): void
    {
        $request = new VendorOnboardingRequest(
            tenantId: 'tenant-123',
            vendorName: 'Acme Corp',
            vendorType: 'domestic',
            taxId: 'TAX123456',
            registrationNumber: 'REG789',
            countryCode: 'MY',
            primaryContact: ['name' => 'John Doe', 'email' => 'john@acme.com'],
            billingAddress: ['street' => '123 Main St'],
            bankDetails: ['bank' => 'CIMB', 'account' => '1234567890'],
            expectedAnnualVolume: 100000.00,
            requestedTier: 'premium',
        );

        $this->assertSame('tenant-123', $request->tenantId);
        $this->assertSame('Acme Corp', $request->vendorName);
        $this->assertSame('domestic', $request->vendorType);
        $this->assertSame('TAX123456', $request->taxId);
        $this->assertSame('MY', $request->countryCode);
        $this->assertSame(100000.00, $request->expectedAnnualVolume);
    }

    #[Test]
    public function it_creates_domestic_vendor_request(): void
    {
        $request = VendorOnboardingRequest::forDomesticVendor(
            tenantId: 'tenant-123',
            vendorName: 'Local Supplier Sdn Bhd',
            taxId: '202001234567',
            primaryContact: ['name' => 'Ahmad', 'email' => 'ahmad@local.my'],
        );

        $this->assertSame('tenant-123', $request->tenantId);
        $this->assertSame('Local Supplier Sdn Bhd', $request->vendorName);
        $this->assertSame('domestic', $request->vendorType);
        $this->assertSame('MY', $request->countryCode);
        $this->assertSame('basic', $request->requestedTier);
    }

    #[Test]
    public function it_creates_foreign_vendor_request(): void
    {
        $request = VendorOnboardingRequest::forForeignVendor(
            tenantId: 'tenant-123',
            vendorName: 'Singapore Trading Pte Ltd',
            taxId: 'SG12345678',
            countryCode: 'SG',
            primaryContact: ['name' => 'Tan Wei', 'email' => 'tan@sgtrading.sg'],
        );

        $this->assertSame('Singapore Trading Pte Ltd', $request->vendorName);
        $this->assertSame('foreign', $request->vendorType);
        $this->assertSame('SG', $request->countryCode);
        $this->assertSame('premium', $request->requestedTier);
    }

    #[Test]
    public function it_creates_enterprise_vendor_request(): void
    {
        $request = VendorOnboardingRequest::forEnterpriseVendor(
            tenantId: 'tenant-123',
            vendorName: 'Global Enterprise Inc',
            taxId: 'US12-3456789',
            countryCode: 'US',
            primaryContact: ['name' => 'Jane Smith', 'email' => 'jane@global.com'],
            expectedAnnualVolume: 5000000.00,
        );

        $this->assertSame('Global Enterprise Inc', $request->vendorName);
        $this->assertSame('enterprise', $request->vendorType);
        $this->assertSame('enterprise', $request->requestedTier);
        $this->assertSame(5000000.00, $request->expectedAnnualVolume);
    }

    #[Test]
    public function it_converts_to_array(): void
    {
        $request = VendorOnboardingRequest::forDomesticVendor(
            tenantId: 'tenant-123',
            vendorName: 'Test Vendor',
            taxId: 'TAX123',
            primaryContact: ['name' => 'Test', 'email' => 'test@test.com'],
        );

        $array = $request->toArray();

        $this->assertIsArray($array);
        $this->assertSame('tenant-123', $array['tenant_id']);
        $this->assertSame('Test Vendor', $array['vendor_name']);
        $this->assertSame('domestic', $array['vendor_type']);
        $this->assertSame('MY', $array['country_code']);
    }

    #[Test]
    #[DataProvider('vendorTypeProvider')]
    public function it_validates_vendor_types(string $type, bool $expectValid): void
    {
        $validTypes = ['domestic', 'foreign', 'enterprise'];
        $isValid = in_array($type, $validTypes, true);

        $this->assertSame($expectValid, $isValid);
    }

    /**
     * @return array<string, array{string, bool}>
     */
    public static function vendorTypeProvider(): array
    {
        return [
            'domestic is valid' => ['domestic', true],
            'foreign is valid' => ['foreign', true],
            'enterprise is valid' => ['enterprise', true],
            'unknown is invalid' => ['unknown', false],
            'empty is invalid' => ['', false],
        ];
    }
}
