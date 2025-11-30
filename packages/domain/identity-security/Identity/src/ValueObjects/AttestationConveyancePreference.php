<?php

declare(strict_types=1);

namespace Nexus\Identity\ValueObjects;

/**
 * Attestation conveyance preference for WebAuthn registration
 *
 * Specifies the Relying Party's preference for attestation conveyance.
 *
 * @see https://www.w3.org/TR/webauthn-2/#enum-attestation-convey
 */
enum AttestationConveyancePreference: string
{
    /**
     * No attestation statement required
     *
     * Best for privacy, suitable for most use cases
     */
    case NONE = 'none';

    /**
     * Attestation statement may be included if available
     *
     * Balance between privacy and verification
     */
    case INDIRECT = 'indirect';

    /**
     * Full attestation statement required
     *
     * Maximum verification, may impact privacy
     */
    case DIRECT = 'direct';

    /**
     * Enterprise attestation (requires enterprise RP ID list)
     *
     * For managed devices only
     */
    case ENTERPRISE = 'enterprise';

    /**
     * Get human-readable description
     */
    public function description(): string
    {
        return match ($this) {
            self::NONE => 'No attestation (privacy-friendly)',
            self::INDIRECT => 'Anonymized attestation',
            self::DIRECT => 'Full attestation (device verification)',
            self::ENTERPRISE => 'Enterprise attestation (managed devices)',
        };
    }

    /**
     * Check if attestation validation is needed
     */
    public function requiresValidation(): bool
    {
        return $this !== self::NONE;
    }

    /**
     * Check if this is the privacy-preserving option
     */
    public function isPrivacyPreserving(): bool
    {
        return $this === self::NONE || $this === self::INDIRECT;
    }
}
