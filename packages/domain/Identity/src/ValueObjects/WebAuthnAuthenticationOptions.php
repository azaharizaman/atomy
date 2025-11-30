<?php

declare(strict_types=1);

namespace Nexus\Domain\Identity\ValueObjects;

use InvalidArgumentException;

/**
 * WebAuthn Authentication Options
 *
 * Immutable value object wrapping PublicKeyCredentialRequestOptions
 * for WebAuthn authentication ceremonies.
 *
 * @see https://www.w3.org/TR/webauthn-2/#dictdef-publickeycredentialrequestoptions
 */
final readonly class WebAuthnAuthenticationOptions
{
    /**
     * @param string $challenge Cryptographic challenge (base64url-encoded, min 16 bytes)
     * @param int $timeout Timeout in milliseconds (default 60000 = 60s)
     * @param string|null $rpId Relying Party identifier (optional, defaults to current domain)
     * @param array<PublicKeyCredentialDescriptor> $allowCredentials Allowed credentials (empty = usernameless)
     * @param UserVerificationRequirement $userVerification User verification requirement
     *
     * @throws InvalidArgumentException If validation fails
     */
    public function __construct(
        public string $challenge,
        public int $timeout = 60000,
        public ?string $rpId = null,
        public array $allowCredentials = [],
        public UserVerificationRequirement $userVerification = UserVerificationRequirement::PREFERRED
    ) {
        if (empty($this->challenge)) {
            throw new InvalidArgumentException('Challenge cannot be empty');
        }

        if (strlen(base64_decode($this->challenge, true) ?: '') < 16) {
            throw new InvalidArgumentException('Challenge must be at least 16 bytes');
        }

        if ($this->timeout < 30000) {
            throw new InvalidArgumentException('Timeout must be at least 30000ms (30 seconds)');
        }

        if ($this->timeout > 600000) {
            throw new InvalidArgumentException('Timeout must not exceed 600000ms (10 minutes)');
        }

        foreach ($this->allowCredentials as $credential) {
            if (!$credential instanceof PublicKeyCredentialDescriptor) {
                throw new InvalidArgumentException('Allow credentials must be PublicKeyCredentialDescriptor instances');
            }
        }
    }

    /**
     * Create authentication options for specific user (with allowed credentials)
     *
     * @param string $challenge Base64url-encoded challenge
     * @param array<PublicKeyCredentialDescriptor> $allowCredentials User's credentials
     * @param string|null $rpId Relying Party ID
     * @param UserVerificationRequirement $userVerification User verification level
     * @return self
     */
    public static function forUser(
        string $challenge,
        array $allowCredentials,
        ?string $rpId = null,
        UserVerificationRequirement $userVerification = UserVerificationRequirement::PREFERRED
    ): self {
        if (empty($allowCredentials)) {
            throw new InvalidArgumentException('Allow credentials cannot be empty for user authentication');
        }

        return new self(
            challenge: $challenge,
            rpId: $rpId,
            allowCredentials: $allowCredentials,
            userVerification: $userVerification
        );
    }

    /**
     * Create authentication options for usernameless/discoverable credentials
     *
     * @param string $challenge Base64url-encoded challenge
     * @param string|null $rpId Relying Party ID
     * @param UserVerificationRequirement $userVerification User verification level (typically REQUIRED)
     * @return self
     */
    public static function usernameless(
        string $challenge,
        ?string $rpId = null,
        UserVerificationRequirement $userVerification = UserVerificationRequirement::REQUIRED
    ): self {
        return new self(
            challenge: $challenge,
            rpId: $rpId,
            allowCredentials: [], // Empty = usernameless
            userVerification: $userVerification
        );
    }

    /**
     * Convert to WebAuthn API array format
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'challenge' => $this->challenge,
            'timeout' => $this->timeout,
            'userVerification' => $this->userVerification->value,
        ];

        if ($this->rpId !== null) {
            $data['rpId'] = $this->rpId;
        }

        if (!empty($this->allowCredentials)) {
            $data['allowCredentials'] = array_map(
                fn(PublicKeyCredentialDescriptor $cred) => $cred->toArray(),
                $this->allowCredentials
            );
        }

        return $data;
    }

    /**
     * Check if this is usernameless authentication
     */
    public function isUsernameless(): bool
    {
        return empty($this->allowCredentials);
    }

    /**
     * Check if user verification is required
     */
    public function requiresUserVerification(): bool
    {
        return $this->userVerification->isRequired();
    }

    /**
     * Get number of allowed credentials
     */
    public function getAllowedCredentialCount(): int
    {
        return count($this->allowCredentials);
    }
}
