<?php

declare(strict_types=1);

namespace Nexus\Connector\Services;

use Nexus\Connector\Contracts\HttpClientInterface;
use Nexus\Connector\Exceptions\CredentialRefreshException;
use Nexus\Connector\ValueObjects\{Credentials, Endpoint, HttpMethod};

/**
 * Service for refreshing OAuth2 access tokens.
 *
 * Handles automatic token refresh when access tokens expire.
 */
final readonly class OAuthTokenRefresher
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
    ) {}

    /**
     * Refresh OAuth2 access token using refresh token.
     *
     * @param string $tokenEndpoint OAuth2 token endpoint URL
     * @param string $clientId OAuth2 client ID
     * @param string $clientSecret OAuth2 client secret
     * @param string $refreshToken Refresh token
     * @return Credentials New credentials with refreshed access token
     * @throws CredentialRefreshException If refresh fails
     */
    public function refresh(
        string $tokenEndpoint,
        string $clientId,
        string $clientSecret,
        string $refreshToken
    ): Credentials {
        $endpoint = new Endpoint(
            url: $tokenEndpoint,
            method: HttpMethod::POST,
            timeout: 30
        );

        $payload = [
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
        ];

        try {
            $response = $this->httpClient->send(
                $endpoint,
                $payload,
                [] // No credentials needed for token refresh
            );

            $body = $response['body'] ?? [];

            if (!isset($body['access_token'])) {
                throw new \RuntimeException('Response missing access_token');
            }

            $expiresIn = $body['expires_in'] ?? 3600;
            $newRefreshToken = $body['refresh_token'] ?? $refreshToken; // Use new refresh token if provided

            return Credentials::oauth2(
                accessToken: $body['access_token'],
                refreshToken: $newRefreshToken,
                expiresAt: (new \DateTimeImmutable())->modify("+{$expiresIn} seconds")
            );

        } catch (\Throwable $e) {
            throw new CredentialRefreshException(
                message: "Failed to refresh OAuth2 token: {$e->getMessage()}",
                serviceName: 'oauth2',
                previous: $e
            );
        }
    }

    /**
     * Check if credentials need refresh (within 5 minutes of expiry).
     *
     * @param Credentials $credentials Current credentials
     * @return bool True if credentials should be refreshed
     */
    public function shouldRefresh(Credentials $credentials): bool
    {
        if ($credentials->expiresAt === null) {
            return false;
        }

        // Refresh if expiring within 5 minutes
        $threshold = (new \DateTimeImmutable())->modify('+5 minutes');
        
        return $credentials->expiresAt <= $threshold;
    }
}
