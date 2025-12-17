<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Tests\Unit\DTOs\Vendor;

use Nexus\ProcurementOperations\DTOs\Vendor\VendorPortalSession;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(VendorPortalSession::class)]
final class VendorPortalSessionTest extends TestCase
{
    #[Test]
    public function it_creates_portal_session(): void
    {
        $createdAt = new \DateTimeImmutable();
        $expiresAt = new \DateTimeImmutable('+8 hours');

        $session = new VendorPortalSession(
            sessionId: 'sess_abc123xyz789',
            vendorId: 'VND-123',
            userId: 'USR-456',
            permissions: ['invoices.view', 'invoices.create', 'purchase_orders.view'],
            ipAddress: '192.168.1.100',
            userAgent: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
            createdAt: $createdAt,
            expiresAt: $expiresAt,
            lastActivityAt: $createdAt,
            context: ['company_name' => 'Acme Corp'],
        );

        $this->assertSame('sess_abc123xyz789', $session->sessionId);
        $this->assertSame('VND-123', $session->vendorId);
        $this->assertSame('USR-456', $session->userId);
        $this->assertCount(3, $session->permissions);
        $this->assertSame('192.168.1.100', $session->ipAddress);
    }

    #[Test]
    public function it_creates_basic_session(): void
    {
        $session = VendorPortalSession::basic(
            vendorId: 'VND-123',
            userId: 'USR-456',
            ipAddress: '10.0.0.1',
        );

        $this->assertSame('VND-123', $session->vendorId);
        $this->assertSame('USR-456', $session->userId);
        $this->assertContains('invoices.view', $session->permissions);
        $this->assertContains('purchase_orders.view', $session->permissions);
        $this->assertNotContains('invoices.create', $session->permissions);
    }

    #[Test]
    public function it_creates_full_access_session(): void
    {
        $session = VendorPortalSession::fullAccess(
            vendorId: 'VND-789',
            userId: 'USR-123',
            ipAddress: '172.16.0.50',
        );

        $this->assertContains('invoices.view', $session->permissions);
        $this->assertContains('invoices.create', $session->permissions);
        $this->assertContains('invoices.submit', $session->permissions);
        $this->assertContains('purchase_orders.view', $session->permissions);
        $this->assertContains('quotes.submit', $session->permissions);
    }

    #[Test]
    public function it_checks_permission(): void
    {
        $session = VendorPortalSession::basic('VND-123', 'USR-456', '10.0.0.1');

        $this->assertTrue($session->hasPermission('invoices.view'));
        $this->assertFalse($session->hasPermission('invoices.create'));
    }

    #[Test]
    public function it_checks_if_session_is_valid(): void
    {
        $validSession = VendorPortalSession::basic('VND-123', 'USR-456', '10.0.0.1');

        $expiredSession = new VendorPortalSession(
            sessionId: 'sess_expired',
            vendorId: 'VND-123',
            userId: 'USR-456',
            permissions: [],
            ipAddress: '10.0.0.1',
            userAgent: null,
            createdAt: new \DateTimeImmutable('-10 hours'),
            expiresAt: new \DateTimeImmutable('-2 hours'), // Expired
            lastActivityAt: new \DateTimeImmutable('-3 hours'),
            context: [],
        );

        $this->assertTrue($validSession->isValid());
        $this->assertFalse($expiredSession->isValid());
    }

    #[Test]
    public function it_checks_if_session_is_expired(): void
    {
        $expiredSession = new VendorPortalSession(
            sessionId: 'sess_old',
            vendorId: 'VND-123',
            userId: 'USR-456',
            permissions: [],
            ipAddress: '10.0.0.1',
            userAgent: null,
            createdAt: new \DateTimeImmutable('-24 hours'),
            expiresAt: new \DateTimeImmutable('-1 hour'),
            lastActivityAt: new \DateTimeImmutable('-2 hours'),
            context: [],
        );

        $this->assertTrue($expiredSession->isExpired());
    }

    #[Test]
    public function it_calculates_time_until_expiry(): void
    {
        $session = new VendorPortalSession(
            sessionId: 'sess_test',
            vendorId: 'VND-123',
            userId: 'USR-456',
            permissions: [],
            ipAddress: '10.0.0.1',
            userAgent: null,
            createdAt: new \DateTimeImmutable(),
            expiresAt: new \DateTimeImmutable('+2 hours'),
            lastActivityAt: new \DateTimeImmutable(),
            context: [],
        );

        $minutesUntilExpiry = $session->getMinutesUntilExpiry();

        $this->assertGreaterThan(115, $minutesUntilExpiry);
        $this->assertLessThanOrEqual(120, $minutesUntilExpiry);
    }

    #[Test]
    public function it_gets_context_value(): void
    {
        $session = new VendorPortalSession(
            sessionId: 'sess_test',
            vendorId: 'VND-123',
            userId: 'USR-456',
            permissions: [],
            ipAddress: '10.0.0.1',
            userAgent: null,
            createdAt: new \DateTimeImmutable(),
            expiresAt: new \DateTimeImmutable('+8 hours'),
            lastActivityAt: new \DateTimeImmutable(),
            context: [
                'company_name' => 'Acme Corp',
                'preferred_language' => 'en',
            ],
        );

        $this->assertSame('Acme Corp', $session->getContextValue('company_name'));
        $this->assertSame('en', $session->getContextValue('preferred_language'));
        $this->assertNull($session->getContextValue('non_existent'));
        $this->assertSame('default', $session->getContextValue('non_existent', 'default'));
    }

    #[Test]
    public function it_checks_ip_match(): void
    {
        $session = VendorPortalSession::basic('VND-123', 'USR-456', '192.168.1.100');

        $this->assertTrue($session->matchesIp('192.168.1.100'));
        $this->assertFalse($session->matchesIp('10.0.0.1'));
    }

    #[Test]
    public function it_converts_to_array(): void
    {
        $session = VendorPortalSession::basic('VND-123', 'USR-456', '10.0.0.1');

        $array = $session->toArray();

        $this->assertIsArray($array);
        $this->assertSame('VND-123', $array['vendor_id']);
        $this->assertSame('USR-456', $array['user_id']);
        $this->assertArrayHasKey('session_id', $array);
        $this->assertArrayHasKey('permissions', $array);
        $this->assertArrayHasKey('expires_at', $array);
        $this->assertArrayHasKey('is_valid', $array);
    }

    #[Test]
    public function it_generates_unique_session_ids(): void
    {
        $session1 = VendorPortalSession::basic('VND-001', 'USR-001', '10.0.0.1');
        $session2 = VendorPortalSession::basic('VND-002', 'USR-002', '10.0.0.2');

        $this->assertNotSame($session1->sessionId, $session2->sessionId);
    }

    #[Test]
    public function it_has_correct_default_expiry(): void
    {
        $beforeCreate = new \DateTimeImmutable();
        $session = VendorPortalSession::basic('VND-123', 'USR-456', '10.0.0.1');
        $afterCreate = new \DateTimeImmutable();

        // Default expiry should be 8 hours
        $expectedExpiry = $beforeCreate->modify('+8 hours');
        $maxExpiry = $afterCreate->modify('+8 hours');

        $this->assertGreaterThanOrEqual($expectedExpiry, $session->expiresAt);
        $this->assertLessThanOrEqual($maxExpiry, $session->expiresAt);
    }
}
