<?php

declare(strict_types=1);

namespace Nexus\Identity\Tests\ValueObjects;

use InvalidArgumentException;
use Nexus\Identity\ValueObjects\PublicKeyCredentialDescriptor;
use Nexus\Identity\ValueObjects\PublicKeyCredentialType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(PublicKeyCredentialDescriptor::class)]
final class PublicKeyCredentialDescriptorTest extends TestCase
{
    #[Test]
    public function it_can_be_created_with_minimal_data(): void
    {
        $descriptor = new PublicKeyCredentialDescriptor(
            type: PublicKeyCredentialType::PUBLIC_KEY,
            id: 'credential-123'
        );

        $this->assertSame(PublicKeyCredentialType::PUBLIC_KEY, $descriptor->type);
        $this->assertSame('credential-123', $descriptor->id);
        $this->assertEmpty($descriptor->transports);
    }

    #[Test]
    public function it_can_be_created_with_transports(): void
    {
        $descriptor = new PublicKeyCredentialDescriptor(
            type: PublicKeyCredentialType::PUBLIC_KEY,
            id: 'credential-123',
            transports: ['usb', 'nfc']
        );

        $this->assertSame(['usb', 'nfc'], $descriptor->transports);
    }

    #[Test]
    public function it_throws_exception_for_empty_credential_id(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Credential ID cannot be empty');

        new PublicKeyCredentialDescriptor(
            type: PublicKeyCredentialType::PUBLIC_KEY,
            id: ''
        );
    }

    #[Test]
    public function it_throws_exception_for_invalid_transport(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid transport 'invalid'");

        new PublicKeyCredentialDescriptor(
            type: PublicKeyCredentialType::PUBLIC_KEY,
            id: 'credential-123',
            transports: ['usb', 'invalid']
        );
    }

    #[Test]
    public function it_accepts_all_valid_transports(): void
    {
        $descriptor = new PublicKeyCredentialDescriptor(
            type: PublicKeyCredentialType::PUBLIC_KEY,
            id: 'credential-123',
            transports: ['usb', 'nfc', 'ble', 'internal', 'hybrid']
        );

        $this->assertCount(5, $descriptor->transports);
    }

    #[Test]
    public function it_can_be_created_via_factory_method(): void
    {
        $descriptor = PublicKeyCredentialDescriptor::create('cred-456', ['usb']);

        $this->assertSame(PublicKeyCredentialType::PUBLIC_KEY, $descriptor->type);
        $this->assertSame('cred-456', $descriptor->id);
        $this->assertSame(['usb'], $descriptor->transports);
    }

    #[Test]
    public function it_converts_to_array_without_transports(): void
    {
        $descriptor = PublicKeyCredentialDescriptor::create('cred-789');

        $array = $descriptor->toArray();

        $this->assertSame([
            'type' => 'public-key',
            'id' => 'cred-789',
        ], $array);
    }

    #[Test]
    public function it_converts_to_array_with_transports(): void
    {
        $descriptor = PublicKeyCredentialDescriptor::create('cred-101', ['usb', 'nfc']);

        $array = $descriptor->toArray();

        $this->assertSame([
            'type' => 'public-key',
            'id' => 'cred-101',
            'transports' => ['usb', 'nfc'],
        ], $array);
    }

    #[Test]
    public function it_checks_usb_support(): void
    {
        $withUsb = PublicKeyCredentialDescriptor::create('cred-1', ['usb', 'nfc']);
        $withoutUsb = PublicKeyCredentialDescriptor::create('cred-2', ['nfc']);

        $this->assertTrue($withUsb->supportsUsb());
        $this->assertFalse($withoutUsb->supportsUsb());
    }

    #[Test]
    public function it_checks_nfc_support(): void
    {
        $withNfc = PublicKeyCredentialDescriptor::create('cred-1', ['usb', 'nfc']);
        $withoutNfc = PublicKeyCredentialDescriptor::create('cred-2', ['usb']);

        $this->assertTrue($withNfc->supportsNfc());
        $this->assertFalse($withoutNfc->supportsNfc());
    }

    #[Test]
    public function it_checks_ble_support(): void
    {
        $withBle = PublicKeyCredentialDescriptor::create('cred-1', ['ble']);
        $withoutBle = PublicKeyCredentialDescriptor::create('cred-2', ['usb']);

        $this->assertTrue($withBle->supportsBle());
        $this->assertFalse($withoutBle->supportsBle());
    }

    #[Test]
    public function it_checks_internal_support(): void
    {
        $withInternal = PublicKeyCredentialDescriptor::create('cred-1', ['internal']);
        $withoutInternal = PublicKeyCredentialDescriptor::create('cred-2', ['usb']);

        $this->assertTrue($withInternal->supportsInternal());
        $this->assertFalse($withoutInternal->supportsInternal());
    }

    #[Test]
    public function it_checks_hybrid_support(): void
    {
        $withHybrid = PublicKeyCredentialDescriptor::create('cred-1', ['hybrid']);
        $withoutHybrid = PublicKeyCredentialDescriptor::create('cred-2', ['usb']);

        $this->assertTrue($withHybrid->supportsHybrid());
        $this->assertFalse($withoutHybrid->supportsHybrid());
    }

    #[Test]
    public function it_is_immutable(): void
    {
        $descriptor = PublicKeyCredentialDescriptor::create('cred-123', ['usb']);

        $this->assertSame('cred-123', $descriptor->id);
        $this->assertSame(['usb'], $descriptor->transports);
    }
}
