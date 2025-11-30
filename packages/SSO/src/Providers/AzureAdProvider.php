<?php

declare(strict_types=1);

namespace Nexus\SSO\Providers;

use Nexus\SSO\ValueObjects\SsoProtocol;
use Nexus\SSO\Exceptions\SsoConfigurationException;
use Nexus\SSO\ValueObjects\SsoProviderConfig;

/**
 * Azure Active Directory (Microsoft Entra ID) SSO Provider
 * 
 * Extends OIDC provider with Azure AD-specific configuration:
 * - Tenant-aware endpoints (common, organizations, consumers, {tenant-id})
 * - Microsoft Graph API integration
 * - Azure AD-specific attributes (oid, preferred_username, upn)
 * 
 * @see https://learn.microsoft.com/en-us/entra/identity-platform/v2-protocols-oidc
 */
final class AzureAdProvider extends OidcProvider
{
    private const AZURE_BASE_URL = 'https://login.microsoftonline.com';
    private const GRAPH_BASE_URL = 'https://graph.microsoft.com';

    public function getName(): string
    {
        return 'azure';
    }

    public function getProtocol(): SsoProtocol
    {
        return SsoProtocol::OIDC;
    }

    /**
     * Generate Azure AD authorization URL with tenant-specific endpoint
     * 
     * Supports multiple tenant types:
     * - 'common': Multi-tenant apps (personal + work/school accounts)
     * - 'organizations': Work/school accounts only
     * - 'consumers': Personal Microsoft accounts only  
     * - '{tenant-id}': Specific Azure AD tenant (UUID or domain)
     */
    public function getAuthorizationUrl(
        SsoProviderConfig $config,
        string $state,
        array $parameters = []
    ): string {
        $tenantId = $config->metadata['tenant_id'] ?? 'common';
        
        // Build Azure AD authorization URL
        $baseUrl = self::AZURE_BASE_URL . "/{$tenantId}/oauth2/v2.0/authorize";
        
        $params = [
            'client_id' => $config->clientId,
            'response_type' => 'code',
            'redirect_uri' => $config->redirectUri,
            'response_mode' => 'query',
            'scope' => $this->buildScopeString($config->scopes),
            'state' => $state,
        ];

        return $baseUrl . '?' . http_build_query($params);
    }

    /**
     * Validate Azure AD-specific configuration
     * 
     * @throws SsoConfigurationException
     */
    public function validateConfig(SsoProviderConfig $config): void
    {
        // Azure AD requires tenant_id
        if (empty($config->metadata['tenant_id'])) {
            throw new SsoConfigurationException(
                "Azure AD provider requires 'tenant_id' in metadata"
            );
        }

        // Call parent OIDC validation (checks discoveryUrl/issuer_url)
        parent::validateConfig($config);
    }

    /**
     * Build scope string with automatic 'openid' prepending
     */
    private function buildScopeString(array $scopes): string
    {
        // Ensure 'openid' is always included for OIDC
        if (!in_array('openid', $scopes, true)) {
            array_unshift($scopes, 'openid');
        }

        return implode(' ', $scopes);
    }

    /**
     * Get Azure AD token endpoint for the configured tenant
     */
    protected function getTokenEndpoint(SsoProviderConfig $config): string
    {
        $tenantId = $config->metadata['tenant_id'] ?? 'common';
        
        return $config->metadata['token_endpoint'] 
            ?? self::AZURE_BASE_URL . "/{$tenantId}/oauth2/v2.0/token";
    }
}
