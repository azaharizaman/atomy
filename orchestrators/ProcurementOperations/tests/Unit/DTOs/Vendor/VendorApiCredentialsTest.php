<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Tests\Unit\DTOs\Vendor;

use Nexus\ProcurementOperations\DTOs\Vendor\VendorApiCredentials;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(VendorApiCredentials::class)]
final class VendorApiCredentialsTest extends TestCase
{
    #[Test]
    public function it_creates_api_credentials(): void
    {
        $expiresAt = new \DateTimeImmutable('+1 year');

        $credentials = new VendorApiCredentials(
            vendorId: 'VND-123',
            apiKey: 'ak_live_abc123xyz789',
            apiSecret: 'sk_live_secret456',
            accessToken: null,
            tokenExpiresAt: null,
            scopes: ['invoices:read', 'invoices:write', 'purchase_orders:read'],
            rateLimitPerHour: 1000,
            rateLimitPerDay: 10000,
            currentHourlyUsage: 0,
            currentDailyUsage: 0,
            isActive: true,
            createdAt: new \DateTimeImmutable(),
            expiresAt: $expiresAt,
        );

        $this->assertSame('VND-123', $credentials->vendorId);
        $this->assertSame('ak_live_abc123xyz789', $credentials->apiKey);
        $this->assertCount(3, $credentials->scopes);
        $this->assertSame(1000, $credentials->rateLimitPerHour);
        $this->assertTrue($credentials->isActive);
    }

    #[Test]
    public function it_creates_read_only_credentials(): void
    {
        $credentials = VendorApiCredentials::readOnly(
            vendorId: 'VND-456',
        );

        $this->assertSame('VND-456', $credentials->vendorId);
        $this->assertNotEmpty($credentials->apiKey);
        $this->assertNotEmpty($credentials->apiSecret);
        $this->assertContains('invoices:read', $credentials->scopes);
        $this->assertContains('purchase_orders:read', $credentials->scopes);
        $this->assertNotContains('invoices:write', $credentials->scopes);
    }

    #[Test]
    public function it_creates_full_access_credentials(): void
    {
        $credentials = VendorApiCredentials::fullAccess(
            vendorId: 'VND-789',
        );

        $this->assertContains('invoices:read', $credentials->scopes);
        $this->assertContains('invoices:write', $credentials->scopes);
        $this->assertContains('purchase_orders:read', $credentials->scopes);
        $this->assertContains('payments:read', $credentials->scopes);
        $this->assertSame(5000, $credentials->rateLimitPerHour);
    }

    #[Test]
    public function it_checks_scope_access(): void
    {
        $credentials = VendorApiCredentials::readOnly('VND-123');

        $this->assertTrue($credentials->hasScope('invoices:read'));
        $this->assertFalse($credentials->hasScope('invoices:write'));
    }

    #[Test]
    public function it_checks_rate_limit_status(): void
    {
        $credentials = new VendorApiCredentials(
            vendorId: 'VND-123',
            apiKey: 'key',
            apiSecret: 'secret',
            accessToken: null,
            tokenExpiresAt: null,
            scopes: [],
            rateLimitPerHour: 100,
            rateLimitPerDay: 1000,
            currentHourlyUsage: 90,
            currentDailyUsage: 500,
            isActive: true,
            createdAt: new \DateTimeImmutable(),
            expiresAt: new \DateTimeImmutable('+1 year'),
        );

        $this->assertFalse($credentials->isRateLimited());
        $this->assertSame(10, $credentials->getRemainingHourlyQuota());
        $this->assertSame(500, $credentials->getRemainingDailyQuota());
    }

    #[Test]
    public function it_detects_hourly_rate_limit_exceeded(): void
    {
        $credentials = new VendorApiCredentials(
            vendorId: 'VND-123',
            apiKey: 'key',
            apiSecret: 'secret',
            accessToken: null,
            tokenExpiresAt: null,
            scopes: [],
            rateLimitPerHour: 100,
            rateLimitPerDay: 1000,
            currentHourlyUsage: 100, // At limit
            currentDailyUsage: 500,
            isActive: true,
            createdAt: new \DateTimeImmutable(),
            expiresAt: new \DateTimeImmutable('+1 year'),
        );

        $this->assertTrue($credentials->isRateLimited());
        $this->assertSame(0, $credentials->getRemainingHourlyQuota());
    }

    #[Test]
    public function it_detects_daily_rate_limit_exceeded(): void
    {
        $credentials = new VendorApiCredentials(
            vendorId: 'VND-123',
            apiKey: 'key',
            apiSecret: 'secret',
            accessToken: null,
            tokenExpiresAt: null,
            scopes: [],
            rateLimitPerHour: 100,
            rateLimitPerDay: 1000,
            currentHourlyUsage: 50,
            currentDailyUsage: 1000, // At limit
            isActive: true,
            createdAt: new \DateTimeImmutable(),
            expiresAt: new \DateTimeImmutable('+1 year'),
        );

        $this->assertTrue($credentials->isRateLimited());
    }

    #[Test]
    public function it_checks_credential_validity(): void
    {
        $validCredentials = VendorApiCredentials::readOnly('VND-123');
        
        $expiredCredentials = new VendorApiCredentials(
            vendorId: 'VND-456',
            apiKey: 'key',
            apiSecret: 'secret',
            accessToken: null,
            tokenExpiresAt: null,
            scopes: [],
            rateLimitPerHour: 100,
            rateLimitPerDay: 1000,
            currentHourlyUsage: 0,
            currentDailyUsage: 0,
            isActive: true,
            createdAt: new \DateTimeImmutable('-2 years'),
            expiresAt: new \DateTimeImmutable('-1 year'), // Expired
        );

        $this->assertTrue($validCredentials->isValid());
        $this->assertFalse($expiredCredentials->isValid());
    }

    #[Test]
    public function it_checks_inactive_credentials(): void
    {
        $credentials = new VendorApiCredentials(
            vendorId: 'VND-123',
            apiKey: 'key',
            apiSecret: 'secret',
            accessToken: null,
            tokenExpiresAt: null,
            scopes: [],
            rateLimitPerHour: 100,
            rateLimitPerDay: 1000,
            currentHourlyUsage: 0,
            currentDailyUsage: 0,
            isActive: false, // Deactivated
            createdAt: new \DateTimeImmutable(),
            expiresAt: new \DateTimeImmutable('+1 year'),
        );

        $this->assertFalse($credentials->isValid());
    }

    #[Test]
    public function it_converts_to_array_without_secrets(): void
    {
        $credentials = VendorApiCredentials::readOnly('VND-123');

        $array = $credentials->toArray();

        $this->assertIsArray($array);
        $this->assertSame('VND-123', $array['vendor_id']);
        $this->assertArrayHasKey('api_key', $array);
        $this->assertArrayNotHasKey('api_secret', $array); // Secret not exposed
        $this->assertArrayHasKey('scopes', $array);
        $this->assertArrayHasKey('rate_limit_per_hour', $array);
    }

    #[Test]
    public function it_generates_unique_keys(): void
    {
        $credentials1 = VendorApiCredentials::readOnly('VND-001');
        $credentials2 = VendorApiCredentials::readOnly('VND-002');

        $this->assertNotSame($credentials1->apiKey, $credentials2->apiKey);
        $this->assertNotSame($credentials1->apiSecret, $credentials2->apiSecret);
    }
}
