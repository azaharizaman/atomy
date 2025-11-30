<?php

declare(strict_types=1);

namespace Nexus\SSO\Contracts;

use Nexus\SSO\ValueObjects\UserProfile;

/**
 * User provisioning interface
 * 
 * This contract is defined in Nexus\SSO but IMPLEMENTED by Nexus\Domain\Identity in consuming application.
 * It decouples SSO from Identity package.
 */
interface UserProvisioningInterface
{
    /**
     * Find existing user by SSO identifier or create new user (JIT provisioning)
     * 
     * @param UserProfile $profile SSO user profile with mapped attributes
     * @param string $providerName SSO provider identifier (e.g., 'azure')
     * @param string $tenantId Current tenant context
     * @return string User ID (local system user ID)
     * @throws \Nexus\SSO\Exceptions\UserProvisioningException
     */
    public function findOrCreateUser(
        UserProfile $profile,
        string $providerName,
        string $tenantId
    ): string;

    /**
     * Update user attributes from SSO profile
     * 
     * @param string $userId Local user ID
     * @param UserProfile $profile Updated SSO profile
     */
    public function updateUserFromProfile(string $userId, UserProfile $profile): void;

    /**
     * Check if Just-In-Time (JIT) provisioning is enabled
     * 
     * @param string $providerName SSO provider identifier
     * @param string $tenantId Current tenant context
     */
    public function isJitProvisioningEnabled(string $providerName, string $tenantId): bool;

    /**
     * Link SSO identity to existing user
     * 
     * @param string $userId Local user ID
     * @param string $ssoUserId SSO user identifier (e.g., Azure AD Object ID)
     * @param string $providerName SSO provider identifier
     */
    public function linkSsoIdentity(string $userId, string $ssoUserId, string $providerName): void;

    /**
     * Unlink SSO identity from user
     * 
     * @param string $userId Local user ID
     * @param string $providerName SSO provider identifier
     */
    public function unlinkSsoIdentity(string $userId, string $providerName): void;
}
