<?php

declare(strict_types=1);

namespace Nexus\Domain\Identity\ValueObjects;

/**
 * Public key credential type
 *
 * Currently only "public-key" is defined by WebAuthn spec.
 *
 * @see https://www.w3.org/TR/webauthn-2/#enumdef-publickeycredentialtype
 */
enum PublicKeyCredentialType: string
{
    /**
     * Public key credential type
     *
     * The only type currently defined by the WebAuthn specification
     */
    case PUBLIC_KEY = 'public-key';

    /**
     * Get human-readable description
     */
    public function description(): string
    {
        return match ($this) {
            self::PUBLIC_KEY => 'WebAuthn public key credential',
        };
    }
}
