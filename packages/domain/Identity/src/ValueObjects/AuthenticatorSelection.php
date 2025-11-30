<?php

declare(strict_types=1);

namespace Nexus\Domain\Identity\ValueObjects;

/**
 * Authenticator Selection Criteria
 *
 * Specifies requirements for the authenticator used during registration.
 *
 * @see https://www.w3.org/TR/webauthn-2/#dictdef-authenticatorselectioncriteria
 */
final readonly class AuthenticatorSelection
{
    /**
     * @param AuthenticatorAttachment|null $authenticatorAttachment Platform or cross-platform
     * @param bool $requireResidentKey Require discoverable credential (passkey)
     * @param UserVerificationRequirement $userVerification User verification requirement
     */
    public function __construct(
        public ?AuthenticatorAttachment $authenticatorAttachment = null,
        public bool $requireResidentKey = false,
        public UserVerificationRequirement $userVerification = UserVerificationRequirement::PREFERRED
    ) {}

    /**
     * Create selection for platform authenticators (Touch ID, Face ID, Windows Hello)
     *
     * @param bool $requireResidentKey Require passkey (discoverable credential)
     * @param UserVerificationRequirement $userVerification User verification level
     * @return self
     */
    public static function platform(
        bool $requireResidentKey = true,
        UserVerificationRequirement $userVerification = UserVerificationRequirement::REQUIRED
    ): self {
        return new self(
            authenticatorAttachment: AuthenticatorAttachment::PLATFORM,
            requireResidentKey: $requireResidentKey,
            userVerification: $userVerification
        );
    }

    /**
     * Create selection for cross-platform authenticators (YubiKey, Security Key)
     *
     * @param bool $requireResidentKey Require passkey (discoverable credential)
     * @param UserVerificationRequirement $userVerification User verification level
     * @return self
     */
    public static function crossPlatform(
        bool $requireResidentKey = false,
        UserVerificationRequirement $userVerification = UserVerificationRequirement::PREFERRED
    ): self {
        return new self(
            authenticatorAttachment: AuthenticatorAttachment::CROSS_PLATFORM,
            requireResidentKey: $requireResidentKey,
            userVerification: $userVerification
        );
    }

    /**
     * Create selection for any authenticator (maximum compatibility)
     *
     * @param bool $requireResidentKey Require passkey (discoverable credential)
     * @param UserVerificationRequirement $userVerification User verification level
     * @return self
     */
    public static function any(
        bool $requireResidentKey = false,
        UserVerificationRequirement $userVerification = UserVerificationRequirement::PREFERRED
    ): self {
        return new self(
            authenticatorAttachment: null,
            requireResidentKey: $requireResidentKey,
            userVerification: $userVerification
        );
    }

    /**
     * Convert to WebAuthn API array format
     *
     * @return array{authenticatorAttachment?: string, residentKey: string, requireResidentKey: bool, userVerification: string}
     */
    public function toArray(): array
    {
        $data = [
            'residentKey' => $this->requireResidentKey ? 'required' : 'preferred',
            'requireResidentKey' => $this->requireResidentKey,
            'userVerification' => $this->userVerification->value,
        ];

        if ($this->authenticatorAttachment !== null) {
            $data['authenticatorAttachment'] = $this->authenticatorAttachment->value;
        }

        return $data;
    }

    /**
     * Check if this selection requires a passkey (discoverable credential)
     */
    public function requiresPasskey(): bool
    {
        return $this->requireResidentKey;
    }

    /**
     * Check if this selection allows any authenticator type
     */
    public function allowsAnyAuthenticator(): bool
    {
        return $this->authenticatorAttachment === null;
    }

    /**
     * Check if this selection is suitable for passwordless authentication
     */
    public function isPasswordless(): bool
    {
        return $this->requireResidentKey && $this->userVerification->isRequired();
    }
}
