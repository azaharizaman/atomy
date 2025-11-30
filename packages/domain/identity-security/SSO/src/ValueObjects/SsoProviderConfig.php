<?php

declare(strict_types=1);

namespace Nexus\SSO\ValueObjects;

/**
 * SSO Provider Configuration
 * 
 * Immutable value object representing the configuration for an SSO provider
 */
final readonly class SsoProviderConfig
{
    /**
     * @param string $providerName Unique identifier for the provider (e.g., 'azure', 'google')
     * @param SsoProtocol $protocol SSO protocol to use
     * @param string $clientId OAuth2/OIDC client ID or SAML entity ID
     * @param string $clientSecret OAuth2/OIDC client secret or SAML certificate
     * @param string $discoveryUrl OIDC discovery URL or SAML metadata URL
     * @param string $redirectUri Callback/redirect URI after authentication
     * @param AttributeMap $attributeMap Attribute mapping configuration
     * @param bool $enabled Whether this provider is enabled
     * @param array<string> $scopes OAuth2/OIDC scopes to request
     * @param array<string, mixed> $metadata Additional provider-specific metadata
     */
    public function __construct(
        public string $providerName,
        public SsoProtocol $protocol,
        public string $clientId,
        public string $clientSecret,
        public string $discoveryUrl,
        public string $redirectUri,
        public AttributeMap $attributeMap = new AttributeMap(mappings: [], requiredFields: ['email', 'sso_user_id']),
        public bool $enabled = true,
        public array $scopes = [],
        public array $metadata = [],
    ) {
    }
}
