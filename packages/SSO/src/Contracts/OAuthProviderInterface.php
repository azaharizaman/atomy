<?php

declare(strict_types=1);

namespace Nexus\SSO\Contracts;

use Nexus\SSO\ValueObjects\OAuthToken;
use Nexus\SSO\ValueObjects\SsoProviderConfig;

/**
 * OAuth2/OIDC provider interface
 * 
 * Extends base provider with OAuth-specific operations
 */
interface OAuthProviderInterface extends SsoProviderInterface
{
    /**
     * Exchange authorization code for access token
     * 
     * @param SsoProviderConfig $config Provider configuration
     * @param string $code Authorization code from callback
     * @return OAuthToken Access token and metadata
     * @throws \Nexus\SSO\Exceptions\InvalidOAuthTokenException
     */
    public function getAccessToken(SsoProviderConfig $config, string $code): OAuthToken;

    /**
     * Get user info from OAuth/OIDC userinfo endpoint
     * 
     * @param SsoProviderConfig $config Provider configuration
     * @param string $accessToken Access token
     * @return array<string, mixed> User attributes from IdP
     */
    public function getUserInfo(SsoProviderConfig $config, string $accessToken): array;

    /**
     * Validate ID token (OIDC only)
     * 
     * @param string $idToken JWT ID token
     * @param SsoProviderConfig $config Provider configuration
     * @throws \Nexus\SSO\Exceptions\InvalidOAuthTokenException
     */
    public function validateIdToken(string $idToken, SsoProviderConfig $config): void;

    /**
     * Refresh access token
     * 
     * @param SsoProviderConfig $config Provider configuration
     * @param string $refreshToken Refresh token
     * @return OAuthToken New access token
     * @throws \Nexus\SSO\Exceptions\TokenRefreshException
     */
    public function refreshToken(SsoProviderConfig $config, string $refreshToken): OAuthToken;
}
