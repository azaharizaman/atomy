<?php

declare(strict_types=1);

namespace Nexus\SSO\Contracts;

use Nexus\SSO\ValueObjects\SsoProviderConfig;
use Nexus\SSO\ValueObjects\SsoProtocol;
use Nexus\SSO\ValueObjects\UserProfile;

/**
 * Base SSO provider interface
 * 
 * All concrete SSO providers must implement this contract
 */
interface SsoProviderInterface
{
    /**
     * Get provider name (e.g., 'azure', 'google', 'okta', 'saml-generic')
     */
    public function getName(): string;

    /**
     * Get SSO protocol (SAML2, OAuth2, OIDC)
     */
    public function getProtocol(): SsoProtocol;

    /**
     * Generate authorization URL for SSO login
     * 
     * @param SsoProviderConfig $config Provider configuration
     * @param string $state Random state token for CSRF protection
     * @param array<string, mixed> $parameters Additional parameters
     * @return string Authorization URL to redirect user
     */
    public function getAuthorizationUrl(
        SsoProviderConfig $config,
        string $state,
        array $parameters = []
    ): string;

    /**
     * Handle SSO callback and extract user profile
     * 
     * @param SsoProviderConfig $config Provider configuration
     * @param array<string, mixed> $callbackData Data from IdP callback
     * @return UserProfile Extracted user profile with attributes
     * @throws \Nexus\SSO\Exceptions\SsoAuthenticationException
     */
    public function handleCallback(
        SsoProviderConfig $config,
        array $callbackData
    ): UserProfile;

    /**
     * Get logout URL for Single Logout (SLO)
     * 
     * @param SsoProviderConfig $config Provider configuration
     * @param string $sessionId SSO session identifier
     * @return string|null Logout URL (null if provider doesn't support SLO)
     */
    public function getLogoutUrl(SsoProviderConfig $config, string $sessionId): ?string;

    /**
     * Validate configuration
     * 
     * @throws \Nexus\SSO\Exceptions\SsoConfigurationException
     */
    public function validateConfig(SsoProviderConfig $config): void;
}
