<?php

declare(strict_types=1);

namespace Nexus\Identity\ValueObjects;

/**
 * User verification requirement for WebAuthn operations
 *
 * Specifies the Relying Party's requirement for user verification.
 *
 * @see https://www.w3.org/TR/webauthn-2/#enum-userVerificationRequirement
 */
enum UserVerificationRequirement: string
{
    /**
     * User verification is required (biometric, PIN, etc.)
     *
     * Operation fails if verification cannot be performed
     */
    case REQUIRED = 'required';

    /**
     * User verification is preferred but not required
     *
     * Use verification if available, proceed without if not
     */
    case PREFERRED = 'preferred';

    /**
     * User verification should not be employed
     *
     * Useful for low-security, high-convenience scenarios
     */
    case DISCOURAGED = 'discouraged';

    /**
     * Get human-readable description
     */
    public function description(): string
    {
        return match ($this) {
            self::REQUIRED => 'Biometric/PIN verification required',
            self::PREFERRED => 'Biometric/PIN verification preferred',
            self::DISCOURAGED => 'Biometric/PIN verification not needed',
        };
    }

    /**
     * Check if user verification is mandatory
     */
    public function isRequired(): bool
    {
        return $this === self::REQUIRED;
    }

    /**
     * Check if user verification is optional
     */
    public function isOptional(): bool
    {
        return $this === self::PREFERRED || $this === self::DISCOURAGED;
    }
}
