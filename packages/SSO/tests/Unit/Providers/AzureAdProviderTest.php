<?php

declare(strict_types=1);

namespace Nexus\SSO\Tests\Unit\Providers;

use Nexus\SSO\Enums\SsoProtocol;
use Nexus\SSO\Providers\AzureAdProvider;
use Nexus\SSO\ValueObjects\AttributeMap;
use Nexus\SSO\ValueObjects\SsoProviderConfig;
use PHPUnit\Framework\TestCase;

final class AzureAdProviderTest extends TestCase
{
    private AzureAdProvider $provider;

    protected function setUp(): void
    {
        $this->provider = new AzureAdProvider();
    }

    public function test_it_returns_correct_name(): void
    {
        $this->assertSame('azure', $this->provider->getName());
    }

    public function test_it_returns_correct_protocol(): void
    {
        $this->assertSame(SsoProtocol::OIDC, $this->provider->getProtocol());
    }

    public function test_it_supports_common_tenant(): void
    {
        $config = $this->createAzureConfig(tenantId: 'common');
        
        $authUrl = $this->provider->getAuthorizationUrl($config, 'test-state-123');
        
        $this->assertStringContainsString('login.microsoftonline.com/common/oauth2/v2.0/authorize', $authUrl);
    }

    public function test_it_supports_organizations_tenant(): void
    {
        $config = $this->createAzureConfig(tenantId: 'organizations');
        
        $authUrl = $this->provider->getAuthorizationUrl($config, 'test-state-123');
        
        $this->assertStringContainsString('login.microsoftonline.com/organizations/oauth2/v2.0/authorize', $authUrl);
    }

    public function test_it_supports_specific_tenant_id(): void
    {
        $tenantId = '12345678-1234-1234-1234-123456789012';
        $config = $this->createAzureConfig(tenantId: $tenantId);
        
        $authUrl = $this->provider->getAuthorizationUrl($config, 'test-state-123');
        
        $this->assertStringContainsString("login.microsoftonline.com/{$tenantId}/oauth2/v2.0/authorize", $authUrl);
    }

    public function test_it_includes_openid_scope_automatically(): void
    {
        $config = $this->createAzureConfig(scopes: ['email', 'profile']);
        
        $authUrl = $this->provider->getAuthorizationUrl($config, 'test-state-123');
        
        // Parse URL to check scope parameter
        parse_str(parse_url($authUrl, PHP_URL_QUERY), $params);
        
        $this->assertStringContainsString('openid', $params['scope']);
        $this->assertStringContainsString('email', $params['scope']);
        $this->assertStringContainsString('profile', $params['scope']);
    }

    public function test_it_validates_config_requires_tenant_id(): void
    {
        $this->expectException(\Nexus\SSO\Exceptions\SsoConfigurationException::class);
        $this->expectExceptionMessage("Azure AD provider requires 'tenant_id' in metadata");
        
        $config = new SsoProviderConfig(
            clientId: 'test-client',
            clientSecret: 'test-secret',
            discoveryUrl: null,
            redirectUri: 'https://app.test/callback',
            metadata: [
                // Missing tenant_id
                'authorization_endpoint' => 'https://login.microsoftonline.com/common/oauth2/v2.0/authorize',
                'token_endpoint' => 'https://login.microsoftonline.com/common/oauth2/v2.0/token',
            ],
            scopes: ['openid', 'email', 'profile'],
            attributeMap: new AttributeMap([]),
        );
        
        $this->provider->validateConfig($config);
    }

    public function test_it_handles_callback_with_azure_id_token(): void
    {
        $config = $this->createAzureConfig(
            tenantId: 'common',
            metadata: [
                'tenant_id' => 'common',
                'authorization_endpoint' => 'https://login.microsoftonline.com/common/oauth2/v2.0/authorize',
                'token_endpoint' => 'https://login.microsoftonline.com/common/oauth2/v2.0/token',
                'userinfo_endpoint' => 'https://graph.microsoft.com/oidc/userinfo',
                'mock_id_token_claims' => [
                    'iss' => 'https://login.microsoftonline.com/common/v2.0',
                    'sub' => 'azure-user-123',
                    'aud' => 'test-client',
                    'exp' => 9999999999,
                    'iat' => 1700000000,
                    'oid' => '12345678-1234-1234-1234-123456789012', // Azure Object ID
                    'preferred_username' => 'user@contoso.com',
                    'email' => 'user@contoso.com',
                    'name' => 'Azure Test User',
                ],
            ]
        );

        $userProfile = $this->provider->handleCallback($config, ['code' => 'mock_authorization_code']);

        $this->assertSame('azure-user-123', $userProfile->ssoUserId);
        $this->assertSame('user@contoso.com', $userProfile->email);
        $this->assertSame('Azure Test User', $userProfile->displayName);
        $this->assertSame('12345678-1234-1234-1234-123456789012', $userProfile->getAttribute('oid'));
    }

    private function createAzureConfig(
        ?string $tenantId = 'common',
        array $scopes = ['openid', 'email', 'profile'],
        array $metadata = []
    ): SsoProviderConfig {
        $defaultMetadata = [
            'tenant_id' => $tenantId,
            'authorization_endpoint' => "https://login.microsoftonline.com/{$tenantId}/oauth2/v2.0/authorize",
            'token_endpoint' => "https://login.microsoftonline.com/{$tenantId}/oauth2/v2.0/token",
            'userinfo_endpoint' => 'https://graph.microsoft.com/oidc/userinfo',
        ];

        return new SsoProviderConfig(
            clientId: 'test-client',
            clientSecret: 'test-secret',
            discoveryUrl: "https://login.microsoftonline.com/{$tenantId}/v2.0/.well-known/openid-configuration",
            redirectUri: 'https://app.test/callback',
            metadata: array_merge($defaultMetadata, $metadata),
            scopes: $scopes,
            attributeMap: new AttributeMap([]),
        );
    }
}
