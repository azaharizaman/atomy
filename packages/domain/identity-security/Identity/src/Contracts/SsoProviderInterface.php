<?php

declare(strict_types=1);

namespace Nexus\Identity\Contracts;

/**
 * SSO provider interface
 * 
 * Handles single sign-on authentication with external identity providers
 */
interface SsoProviderInterface
{
    /**
     * Get the SSO provider name (e.g., "google", "microsoft", "saml")
     */
    public function getName(): string;

    /**
     * Generate authorization URL for SSO login
     * 
     * @param array<string, mixed> $parameters Additional parameters
     * @return string Authorization URL
     */
    public function getAuthorizationUrl(array $parameters = []): string;

    /**
     * Handle SSO callback and authenticate user
     * 
     * @param array<string, mixed> $callbackData Data from SSO callback
     * @return UserInterface Authenticated or provisioned user
     * @throws \Nexus\Identity\Exceptions\SsoAuthenticationException
     */
    public function handleCallback(array $callbackData): UserInterface;

    /**
     * Get user profile from SSO provider
     * 
     * @param string $accessToken SSO access token
     * @return array<string, mixed> User profile data
     */
    public function getUserProfile(string $accessToken): array;

    /**
     * Map SSO attributes to local user attributes
     * 
     * @param array<string, mixed> $ssoAttributes SSO user attributes
     * @return array<string, mixed> Mapped local attributes
     */
    public function mapAttributes(array $ssoAttributes): array;

    /**
     * Check if JIT (Just-In-Time) provisioning is enabled
     */
    public function isJitProvisioningEnabled(): bool;

    /**
     * Provision a new user from SSO attributes
     * 
     * @param array<string, mixed> $ssoAttributes SSO user attributes
     * @return UserInterface Newly created user
     */
    public function provisionUser(array $ssoAttributes): UserInterface;
}
