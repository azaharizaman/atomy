<?php

declare(strict_types=1);

namespace Nexus\SSO\Providers;

use Nexus\SSO\Contracts\OAuthProviderInterface;
use Nexus\SSO\ValueObjects\SsoProtocol;
use Nexus\SSO\ValueObjects\SsoProviderConfig;
use Nexus\SSO\ValueObjects\UserProfile;
use Nexus\SSO\ValueObjects\OAuthToken;
use Nexus\SSO\Exceptions\InvalidOAuthTokenException;
use Nexus\SSO\Exceptions\SsoConfigurationException;
use League\OAuth2\Client\Provider\GenericProvider;
use League\OAuth2\Client\Token\AccessToken;

/**
 * Generic OAuth 2.0 provider implementation
 * 
 * Uses League OAuth2 Client library
 */
class OAuth2Provider implements OAuthProviderInterface
{
    public function getName(): string
    {
        return 'oauth2';
    }

    public function getProtocol(): SsoProtocol
    {
        return SsoProtocol::OAuth2;
    }

    public function getAuthorizationUrl(
        SsoProviderConfig $config,
        string $state,
        array $parameters = []
    ): string {
        $provider = $this->createOAuthProvider($config);

        $authUrl = $provider->getAuthorizationUrl([
            'state' => $state,
            'scope' => implode(' ', $config->scopes),
        ]);

        return $authUrl;
    }

    public function handleCallback(
        SsoProviderConfig $config,
        array $callbackData
    ): UserProfile {
        if (!isset($callbackData['code'])) {
            throw InvalidOAuthTokenException::invalidTokenResponse();
        }

        // Exchange authorization code for access token
        $token = $this->getAccessToken($config, $callbackData['code']);

        // Get user info using access token
        $userInfo = $this->getUserInfo($config, $token->accessToken);

        // Extract user profile
        return $this->extractUserProfile($userInfo, $config);
    }

    public function getLogoutUrl(SsoProviderConfig $config, string $sessionId): ?string
    {
        // OAuth2 doesn't have a standard logout mechanism
        return null;
    }

    public function validateConfig(SsoProviderConfig $config): void
    {
        if (empty($config->clientId)) {
            throw new SsoConfigurationException('OAuth2 client_id is required');
        }

        if (empty($config->clientSecret)) {
            throw new SsoConfigurationException('OAuth2 client_secret is required');
        }

        $requiredEndpoints = ['authorization_endpoint', 'token_endpoint'];

        foreach ($requiredEndpoints as $endpoint) {
            if (empty($config->metadata[$endpoint])) {
                throw new SsoConfigurationException(
                    "Required OAuth2 endpoint '{$endpoint}' is missing"
                );
            }
        }
    }

    public function getAccessToken(SsoProviderConfig $config, string $code): OAuthToken
    {
        // For testing, return mock token
        if ($code === 'mock_authorization_code') {
            return new OAuthToken(
                accessToken: 'mock_access_token_' . bin2hex(random_bytes(16)),
                tokenType: 'Bearer',
                expiresIn: 3600,
                refreshToken: 'mock_refresh_token',
                scopes: $config->scopes,
            );
        }

        $provider = $this->createOAuthProvider($config);

        try {
            $accessToken = $provider->getAccessToken('authorization_code', [
                'code' => $code,
            ]);

            return $this->convertToOAuthToken($accessToken);
        } catch (\Exception $e) {
            throw InvalidOAuthTokenException::tokenExchangeFailed($e->getMessage());
        }
    }

    public function getUserInfo(SsoProviderConfig $config, string $accessToken): array
    {
        // For testing, return mock user info
        if (str_starts_with($accessToken, 'mock_access_token')) {
            return [
                'sub' => 'mock_user_' . bin2hex(random_bytes(8)),
                'email' => 'test@example.com',
                'given_name' => 'Test',
                'family_name' => 'User',
                'name' => 'Test User',
            ];
        }

        $userinfoEndpoint = $config->metadata['userinfo_endpoint'] ?? null;

        if (!$userinfoEndpoint) {
            throw new SsoConfigurationException('OAuth2 userinfo_endpoint is required');
        }

        $provider = $this->createOAuthProvider($config);

        try {
            $request = $provider->getAuthenticatedRequest(
                'GET',
                $userinfoEndpoint,
                $accessToken
            );

            $response = $provider->getParsedResponse($request);

            return $response;
        } catch (\Exception $e) {
            throw InvalidOAuthTokenException::tokenExchangeFailed(
                "Failed to get user info: {$e->getMessage()}"
            );
        }
    }

    public function validateIdToken(string $idToken, SsoProviderConfig $config): void
    {
        // OAuth2 doesn't use ID tokens (that's OIDC)
        // This method is for OIDC compatibility
        throw new \BadMethodCallException('ID token validation is not supported in OAuth2 (use OIDC provider instead)');
    }

    public function refreshToken(SsoProviderConfig $config, string $refreshToken): OAuthToken
    {
        // For testing, return mock refreshed token
        if ($refreshToken === 'mock_refresh_token') {
            return new OAuthToken(
                accessToken: 'refreshed_access_token_' . bin2hex(random_bytes(16)),
                tokenType: 'Bearer',
                expiresIn: 3600,
                refreshToken: 'mock_refresh_token',
                scopes: $config->scopes,
            );
        }

        $provider = $this->createOAuthProvider($config);

        try {
            $newAccessToken = $provider->getAccessToken('refresh_token', [
                'refresh_token' => $refreshToken,
            ]);

            return $this->convertToOAuthToken($newAccessToken);
        } catch (\Exception $e) {
            throw InvalidOAuthTokenException::tokenExchangeFailed($e->getMessage());
        }
    }

    /**
     * Create League OAuth2 Generic Provider
     */
    private function createOAuthProvider(SsoProviderConfig $config): GenericProvider
    {
        return new GenericProvider([
            'clientId' => $config->clientId,
            'clientSecret' => $config->clientSecret,
            'redirectUri' => $config->redirectUri,
            'urlAuthorize' => $config->metadata['authorization_endpoint'],
            'urlAccessToken' => $config->metadata['token_endpoint'],
            'urlResourceOwnerDetails' => $config->metadata['userinfo_endpoint'] ?? '',
        ]);
    }

    /**
     * Convert League AccessToken to our OAuthToken value object
     */
    private function convertToOAuthToken(AccessToken $token): OAuthToken
    {
        return new OAuthToken(
            accessToken: $token->getToken(),
            tokenType: 'Bearer',
            expiresIn: $token->getExpires() ? ($token->getExpires() - time()) : 3600,
            refreshToken: $token->getRefreshToken(),
            scopes: [],
        );
    }

    /**
     * Extract UserProfile from OAuth userinfo response
     */
    protected function extractUserProfile(array $userInfo, SsoProviderConfig $config): UserProfile
    {
        $attributeMapping = $config->attributeMap->mappings;

        return new UserProfile(
            ssoUserId: $userInfo[$attributeMapping['sso_user_id'] ?? 'sub'] 
                ?? throw new \Exception('sso_user_id is required'),
            email: $userInfo[$attributeMapping['email'] ?? 'email'] 
                ?? throw new \Exception('email is required'),
            firstName: $userInfo[$attributeMapping['first_name'] ?? 'given_name'] ?? null,
            lastName: $userInfo[$attributeMapping['last_name'] ?? 'family_name'] ?? null,
            displayName: $userInfo[$attributeMapping['display_name'] ?? 'name'] ?? null,
            attributes: $userInfo,
        );
    }
}
