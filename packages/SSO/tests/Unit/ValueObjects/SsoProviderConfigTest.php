<?php

declare(strict_types=1);

namespace Nexus\SSO\Tests\Unit\ValueObjects;

use DateTimeImmutable;
use Nexus\SSO\ValueObjects\AttributeMap;
use Nexus\SSO\ValueObjects\SsoProtocol;
use Nexus\SSO\ValueObjects\SsoProviderConfig;
use PHPUnit\Framework\TestCase;

final class SsoProviderConfigTest extends TestCase
{
    public function test_can_create_provider_config(): void
    {
        $attributeMap = new AttributeMap(
            mappings: ['email' => 'email', 'name' => 'displayName'],
            requiredFields: ['email']
        );
        
        $config = new SsoProviderConfig(
            providerName: 'azure',
            protocol: SsoProtocol::OIDC,
            clientId: 'client-id-123',
            clientSecret: 'secret-abc',
            discoveryUrl: 'https://login.microsoftonline.com/common/v2.0/.well-known/openid-configuration',
            redirectUri: 'https://app.example.com/sso/callback',
            attributeMap: $attributeMap,
            enabled: true
        );
        
        $this->assertSame('azure', $config->providerName);
        $this->assertSame(SsoProtocol::OIDC, $config->protocol);
        $this->assertSame('client-id-123', $config->clientId);
        $this->assertSame('secret-abc', $config->clientSecret);
        $this->assertSame('https://login.microsoftonline.com/common/v2.0/.well-known/openid-configuration', $config->discoveryUrl);
        $this->assertSame('https://app.example.com/sso/callback', $config->redirectUri);
        $this->assertSame($attributeMap, $config->attributeMap);
        $this->assertTrue($config->enabled);
    }
    
    public function test_optional_fields_default_correctly(): void
    {
        $config = new SsoProviderConfig(
            providerName: 'test-provider',
            protocol: SsoProtocol::SAML2,
            clientId: 'client-id',
            clientSecret: 'secret',
            discoveryUrl: 'https://example.com/metadata',
            redirectUri: 'https://app.com/callback'
        );
        
        // AttributeMap defaults
        $this->assertInstanceOf(AttributeMap::class, $config->attributeMap);
        $this->assertEquals(['email', 'sso_user_id'], $config->attributeMap->requiredFields);
        
        // Enabled defaults to true
        $this->assertTrue($config->enabled);
        
        // Scopes defaults to empty array
        $this->assertSame([], $config->scopes);
        
        // Metadata defaults to empty array
        $this->assertEmpty($config->metadata);
    }
    
    public function test_can_set_optional_scopes(): void
    {
        $config = new SsoProviderConfig(
            providerName: 'google',
            protocol: SsoProtocol::OAuth2,
            clientId: 'client-id',
            clientSecret: 'secret',
            discoveryUrl: 'https://accounts.google.com/.well-known/openid-configuration',
            redirectUri: 'https://app.com/callback',
            scopes: ['openid', 'email', 'profile']
        );
        
        $this->assertSame(['openid', 'email', 'profile'], $config->scopes);
    }
    
    public function test_can_set_metadata(): void
    {
        $metadata = [
            'tenant_id' => 'abc-123',
            'environment' => 'production',
            'jit_provisioning' => true,
        ];
        
        $config = new SsoProviderConfig(
            providerName: 'azure',
            protocol: SsoProtocol::OIDC,
            clientId: 'client-id',
            clientSecret: 'secret',
            discoveryUrl: 'https://example.com',
            redirectUri: 'https://app.com/callback',
            metadata: $metadata
        );
        
        $this->assertSame($metadata, $config->metadata);
        $this->assertSame('abc-123', $config->metadata['tenant_id']);
    }
    
    public function test_disabled_provider(): void
    {
        $config = new SsoProviderConfig(
            providerName: 'disabled-provider',
            protocol: SsoProtocol::SAML2,
            clientId: 'client-id',
            clientSecret: 'secret',
            discoveryUrl: 'https://example.com',
            redirectUri: 'https://app.com/callback',
            enabled: false
        );
        
        $this->assertFalse($config->enabled);
    }
}
