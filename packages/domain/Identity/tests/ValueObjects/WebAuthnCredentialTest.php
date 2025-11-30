<?php

declare(strict_types=1);

namespace Nexus\Domain\Identity\Tests\ValueObjects;

use DateTimeImmutable;
use InvalidArgumentException;
use Nexus\Domain\Identity\ValueObjects\WebAuthnCredential;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

#[CoversClass(WebAuthnCredential::class)]
#[Group('identity')]
#[Group('mfa')]
#[Group('value-objects')]
final class WebAuthnCredentialTest extends TestCase
{
    #[Test]
    public function it_creates_valid_webauthn_credential(): void
    {
        $credential = new WebAuthnCredential(
            credentialId: 'ABCDEFGHIJKLMNOPQRSTUV',
            publicKey: 'dGVzdHB1YmxpY2tleWRhdGF0aGF0aXNsb25nZW5vdWdo',
            signCount: 0,
            transports: ['usb', 'nfc']
        );

        $this->assertSame('ABCDEFGHIJKLMNOPQRSTUV', $credential->credentialId);
        $this->assertSame('dGVzdHB1YmxpY2tleWRhdGF0aGF0aXNsb25nZW5vdWdo', $credential->publicKey);
        $this->assertSame(0, $credential->signCount);
        $this->assertSame(['usb', 'nfc'], $credential->transports);
        $this->assertNull($credential->aaguid);
        $this->assertNull($credential->lastUsedDeviceFingerprint);
        $this->assertNull($credential->friendlyName);
        $this->assertNull($credential->lastUsedAt);
    }

    #[Test]
    public function it_creates_credential_with_all_optional_fields(): void
    {
        $lastUsed = new DateTimeImmutable('2024-01-15 10:30:00');
        
        $credential = new WebAuthnCredential(
            credentialId: 'ABCDEFGHIJKLMNOPQRSTUV',
            publicKey: 'dGVzdHB1YmxpY2tleWRhdGF0aGF0aXNsb25nZW5vdWdo',
            signCount: 5,
            transports: ['internal'],
            aaguid: '00000000-0000-0000-0000-000000000000',
            lastUsedDeviceFingerprint: 'fingerprint123',
            friendlyName: 'My MacBook Touch ID',
            lastUsedAt: $lastUsed
        );

        $this->assertSame('00000000-0000-0000-0000-000000000000', $credential->aaguid);
        $this->assertSame('fingerprint123', $credential->lastUsedDeviceFingerprint);
        $this->assertSame('My MacBook Touch ID', $credential->friendlyName);
        $this->assertSame($lastUsed, $credential->lastUsedAt);
    }

    #[Test]
    public function it_rejects_empty_credential_id(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Credential ID cannot be empty');

        new WebAuthnCredential(
            credentialId: '',
            publicKey: 'dGVzdHB1YmxpY2tleWRhdGF0aGF0aXNsb25nZW5vdWdo',
            signCount: 0,
            transports: ['usb']
        );
    }

    #[Test]
    #[DataProvider('invalidCredentialIdProvider')]
    public function it_rejects_invalid_credential_id_format(string $invalidId): void
    {
        $this->expectException(InvalidArgumentException::class);

        new WebAuthnCredential(
            credentialId: $invalidId,
            publicKey: 'dGVzdHB1YmxpY2tleWRhdGF0aGF0aXNsb25nZW5vdWdo',
            signCount: 0,
            transports: ['usb']
        );
    }

    public static function invalidCredentialIdProvider(): array
    {
        return [
            'Contains invalid chars' => ['ABCD+/=='],  // Base64 instead of Base64URL
            'Too short' => ['ABCD'],  // Less than 22 chars
            'Contains spaces' => ['ABCD EFGH IJKL MNOP QR'],
        ];
    }

    #[Test]
    public function it_rejects_empty_public_key(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Public key cannot be empty');

        new WebAuthnCredential(
            credentialId: 'ABCDEFGHIJKLMNOPQRSTUV',
            publicKey: '',
            signCount: 0,
            transports: ['usb']
        );
    }

    #[Test]
    #[DataProvider('invalidPublicKeyProvider')]
    public function it_rejects_invalid_public_key_format(string $invalidKey): void
    {
        $this->expectException(InvalidArgumentException::class);

        new WebAuthnCredential(
            credentialId: 'ABCDEFGHIJKLMNOPQRSTUV',
            publicKey: $invalidKey,
            signCount: 0,
            transports: ['usb']
        );
    }

    public static function invalidPublicKeyProvider(): array
    {
        return [
            'Contains invalid chars' => ['invalid_base64_chars!@#$'],
            'Too short' => ['abc'],  // Less than 44 chars
        ];
    }

    #[Test]
    public function it_rejects_negative_sign_count(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Sign count cannot be negative');

        new WebAuthnCredential(
            credentialId: 'ABCDEFGHIJKLMNOPQRSTUV',
            publicKey: 'dGVzdHB1YmxpY2tleWRhdGF0aGF0aXNsb25nZW5vdWdo',
            signCount: -1,
            transports: ['usb']
        );
    }

    #[Test]
    #[DataProvider('invalidTransportProvider')]
    public function it_rejects_invalid_transport(array $invalidTransports): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid transport');

        new WebAuthnCredential(
            credentialId: 'ABCDEFGHIJKLMNOPQRSTUV',
            publicKey: 'dGVzdHB1YmxpY2tleWRhdGF0aGF0aXNsb25nZW5vdWdo',
            signCount: 0,
            transports: $invalidTransports
        );
    }

    public static function invalidTransportProvider(): array
    {
        return [
            'Invalid transport name' => [['bluetooth']],  // Should be 'ble'
            'Unknown transport' => [['wifi']],
            'Mixed valid and invalid' => [['usb', 'invalid']],
        ];
    }

    #[Test]
    #[DataProvider('validTransportProvider')]
    public function it_accepts_valid_transports(array $validTransports): void
    {
        $credential = new WebAuthnCredential(
            credentialId: 'ABCDEFGHIJKLMNOPQRSTUV',
            publicKey: 'dGVzdHB1YmxpY2tleWRhdGF0aGF0aXNsb25nZW5vdWdo',
            signCount: 0,
            transports: $validTransports
        );

        $this->assertSame($validTransports, $credential->transports);
    }

    public static function validTransportProvider(): array
    {
        return [
            'USB only' => [['usb']],
            'NFC only' => [['nfc']],
            'BLE only' => [['ble']],
            'Internal only' => [['internal']],
            'Hybrid only' => [['hybrid']],
            'Multiple transports' => [['usb', 'nfc', 'ble']],
            'All transports' => [['usb', 'nfc', 'ble', 'internal', 'hybrid']],
        ];
    }

    #[Test]
    #[DataProvider('invalidAaguidProvider')]
    public function it_rejects_invalid_aaguid(string $invalidAaguid): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('AAGUID must be a valid UUID format');

        new WebAuthnCredential(
            credentialId: 'ABCDEFGHIJKLMNOPQRSTUV',
            publicKey: 'dGVzdHB1YmxpY2tleWRhdGF0aGF0aXNsb25nZW5vdWdo',
            signCount: 0,
            transports: ['usb'],
            aaguid: $invalidAaguid
        );
    }

    public static function invalidAaguidProvider(): array
    {
        return [
            'Too short' => ['12345'],
            'Invalid format' => ['not-a-uuid'],
            'Missing hyphens' => ['000000000000000000000000000000000'],
        ];
    }

    #[Test]
    #[DataProvider('validAaguidProvider')]
    public function it_accepts_valid_aaguid(string $validAaguid): void
    {
        $credential = new WebAuthnCredential(
            credentialId: 'ABCDEFGHIJKLMNOPQRSTUV',
            publicKey: 'dGVzdHB1YmxpY2tleWRhdGF0aGF0aXNsb25nZW5vdWdo',
            signCount: 0,
            transports: ['usb'],
            aaguid: $validAaguid
        );

        $this->assertSame($validAaguid, $credential->aaguid);
    }

    public static function validAaguidProvider(): array
    {
        return [
            'Standard UUID' => ['00000000-0000-0000-0000-000000000000'],
            'Random UUID' => ['123e4567-e89b-12d3-a456-426614174000'],
            'Compact format' => ['00000000000000000000000000000000'],
        ];
    }

    #[Test]
    #[DataProvider('invalidFriendlyNameProvider')]
    public function it_rejects_invalid_friendly_name(string $invalidName): void
    {
        $this->expectException(InvalidArgumentException::class);

        new WebAuthnCredential(
            credentialId: 'ABCDEFGHIJKLMNOPQRSTUV',
            publicKey: 'dGVzdHB1YmxpY2tleWRhdGF0aGF0aXNsb25nZW5vdWdo',
            signCount: 0,
            transports: ['usb'],
            friendlyName: $invalidName
        );
    }

    public static function invalidFriendlyNameProvider(): array
    {
        return [
            'Empty string' => [''],
            'Whitespace only' => ['   '],
            'Too long' => [str_repeat('a', 101)],
        ];
    }

    #[Test]
    public function it_detects_sign_count_rollback(): void
    {
        $credential = new WebAuthnCredential(
            credentialId: 'ABCDEFGHIJKLMNOPQRSTUV',
            publicKey: 'dGVzdHB1YmxpY2tleWRhdGF0aGF0aXNsb25nZW5vdWdo',
            signCount: 10,
            transports: ['usb']
        );

        $this->assertTrue($credential->detectSignCountRollback(5));  // Rollback!
        $this->assertFalse($credential->detectSignCountRollback(10));  // Same count
        $this->assertFalse($credential->detectSignCountRollback(15));  // Normal increment
    }

    #[Test]
    public function it_allows_zero_sign_count_for_unsupported_authenticators(): void
    {
        $credential = new WebAuthnCredential(
            credentialId: 'ABCDEFGHIJKLMNOPQRSTUV',
            publicKey: 'dGVzdHB1YmxpY2tleWRhdGF0aGF0aXNsb25nZW5vdWdo',
            signCount: 0,
            transports: ['usb']
        );

        // Both 0 means authenticator doesn't support counters
        $this->assertFalse($credential->detectSignCountRollback(0));
    }

    #[Test]
    public function it_updates_after_successful_authentication(): void
    {
        $credential = new WebAuthnCredential(
            credentialId: 'ABCDEFGHIJKLMNOPQRSTUV',
            publicKey: 'dGVzdHB1YmxpY2tleWRhdGF0aGF0aXNsb25nZW5vdWdo',
            signCount: 5,
            transports: ['usb']
        );

        $timestamp = new DateTimeImmutable('2024-01-15 10:30:00');
        $updated = $credential->updateAfterAuthentication(
            newSignCount: 6,
            deviceFingerprint: 'fingerprint123',
            timestamp: $timestamp
        );

        $this->assertSame(5, $credential->signCount);  // Original unchanged
        $this->assertSame(6, $updated->signCount);
        $this->assertSame('fingerprint123', $updated->lastUsedDeviceFingerprint);
        $this->assertSame($timestamp, $updated->lastUsedAt);
    }

    #[Test]
    public function it_rejects_update_with_sign_count_rollback(): void
    {
        $credential = new WebAuthnCredential(
            credentialId: 'ABCDEFGHIJKLMNOPQRSTUV',
            publicKey: 'dGVzdHB1YmxpY2tleWRhdGF0aGF0aXNsb25nZW5vdWdo',
            signCount: 10,
            transports: ['usb']
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Sign count rollback detected');

        $credential->updateAfterAuthentication(newSignCount: 5);
    }

    #[Test]
    public function it_updates_friendly_name(): void
    {
        $credential = new WebAuthnCredential(
            credentialId: 'ABCDEFGHIJKLMNOPQRSTUV',
            publicKey: 'dGVzdHB1YmxpY2tleWRhdGF0aGF0aXNsb25nZW5vdWdo',
            signCount: 0,
            transports: ['usb']
        );

        $updated = $credential->withFriendlyName('My YubiKey');

        $this->assertNull($credential->friendlyName);  // Original unchanged
        $this->assertSame('My YubiKey', $updated->friendlyName);
        $this->assertSame($credential->credentialId, $updated->credentialId);  // Other fields preserved
    }

    #[Test]
    public function it_checks_transport_support(): void
    {
        $credential = new WebAuthnCredential(
            credentialId: 'ABCDEFGHIJKLMNOPQRSTUV',
            publicKey: 'dGVzdHB1YmxpY2tleWRhdGF0aGF0aXNsb25nZW5vdWdo',
            signCount: 0,
            transports: ['usb', 'nfc']
        );

        $this->assertTrue($credential->supportsTransport('usb'));
        $this->assertTrue($credential->supportsTransport('nfc'));
        $this->assertFalse($credential->supportsTransport('ble'));
        $this->assertFalse($credential->supportsTransport('internal'));
    }

    #[Test]
    public function it_identifies_platform_authenticator(): void
    {
        $credential = new WebAuthnCredential(
            credentialId: 'ABCDEFGHIJKLMNOPQRSTUV',
            publicKey: 'dGVzdHB1YmxpY2tleWRhdGF0aGF0aXNsb25nZW5vdWdo',
            signCount: 0,
            transports: ['internal']
        );

        $this->assertTrue($credential->isPlatformAuthenticator());
        $this->assertFalse($credential->isRoamingAuthenticator());
    }

    #[Test]
    public function it_identifies_roaming_authenticator(): void
    {
        $credential = new WebAuthnCredential(
            credentialId: 'ABCDEFGHIJKLMNOPQRSTUV',
            publicKey: 'dGVzdHB1YmxpY2tleWRhdGF0aGF0aXNsb25nZW5vdWdo',
            signCount: 0,
            transports: ['usb', 'nfc']
        );

        $this->assertTrue($credential->isRoamingAuthenticator());
        $this->assertFalse($credential->isPlatformAuthenticator());
    }

    #[Test]
    public function it_returns_friendly_name_as_display_name(): void
    {
        $credential = new WebAuthnCredential(
            credentialId: 'ABCDEFGHIJKLMNOPQRSTUV',
            publicKey: 'dGVzdHB1YmxpY2tleWRhdGF0aGF0aXNsb25nZW5vdWdo',
            signCount: 0,
            transports: ['usb'],
            friendlyName: 'My YubiKey 5'
        );

        $this->assertSame('My YubiKey 5', $credential->getDisplayName());
    }

    #[Test]
    public function it_generates_display_name_for_platform_authenticator(): void
    {
        $credential = new WebAuthnCredential(
            credentialId: 'ABCDEFGHIJKLMNOPQRSTUV',
            publicKey: 'dGVzdHB1YmxpY2tleWRhdGF0aGF0aXNsb25nZW5vdWdo',
            signCount: 0,
            transports: ['internal']
        );

        $this->assertSame('Platform Authenticator', $credential->getDisplayName());
    }

    #[Test]
    public function it_generates_display_name_for_security_key(): void
    {
        $credential = new WebAuthnCredential(
            credentialId: 'ABCDEFGHIJKLMNOPQRSTUV',
            publicKey: 'dGVzdHB1YmxpY2tleWRhdGF0aGF0aXNsb25nZW5vdWdo',
            signCount: 0,
            transports: ['usb']
        );

        $this->assertSame('Security Key', $credential->getDisplayName());
    }

    #[Test]
    public function it_includes_last_used_date_in_display_name(): void
    {
        $credential = new WebAuthnCredential(
            credentialId: 'ABCDEFGHIJKLMNOPQRSTUV',
            publicKey: 'dGVzdHB1YmxpY2tleWRhdGF0aGF0aXNsb25nZW5vdWdo',
            signCount: 0,
            transports: ['usb'],
            lastUsedAt: new DateTimeImmutable('2024-01-15')
        );

        $this->assertSame('Security Key (last used 2024-01-15)', $credential->getDisplayName());
    }

    #[Test]
    public function it_converts_to_array(): void
    {
        $lastUsed = new DateTimeImmutable('2024-01-15 10:30:00');
        
        $credential = new WebAuthnCredential(
            credentialId: 'ABCDEFGHIJKLMNOPQRSTUV',
            publicKey: 'dGVzdHB1YmxpY2tleWRhdGF0aGF0aXNsb25nZW5vdWdo',
            signCount: 5,
            transports: ['usb', 'nfc'],
            aaguid: '00000000-0000-0000-0000-000000000000',
            lastUsedDeviceFingerprint: 'fingerprint123',
            friendlyName: 'My YubiKey',
            lastUsedAt: $lastUsed
        );

        $array = $credential->toArray();

        $this->assertSame([
            'credential_id' => 'ABCDEFGHIJKLMNOPQRSTUV',
            'public_key' => 'dGVzdHB1YmxpY2tleWRhdGF0aGF0aXNsb25nZW5vdWdo',
            'sign_count' => 5,
            'transports' => ['usb', 'nfc'],
            'aaguid' => '00000000-0000-0000-0000-000000000000',
            'last_used_device_fingerprint' => 'fingerprint123',
            'friendly_name' => 'My YubiKey',
            'last_used_at' => '2024-01-15 10:30:00',
        ], $array);
    }

    #[Test]
    public function it_is_immutable(): void
    {
        $credential = new WebAuthnCredential(
            credentialId: 'ABCDEFGHIJKLMNOPQRSTUV',
            publicKey: 'dGVzdHB1YmxpY2tleWRhdGF0aGF0aXNsb25nZW5vdWdo',
            signCount: 0,
            transports: ['usb']
        );
        
        $reflection = new ReflectionClass($credential);
        
        // Verify all properties are readonly
        foreach ($reflection->getProperties() as $property) {
            $this->assertTrue(
                $property->isReadOnly(),
                "Property {$property->getName()} should be readonly"
            );
        }
    }
}
