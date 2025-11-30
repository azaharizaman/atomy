<?php

declare(strict_types=1);

namespace Nexus\Identity\ValueObjects;

use InvalidArgumentException;

/**
 * Public Key Credential Descriptor
 *
 * Describes a specific public key credential for WebAuthn operations.
 * Used in authentication to specify which credentials are acceptable.
 *
 * @see https://www.w3.org/TR/webauthn-2/#dictdef-publickeycredentialdescriptor
 */
final readonly class PublicKeyCredentialDescriptor
{
    /**
     * @param PublicKeyCredentialType $type Credential type (always "public-key")
     * @param string $id Credential ID (base64url-encoded)
     * @param array<string> $transports Authenticator transports (usb, nfc, ble, internal)
     *
     * @throws InvalidArgumentException If credential ID is empty or transports are invalid
     */
    public function __construct(
        public PublicKeyCredentialType $type,
        public string $id,
        public array $transports = []
    ) {
        if (empty($this->id)) {
            throw new InvalidArgumentException('Credential ID cannot be empty');
        }

        $validTransports = ['usb', 'nfc', 'ble', 'internal', 'hybrid'];
        foreach ($this->transports as $transport) {
            if (!in_array($transport, $validTransports, true)) {
                throw new InvalidArgumentException(
                    "Invalid transport '{$transport}'. Must be one of: " . implode(', ', $validTransports)
                );
            }
        }
    }

    /**
     * Create from credential ID and transports
     *
     * @param string $credentialId Base64url-encoded credential ID
     * @param array<string> $transports Transport methods
     * @return self
     */
    public static function create(string $credentialId, array $transports = []): self
    {
        return new self(
            type: PublicKeyCredentialType::PUBLIC_KEY,
            id: $credentialId,
            transports: $transports
        );
    }

    /**
     * Convert to WebAuthn API array format
     *
     * @return array{type: string, id: string, transports?: array<string>}
     */
    public function toArray(): array
    {
        $data = [
            'type' => $this->type->value,
            'id' => $this->id,
        ];

        if (!empty($this->transports)) {
            $data['transports'] = $this->transports;
        }

        return $data;
    }

    /**
     * Check if this descriptor supports USB transport
     */
    public function supportsUsb(): bool
    {
        return in_array('usb', $this->transports, true);
    }

    /**
     * Check if this descriptor supports NFC transport
     */
    public function supportsNfc(): bool
    {
        return in_array('nfc', $this->transports, true);
    }

    /**
     * Check if this descriptor supports BLE transport
     */
    public function supportsBle(): bool
    {
        return in_array('ble', $this->transports, true);
    }

    /**
     * Check if this descriptor supports internal transport (platform authenticator)
     */
    public function supportsInternal(): bool
    {
        return in_array('internal', $this->transports, true);
    }

    /**
     * Check if this descriptor supports hybrid transport (phone as authenticator)
     */
    public function supportsHybrid(): bool
    {
        return in_array('hybrid', $this->transports, true);
    }
}
