<?php

declare(strict_types=1);

namespace Nexus\Identity\ValueObjects;

/**
 * Authenticator attachment modality
 *
 * Defines how the authenticator is attached to the client device.
 *
 * @see https://www.w3.org/TR/webauthn-2/#enum-attachment
 */
enum AuthenticatorAttachment: string
{
    /**
     * Platform authenticator (built into the device)
     *
     * Examples: Touch ID, Face ID, Windows Hello
     */
    case PLATFORM = 'platform';

    /**
     * Cross-platform authenticator (removable/roaming)
     *
     * Examples: YubiKey, Security Key, NFC devices
     */
    case CROSS_PLATFORM = 'cross-platform';

    /**
     * Get human-readable description
     */
    public function description(): string
    {
        return match ($this) {
            self::PLATFORM => 'Built-in authenticator (Touch ID, Face ID, Windows Hello)',
            self::CROSS_PLATFORM => 'External security key (YubiKey, USB key)',
        };
    }

    /**
     * Check if this is a platform authenticator
     */
    public function isPlatform(): bool
    {
        return $this === self::PLATFORM;
    }

    /**
     * Check if this is a cross-platform authenticator
     */
    public function isCrossPlatform(): bool
    {
        return $this === self::CROSS_PLATFORM;
    }
}
