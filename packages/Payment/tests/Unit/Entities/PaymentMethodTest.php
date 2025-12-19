<?php

declare(strict_types=1);

namespace Nexus\Payment\Tests\Unit\Entities;

use Nexus\Payment\Entities\PaymentMethod;
use Nexus\Payment\Enums\PaymentMethodType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(PaymentMethod::class)]
final class PaymentMethodTest extends TestCase
{
    #[Test]
    public function it_can_be_created_via_constructor(): void
    {
        $createdAt = new \DateTimeImmutable('2024-01-15 10:00:00');

        $method = new PaymentMethod(
            id: 'pm-001',
            tenantId: 'tenant-001',
            partyId: 'party-001',
            type: PaymentMethodType::CARD,
            displayLabel: 'Visa •••• 4242',
            last4: '4242',
            fingerprint: 'fp-abc123',
            expiresAt: new \DateTimeImmutable('2025-12-31'),
            providerToken: 'tok_123',
            supportsRecurring: true,
            createdAt: $createdAt,
            isDefault: true,
            isActive: true,
            isVerified: true,
        );

        $this->assertSame('pm-001', $method->getId());
        $this->assertSame('tenant-001', $method->getTenantId());
        $this->assertSame('party-001', $method->getPartyId());
        $this->assertSame(PaymentMethodType::CARD, $method->getType());
        $this->assertSame('Visa •••• 4242', $method->getDisplayLabel());
        $this->assertSame('4242', $method->getLast4());
        $this->assertSame('fp-abc123', $method->getFingerprint());
        $this->assertSame('tok_123', $method->getProviderToken());
        $this->assertTrue($method->supportsRecurring());
        $this->assertTrue($method->isDefault());
        $this->assertTrue($method->isActive());
        $this->assertTrue($method->isVerified());
    }

    #[Test]
    public function it_can_be_created_via_factory_method(): void
    {
        $method = PaymentMethod::create(
            id: 'pm-002',
            tenantId: 'tenant-001',
            partyId: 'party-001',
            type: PaymentMethodType::BANK_ACCOUNT,
            displayLabel: 'Bank Account •••• 1234',
            options: [
                'last4' => '1234',
                'fingerprint' => 'fp-xyz',
                'supports_recurring' => true,
                'is_default' => false,
                'metadata' => ['bank_name' => 'Acme Bank'],
            ],
        );

        $this->assertSame('pm-002', $method->getId());
        $this->assertSame('1234', $method->getLast4());
        $this->assertFalse($method->isDefault());
        $this->assertTrue($method->isActive());
        $this->assertFalse($method->isVerified());
        $this->assertSame('Acme Bank', $method->getMetadata()['bank_name']);
    }

    #[Test]
    public function it_defaults_to_active_and_not_verified(): void
    {
        $method = PaymentMethod::create(
            id: 'pm-003',
            tenantId: 'tenant-001',
            partyId: 'party-001',
            type: PaymentMethodType::CARD,
            displayLabel: 'Card',
        );

        $this->assertTrue($method->isActive());
        $this->assertFalse($method->isVerified());
        $this->assertFalse($method->isDefault());
    }

    #[Test]
    public function it_can_set_and_clear_default(): void
    {
        $method = PaymentMethod::create(
            id: 'pm-004',
            tenantId: 'tenant-001',
            partyId: 'party-001',
            type: PaymentMethodType::CARD,
            displayLabel: 'Card',
        );

        $this->assertFalse($method->isDefault());

        $method->setAsDefault();
        $this->assertTrue($method->isDefault());

        $method->clearDefault();
        $this->assertFalse($method->isDefault());
    }

    #[Test]
    public function it_can_activate_and_deactivate(): void
    {
        $method = PaymentMethod::create(
            id: 'pm-005',
            tenantId: 'tenant-001',
            partyId: 'party-001',
            type: PaymentMethodType::CARD,
            displayLabel: 'Card',
        );

        $this->assertTrue($method->isActive());

        $method->deactivate();
        $this->assertFalse($method->isActive());

        $method->activate();
        $this->assertTrue($method->isActive());
    }

    #[Test]
    public function it_can_verify(): void
    {
        $method = PaymentMethod::create(
            id: 'pm-006',
            tenantId: 'tenant-001',
            partyId: 'party-001',
            type: PaymentMethodType::CARD,
            displayLabel: 'Card',
        );

        $this->assertFalse($method->isVerified());

        $method->verify();
        $this->assertTrue($method->isVerified());
    }

    #[Test]
    public function it_checks_expiry_status(): void
    {
        $futureExpiry = new \DateTimeImmutable('+1 year');
        $pastExpiry = new \DateTimeImmutable('-1 day');

        $validMethod = PaymentMethod::create(
            id: 'pm-007',
            tenantId: 'tenant-001',
            partyId: 'party-001',
            type: PaymentMethodType::CARD,
            displayLabel: 'Card',
            options: ['expires_at' => $futureExpiry],
        );

        $expiredMethod = PaymentMethod::create(
            id: 'pm-008',
            tenantId: 'tenant-001',
            partyId: 'party-001',
            type: PaymentMethodType::CARD,
            displayLabel: 'Card',
            options: ['expires_at' => $pastExpiry],
        );

        $noExpiryMethod = PaymentMethod::create(
            id: 'pm-009',
            tenantId: 'tenant-001',
            partyId: 'party-001',
            type: PaymentMethodType::BANK_ACCOUNT,
            displayLabel: 'Bank Account',
        );

        $this->assertFalse($validMethod->isExpired());
        $this->assertTrue($expiredMethod->isExpired());
        $this->assertFalse($noExpiryMethod->isExpired());
    }

    #[Test]
    public function it_checks_if_can_be_used(): void
    {
        $activeNotExpired = PaymentMethod::create(
            id: 'pm-010',
            tenantId: 'tenant-001',
            partyId: 'party-001',
            type: PaymentMethodType::CARD,
            displayLabel: 'Card',
            options: ['expires_at' => new \DateTimeImmutable('+1 year')],
        );

        $this->assertTrue($activeNotExpired->canBeUsed());

        // Deactivated cannot be used
        $activeNotExpired->deactivate();
        $this->assertFalse($activeNotExpired->canBeUsed());

        // Expired cannot be used
        $expired = PaymentMethod::create(
            id: 'pm-011',
            tenantId: 'tenant-001',
            partyId: 'party-001',
            type: PaymentMethodType::CARD,
            displayLabel: 'Card',
            options: ['expires_at' => new \DateTimeImmutable('-1 day')],
        );
        $this->assertFalse($expired->canBeUsed());
    }

    #[Test]
    public function it_updates_last_used_timestamp(): void
    {
        $method = PaymentMethod::create(
            id: 'pm-012',
            tenantId: 'tenant-001',
            partyId: 'party-001',
            type: PaymentMethodType::CARD,
            displayLabel: 'Card',
        );

        $this->assertNull($method->getLastUsedAt());

        $usedAt = new \DateTimeImmutable('2024-01-15 12:00:00');
        $method->updateLastUsed($usedAt);

        $this->assertSame($usedAt, $method->getLastUsedAt());
    }

    #[Test]
    public function it_can_add_metadata(): void
    {
        $method = PaymentMethod::create(
            id: 'pm-013',
            tenantId: 'tenant-001',
            partyId: 'party-001',
            type: PaymentMethodType::CARD,
            displayLabel: 'Card',
        );

        $method->addMetadata(['key1' => 'value1']);
        $method->addMetadata(['key2' => 'value2']);

        $metadata = $method->getMetadata();
        $this->assertSame('value1', $metadata['key1']);
        $this->assertSame('value2', $metadata['key2']);
    }

    #[Test]
    public function it_returns_expiry_date(): void
    {
        $expiresAt = new \DateTimeImmutable('2025-12-31');

        $method = PaymentMethod::create(
            id: 'pm-014',
            tenantId: 'tenant-001',
            partyId: 'party-001',
            type: PaymentMethodType::CARD,
            displayLabel: 'Card',
            options: ['expires_at' => $expiresAt],
        );

        $this->assertSame($expiresAt, $method->getExpiresAt());
    }
}
