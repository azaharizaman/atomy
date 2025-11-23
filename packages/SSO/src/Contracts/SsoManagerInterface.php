<?php

declare(strict_types=1);

namespace Nexus\SSO\Contracts;

use Nexus\SSO\ValueObjects\SsoSession;
use Nexus\SSO\ValueObjects\UserProfile;

/**
 * Main SSO orchestration interface
 * 
 * Coordinates SSO authentication flow across providers
 */
interface SsoManagerInterface
{
    /**
     * Initiate SSO login flow
     * 
     * @param string $providerName SSO provider identifier (e.g., 'azure', 'google')
     * @param string $tenantId Current tenant context
     * @param array<string, mixed> $parameters Additional parameters (returnUrl, etc.)
     * @return array{authUrl: string, state: string} Authorization URL and state token
     * @throws \Nexus\SSO\Exceptions\SsoProviderNotFoundException
     * @throws \Nexus\SSO\Exceptions\SsoConfigurationException
     */
    public function initiateLogin(
        string $providerName,
        string $tenantId,
        array $parameters = []
    ): array;

    /**
     * Handle SSO callback and provision user
     * 
     * @param string $providerName SSO provider identifier
     * @param array<string, mixed> $callbackData Data from IdP callback (code, SAML response, etc.)
     * @param string $state State token from initiateLogin()
     * @return SsoSession Authenticated SSO session with user profile
     * @throws \Nexus\SSO\Exceptions\InvalidCallbackStateException
     * @throws \Nexus\SSO\Exceptions\SsoAuthenticationException
     * @throws \Nexus\SSO\Exceptions\UserProvisioningException
     */
    public function handleCallback(
        string $providerName,
        array $callbackData,
        string $state
    ): SsoSession;

    /**
     * Get user profile from SSO session
     * 
     * @param string $sessionId SSO session identifier
     * @return UserProfile User profile with mapped attributes
     * @throws \Nexus\SSO\Exceptions\SsoSessionExpiredException
     */
    public function getUserProfile(string $sessionId): UserProfile;

    /**
     * Initiate SSO logout (Single Logout - SLO)
     * 
     * @param string $sessionId SSO session identifier
     * @param string $providerName SSO provider identifier
     * @return string|null Logout URL (null if provider doesn't support SLO)
     */
    public function initiateLogout(string $sessionId, string $providerName): ?string;

    /**
     * Check if SSO is enabled for tenant
     */
    public function isSsoEnabled(string $tenantId): bool;

    /**
     * Get available SSO providers for tenant
     * 
     * @return array<string, array{name: string, protocol: string, enabled: bool}>
     */
    public function getAvailableProviders(string $tenantId): array;
}
