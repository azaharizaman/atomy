<?php

declare(strict_types=1);

namespace Nexus\Connector\ValueObjects;

/**
 * Immutable credentials object for external service authentication.
 */
final readonly class Credentials
{
    /**
     * @param AuthMethod $authMethod Authentication method
     * @param array<string, mixed> $data Credential data (api_key, token, client_id, etc.)
     * @param \DateTimeImmutable|null $expiresAt Token expiration time (for OAuth)
     * @param string|null $refreshToken OAuth refresh token
     */
    public function __construct(
        public AuthMethod $authMethod,
        public array $data,
        public ?\DateTimeImmutable $expiresAt = null,
        public ?string $refreshToken = null,
    ) {}

    /**
     * Check if credentials have expired.
     */
    public function isExpired(): bool
    {
        if ($this->expiresAt === null) {
            return false;
        }

        return $this->expiresAt <= new \DateTimeImmutable();
    }

    /**
     * Get a specific credential value.
     */
    public function get(string $key): mixed
    {
        return $this->data[$key] ?? null;
    }

    /**
     * Create credentials for API key authentication.
     */
    public static function apiKey(string $apiKey): self
    {
        return new self(
            authMethod: AuthMethod::API_KEY,
            data: ['api_key' => $apiKey]
        );
    }

    /**
     * Create credentials for Bearer token authentication.
     */
    public static function bearerToken(string $token, ?\DateTimeImmutable $expiresAt = null): self
    {
        return new self(
            authMethod: AuthMethod::BEARER_TOKEN,
            data: ['token' => $token],
            expiresAt: $expiresAt
        );
    }

    /**
     * Create credentials for OAuth2 authentication.
     */
    public static function oauth2(
        string $accessToken,
        string $refreshToken,
        \DateTimeImmutable $expiresAt
    ): self {
        return new self(
            authMethod: AuthMethod::OAUTH2,
            data: ['access_token' => $accessToken],
            expiresAt: $expiresAt,
            refreshToken: $refreshToken
        );
    }

    /**
     * Create credentials for Basic authentication.
     */
    public static function basicAuth(string $username, string $password): self
    {
        return new self(
            authMethod: AuthMethod::BASIC_AUTH,
            data: ['username' => $username, 'password' => $password]
        );
    }
}
