<?php

declare(strict_types=1);

namespace Nexus\Domain\Identity\ValueObjects;

use InvalidArgumentException;

/**
 * WebAuthn Registration Options
 *
 * Immutable value object wrapping PublicKeyCredentialCreationOptions
 * for WebAuthn registration ceremonies.
 *
 * @see https://www.w3.org/TR/webauthn-2/#dictdef-publickeycredentialcreationoptions
 */
final readonly class WebAuthnRegistrationOptions
{
    /**
     * @param string $challenge Cryptographic challenge (base64url-encoded, min 16 bytes)
     * @param string $rpId Relying Party identifier (domain)
     * @param string $rpName Relying Party display name
     * @param string $userId User identifier (base64url-encoded)
     * @param string $userName User name (e.g., email)
     * @param string $userDisplayName User display name
     * @param array<array{alg: int, type: string}> $pubKeyCredParams Supported algorithms
     * @param int $timeout Timeout in milliseconds (default 60000 = 60s)
     * @param AuthenticatorSelection|null $authenticatorSelection Authenticator criteria
     * @param AttestationConveyancePreference $attestation Attestation preference
     * @param array<PublicKeyCredentialDescriptor> $excludeCredentials Credentials to exclude
     *
     * @throws InvalidArgumentException If validation fails
     */
    public function __construct(
        public string $challenge,
        public string $rpId,
        public string $rpName,
        public string $userId,
        public string $userName,
        public string $userDisplayName,
        public array $pubKeyCredParams,
        public int $timeout = 60000,
        public ?AuthenticatorSelection $authenticatorSelection = null,
        public AttestationConveyancePreference $attestation = AttestationConveyancePreference::NONE,
        public array $excludeCredentials = []
    ) {
        if (empty($this->challenge)) {
            throw new InvalidArgumentException('Challenge cannot be empty');
        }

        if (strlen(base64_decode($this->challenge, true) ?: '') < 16) {
            throw new InvalidArgumentException('Challenge must be at least 16 bytes');
        }

        if (empty($this->rpId)) {
            throw new InvalidArgumentException('Relying Party ID cannot be empty');
        }

        if (empty($this->rpName)) {
            throw new InvalidArgumentException('Relying Party name cannot be empty');
        }

        if (empty($this->userId)) {
            throw new InvalidArgumentException('User ID cannot be empty');
        }

        if (empty($this->userName)) {
            throw new InvalidArgumentException('User name cannot be empty');
        }

        if (empty($this->userDisplayName)) {
            throw new InvalidArgumentException('User display name cannot be empty');
        }

        if (empty($this->pubKeyCredParams)) {
            throw new InvalidArgumentException('At least one public key credential parameter is required');
        }

        foreach ($this->pubKeyCredParams as $param) {
            if (!isset($param['alg']) || !isset($param['type'])) {
                throw new InvalidArgumentException('Public key credential parameter must have alg and type');
            }
        }

        if ($this->timeout < 30000) {
            throw new InvalidArgumentException('Timeout must be at least 30000ms (30 seconds)');
        }

        if ($this->timeout > 600000) {
            throw new InvalidArgumentException('Timeout must not exceed 600000ms (10 minutes)');
        }

        foreach ($this->excludeCredentials as $credential) {
            if (!$credential instanceof PublicKeyCredentialDescriptor) {
                throw new InvalidArgumentException('Exclude credentials must be PublicKeyCredentialDescriptor instances');
            }
        }
    }

    /**
     * Create registration options with default algorithm support
     *
     * @param string $challenge Base64url-encoded challenge
     * @param string $rpId Relying Party ID
     * @param string $rpName Relying Party name
     * @param string $userId User ID
     * @param string $userName User name
     * @param string $userDisplayName User display name
     * @param AuthenticatorSelection|null $authenticatorSelection Authenticator criteria
     * @return self
     */
    public static function create(
        string $challenge,
        string $rpId,
        string $rpName,
        string $userId,
        string $userName,
        string $userDisplayName,
        ?AuthenticatorSelection $authenticatorSelection = null
    ): self {
        return new self(
            challenge: $challenge,
            rpId: $rpId,
            rpName: $rpName,
            userId: $userId,
            userName: $userName,
            userDisplayName: $userDisplayName,
            pubKeyCredParams: self::defaultAlgorithms(),
            authenticatorSelection: $authenticatorSelection
        );
    }

    /**
     * Get default algorithm support (ES256, RS256, EdDSA)
     *
     * @return array<array{alg: int, type: string}>
     */
    public static function defaultAlgorithms(): array
    {
        return [
            ['alg' => -7, 'type' => 'public-key'],   // ES256 (ECDSA with SHA-256)
            ['alg' => -257, 'type' => 'public-key'], // RS256 (RSASSA-PKCS1-v1_5 with SHA-256)
            ['alg' => -8, 'type' => 'public-key'],   // EdDSA
        ];
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
            'rp' => [
                'id' => $this->rpId,
                'name' => $this->rpName,
            ],
            'user' => [
                'id' => $this->userId,
                'name' => $this->userName,
                'displayName' => $this->userDisplayName,
            ],
            'pubKeyCredParams' => $this->pubKeyCredParams,
            'timeout' => $this->timeout,
            'attestation' => $this->attestation->value,
        ];

        if ($this->authenticatorSelection !== null) {
            $data['authenticatorSelection'] = $this->authenticatorSelection->toArray();
        }

        if (!empty($this->excludeCredentials)) {
            $data['excludeCredentials'] = array_map(
                fn(PublicKeyCredentialDescriptor $cred) => $cred->toArray(),
                $this->excludeCredentials
            );
        }

        return $data;
    }

    /**
     * Check if this is for passwordless registration
     */
    public function isPasswordless(): bool
    {
        return $this->authenticatorSelection?->isPasswordless() ?? false;
    }

    /**
     * Check if this requires platform authenticator
     */
    public function requiresPlatformAuthenticator(): bool
    {
        return $this->authenticatorSelection?->authenticatorAttachment === AuthenticatorAttachment::PLATFORM;
    }

    /**
     * Check if attestation validation is required
     */
    public function requiresAttestationValidation(): bool
    {
        return $this->attestation->requiresValidation();
    }
}
