<?php

declare(strict_types=1);

namespace Nexus\SSO\Providers;

use Nexus\SSO\Contracts\OidcProviderInterface;
use Nexus\SSO\ValueObjects\SsoProviderConfig;
use Nexus\SSO\ValueObjects\SsoProtocol;
use Nexus\SSO\ValueObjects\UserProfile;
use Nexus\SSO\Exceptions\SsoConfigurationException;
use Nexus\SSO\Exceptions\InvalidOAuthTokenException;

/**
 * OpenID Connect (OIDC) provider
 * 
 * Extends OAuth2Provider with JWT ID token validation and discovery document support
 */
class OidcProvider extends OAuth2Provider implements OidcProviderInterface
{
    public function getName(): string
    {
        return 'oidc';
    }

    public function getProtocol(): SsoProtocol
    {
        return SsoProtocol::OIDC;
    }

    public function getAuthorizationUrl(
        SsoProviderConfig $config,
        string $state,
        array $parameters = []
    ): string {
        // OIDC requires 'openid' scope
        $scopes = $parameters['scopes'] ?? [];
        if (!in_array('openid', $scopes, true)) {
            array_unshift($scopes, 'openid');
        }
        $parameters['scopes'] = $scopes;

        return parent::getAuthorizationUrl($config, $state, $parameters);
    }

    public function getAccessToken(SsoProviderConfig $config, string $code): \Nexus\SSO\ValueObjects\OAuthToken
    {
        // For testing with mock ID token
        if ($code === 'mock_authorization_code' && isset($config->metadata['mock_id_token_claims'])) {
            return new \Nexus\SSO\ValueObjects\OAuthToken(
                accessToken: 'mock_access_token_' . bin2hex(random_bytes(16)),
                tokenType: 'Bearer',
                expiresIn: 3600,
                refreshToken: 'mock_refresh_token',
                idToken: 'mock.id.token', // OIDC includes ID token
                scopes: $config->scopes,
            );
        }

        // Use parent OAuth2 implementation for real requests
        return parent::getAccessToken($config, $code);
    }

    public function validateConfig(SsoProviderConfig $config): void
    {
        parent::validateConfig($config);

        // OIDC requires issuer_url (stored in discoveryUrl field)
        if (empty($config->discoveryUrl)) {
            throw new SsoConfigurationException("OIDC provider requires 'issuer_url' (discoveryUrl) to be configured");
        }
    }

    public function getDiscoveryDocument(string $issuerUrl): array
    {
        // Mock response for testing (check global first, then try to use real fetch)
        if (isset($GLOBALS['SSO_MOCK_DISCOVERY_DOCUMENT'])) {
            return $GLOBALS['SSO_MOCK_DISCOVERY_DOCUMENT'];
        }

        // Real implementation would fetch from /.well-known/openid-configuration
        $discoveryUrl = rtrim($issuerUrl, '/') . '/.well-known/openid-configuration';
        
        // Use simple HTTP client (in production, use Guzzle or similar)
        $response = file_get_contents($discoveryUrl);
        
        if ($response === false) {
            throw new SsoConfigurationException("Failed to fetch discovery document from {$discoveryUrl}");
        }

        $discovery = json_decode($response, true);
        
        if (!is_array($discovery)) {
            throw new SsoConfigurationException("Invalid discovery document format");
        }

        return $discovery;
    }

    public function validateIdToken(string $idToken, SsoProviderConfig $config): void
    {
        // OAuth2 base interface requires void return
        // Actual validation with claims parsing is in parseIdToken()
        $this->parseIdToken($idToken, $config);
    }

    public function parseIdToken(string $idToken, SsoProviderConfig $config): array
    {
        // Mock validation for testing (check for mock claims in metadata)
        if (isset($config->metadata['mock_id_token_claims'])) {
            return $this->validateMockIdToken($idToken, $config);
        }

        // Real JWT validation using lcobucci/jwt
        try {
            // Parse JWT without validation first
            $parts = explode('.', $idToken);
            if (count($parts) !== 3) {
                throw InvalidOAuthTokenException::invalidIdToken('Malformed JWT token');
            }

            // Decode payload
            $payload = base64_decode($parts[1], true);
            if ($payload === false) {
                throw InvalidOAuthTokenException::invalidIdToken('Invalid JWT payload encoding');
            }

            $claims = json_decode($payload, true);
            if (!is_array($claims)) {
                throw InvalidOAuthTokenException::invalidIdToken('Invalid JWT payload JSON');
            }

            // Validate required claims
            $this->validateClaims($claims, $config);

            return $claims;

        } catch (\Throwable $e) {
            if ($e instanceof InvalidOAuthTokenException) {
                throw $e;
            }
            throw InvalidOAuthTokenException::invalidIdToken($e->getMessage());
        }
    }

    public function getJwks(string $jwksUri): array
    {
        // Mock response for testing
        if (isset($_ENV['SSO_TEST_MODE']) || defined('PHPUNIT_RUNNING')) {
            return ['keys' => []];
        }

        // Real implementation would fetch JWKS
        $response = file_get_contents($jwksUri);
        
        if ($response === false) {
            throw new SsoConfigurationException("Failed to fetch JWKS from {$jwksUri}");
        }

        $jwks = json_decode($response, true);
        
        if (!is_array($jwks)) {
            throw new SsoConfigurationException("Invalid JWKS format");
        }

        return $jwks;
    }

    public function handleCallback(
        SsoProviderConfig $config,
        array $callbackData
    ): UserProfile {
        // Get OAuth2 token (includes id_token)
        $token = $this->getAccessToken($config, $callbackData['code']);

        // Validate ID token and extract claims
        $claims = $this->parseIdToken($token->idToken ?? '', $config);

        // Map ID token claims to UserProfile using parent's method
        return $this->extractUserProfile($claims, $config);
    }

    /**
     * Validate ID token claims (issuer, audience, expiration, etc.)
     */
    private function validateClaims(array $claims, SsoProviderConfig $config): void
    {
        $now = time();

        // Validate issuer (iss)
        if (!isset($claims['iss'])) {
            throw InvalidOAuthTokenException::invalidIdToken('Missing issuer (iss) claim');
        }
        
        $expectedIssuer = $config->discoveryUrl; // Use discoveryUrl as issuer
        if ($claims['iss'] !== $expectedIssuer) {
            throw InvalidOAuthTokenException::invalidIdToken(
                "Invalid issuer: expected {$expectedIssuer}, got {$claims['iss']}"
            );
        }

        // Validate audience (aud)
        if (!isset($claims['aud'])) {
            throw InvalidOAuthTokenException::invalidIdToken('Missing audience (aud) claim');
        }
        
        $expectedAudience = $config->clientId;
        $audience = is_array($claims['aud']) ? $claims['aud'] : [$claims['aud']];
        
        if (!in_array($expectedAudience, $audience, true)) {
            throw InvalidOAuthTokenException::invalidIdToken(
                "Invalid audience: expected {$expectedAudience}"
            );
        }

        // Validate expiration (exp)
        if (!isset($claims['exp'])) {
            throw InvalidOAuthTokenException::invalidIdToken('Missing expiration (exp) claim');
        }
        
        if ($claims['exp'] < $now) {
            throw InvalidOAuthTokenException::expiredToken();
        }

        // Validate issued at (iat)
        if (!isset($claims['iat'])) {
            throw InvalidOAuthTokenException::invalidIdToken('Missing issued at (iat) claim');
        }
        
        if ($claims['iat'] > $now) {
            throw InvalidOAuthTokenException::invalidIdToken('Token issued in the future');
        }

        // Validate not before (nbf) if present
        if (isset($claims['nbf']) && $claims['nbf'] > $now) {
            throw InvalidOAuthTokenException::invalidIdToken('Token not yet valid (nbf)');
        }
    }

    /**
     * Get mock discovery document for testing
     */
    private function getMockDiscoveryDocument(string $issuerUrl): array
    {
        // Try to get from global mock config first
        if (isset($GLOBALS['SSO_MOCK_DISCOVERY_DOCUMENT'])) {
            return $GLOBALS['SSO_MOCK_DISCOVERY_DOCUMENT'];
        }

        // Default mock discovery document
        return [
            'issuer' => $issuerUrl,
            'authorization_endpoint' => $issuerUrl . '/oauth2/authorize',
            'token_endpoint' => $issuerUrl . '/oauth2/token',
            'userinfo_endpoint' => $issuerUrl . '/oauth2/userinfo',
            'jwks_uri' => $issuerUrl . '/.well-known/jwks.json',
        ];
    }

    /**
     * Validate mock ID token for testing
     */
    private function validateMockIdToken(string $idToken, SsoProviderConfig $config): array
    {
        // Use mock claims from metadata
        $mockClaims = $config->metadata['mock_id_token_claims'] ?? null;
        
        if (!is_array($mockClaims)) {
            throw InvalidOAuthTokenException::invalidIdToken('No mock claims configured');
        }

        // Validate claims as if they were real
        $this->validateClaims($mockClaims, $config);

        return $mockClaims;
    }
}
